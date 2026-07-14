<?php

namespace App\Services\Midtrans;

use App\Events\PembayaranRecorded;
use App\Exceptions\Midtrans\OverpaymentBlockedException;
use App\Exceptions\Midtrans\WebhookDisabledException;
use App\Models\MidtransTransaction;
use App\Models\Pembayaran;
use App\Services\GenerateKodePembayaran;
use App\Services\Midtrans\Dto\NotificationResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransNotificationService
{
    public function __construct(
        private SignatureVerifier $signatureVerifier,
        private StatusMapper $statusMapper,
        private StatusTransitionGuard $transitionGuard,
        private MidtransLogService $logService,
        private MidtransFeeService $feeService,
    ) {}

    /**
     * Handle an inbound Midtrans webhook notification.
     *
     * @throws WebhookDisabledException
     */
    public function handle(array $rawPayload, string $rawBody, ?string $remoteIp): NotificationResult
    {
        // 1. Check webhook enabled
        if (! config('midtrans.webhook_enabled')) {
            throw new WebhookDisabledException;
        }

        $orderId = $rawPayload['order_id'] ?? null;

        // 2. Record inbound log (before anything else)
        $this->logService->recordInbound($rawBody, $remoteIp, $orderId);

        // 3. Verify signature
        $serverKey = config('midtrans.server_key');
        if (! $this->signatureVerifier->verify($rawPayload, $serverKey)) {
            Log::warning('MidtransNotification: invalid signature', [
                'order_id' => $orderId,
                'remote_ip' => $remoteIp,
            ]);

            return NotificationResult::rejected(403, 'INVALID_SIGNATURE');
        }

        // 4. DB transaction with retry for deadlocks
        return DB::transaction(function () use ($rawPayload, $orderId) {
            // 4a. Load MidtransTransaction FOR UPDATE
            $trx = MidtransTransaction::where('order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if (! $trx) {
                return NotificationResult::rejected(404, 'ORDER_NOT_FOUND');
            }

            // Delegate to shared processing logic
            return $this->processTransaction($trx, $rawPayload);
        }, 2); // Retry 2x on deadlock
    }

    /**
     * Process a verified payload for a transaction.
     *
     * Manages its own DB transaction with deadlock retry, mirroring `handle()`.
     * Used by the sync service which has already issued the outbound API call.
     */
    public function processVerifiedPayload(MidtransTransaction $trx, array $payload): NotificationResult
    {
        return DB::transaction(function () use ($trx, $payload) {
            $lockedTrx = MidtransTransaction::where('order_id', $trx->order_id)
                ->lockForUpdate()
                ->first();

            if (! $lockedTrx) {
                return NotificationResult::rejected(404, 'ORDER_NOT_FOUND');
            }

            return $this->processTransaction($lockedTrx, $payload);
        }, 2);
    }

    /**
     * Shared processing logic for both webhook and sync flows.
     *
     * Expects the transaction to be locked (FOR UPDATE) by the caller.
     */
    private function processTransaction(MidtransTransaction $trx, array $payload): NotificationResult
    {
        // 4b. Verify gross_amount matches
        $payloadGross = $payload['gross_amount'] ?? null;
        if ($payloadGross !== null && (int) str_replace('.00', '', $payloadGross) !== $trx->gross_amount) {
            // Compare as integers (Midtrans sends "14000.00" format)
            $payloadGrossInt = (int) floatval($payloadGross);
            if ($payloadGrossInt !== $trx->gross_amount) {
                Log::error('MidtransNotification: amount mismatch', [
                    'order_id' => $trx->order_id,
                    'expected' => $trx->gross_amount,
                    'received' => $payloadGross,
                ]);

                return NotificationResult::rejected(422, 'AMOUNT_MISMATCH');
            }
        }

        // 4c. Map status
        $transactionStatus = $payload['transaction_status'] ?? 'pending';
        $fraudStatus = $payload['fraud_status'] ?? null;
        $newStatus = $this->statusMapper->map($transactionStatus, $fraudStatus);

        // 4d. Check transition
        $currentStatus = MidtransInternalStatus::from($trx->status);

        if ($currentStatus === $newStatus) {
            // Same status → no-op
            return NotificationResult::ok();
        }

        if (! $this->transitionGuard->isAllowed($currentStatus, $newStatus)) {
            return NotificationResult::rejected(409, 'INVALID_STATUS_TRANSITION');
        }

        // 4e. Update MidtransTransaction
        $updateData = [
            'status' => $newStatus->value,
            'payment_type' => $payload['payment_type'] ?? $trx->payment_type,
            'last_raw_response' => $payload,
        ];

        if ($newStatus->isSuccess() && isset($payload['settlement_time'])) {
            $updateData['paid_at'] = Carbon::parse($payload['settlement_time']);
        }

        $trx->update($updateData);

        // 4f. If status is success → record pembayaran
        if ($newStatus->isSuccess()) {
            $this->recordPembayaran($trx, $payload);
        }

        return NotificationResult::ok();
    }

    /**
     * Record Pembayaran(s) for a successful Midtrans transaction.
     *
     * For batch transactions, materialise one Pembayaran per item in
     * `batch_items`. Single transactions remain unchanged.
     *
     * Idempotent: skips if a Pembayaran with the same midtrans_order_id already exists.
     *
     * @throws OverpaymentBlockedException
     */
    private function recordPembayaran(MidtransTransaction $trx, array $payload): void
    {
        // Idempotent guard — works for both single and batch.
        $existing = Pembayaran::where('midtrans_order_id', $trx->order_id)->first();
        if ($existing) {
            return;
        }

        $tanggal = isset($payload['settlement_time'])
            ? Carbon::parse($payload['settlement_time'])->format('Y-m-d')
            : now()->format('Y-m-d');

        $pembayar = $trx->initiator?->name ?? '';

        // Batch flow: one Pembayaran per item, all sharing midtrans_order_id.
        // Note: midtrans_order_id has a unique constraint so we must guard
        // it manually — tag only the FIRST row with the order id and leave
        // subsequent ones NULL (still discoverable via kode_pembayaran prefix).
        if ($trx->isBatch()) {
            $first = true;
            foreach ((array) $trx->batch_items as $item) {
                $kodeTagihan = (string) ($item['kode_tagihan'] ?? '');
                $amount = (int) ($item['amount'] ?? 0);
                if ($kodeTagihan === '' || $amount <= 0) {
                    continue;
                }

                $tagihan = \App\Models\Tagihan::with('jenis_tagihan')
                    ->where('kode_tagihan', $kodeTagihan)
                    ->lockForUpdate()
                    ->first();

                if (! $tagihan) {
                    continue;
                }

                $jumlahTagihan = (int) ($tagihan->jenis_tagihan->jumlah ?? 0);
                $sisaTagihan = $jumlahTagihan - (int) $tagihan->tmp;

                if ($sisaTagihan < $amount) {
                    Log::error('MidtransNotification: OVERPAYMENT_BLOCKED (batch)', [
                        'order_id' => $trx->order_id,
                        'kode_tagihan' => $kodeTagihan,
                        'amount' => $amount,
                        'sisa_tagihan' => $sisaTagihan,
                    ]);
                    throw new OverpaymentBlockedException($trx->order_id, $amount, $sisaTagihan);
                }

                $pembayaran = Pembayaran::create([
                    'kode_pembayaran' => GenerateKodePembayaran::generate(),
                    'kode_tagihan' => $kodeTagihan,
                    'tanggal' => $tanggal,
                    'metode' => 'online_midtrans',
                    'jumlah' => $amount,
                    'pembayar' => $pembayar,
                    'branch_id' => $tagihan->branch_id ?? $trx->branch_id,
                    // Only the primary row carries the order_id to satisfy the
                    // unique constraint while still keeping batch traceability
                    // through MidtransTransaction.batch_items.
                    'midtrans_order_id' => $first ? $trx->order_id : null,
                ]);

                $tmpBaru = (int) $tagihan->tmp + $amount;
                $tagihan->update([
                    'tmp' => $tmpBaru,
                    'status' => $tmpBaru >= $jumlahTagihan ? 'Lunas' : 'Belum Lunas',
                ]);

                PembayaranRecorded::dispatch($pembayaran);
                $first = false;
            }

            return;
        }

        // Single-tagihan flow (unchanged).
        $tagihan = $trx->tagihan()->with('jenis_tagihan')->lockForUpdate()->first();

        if ($tagihan) {
            $jumlahTagihan = (int) ($tagihan->jenis_tagihan->jumlah ?? 0);
            $sisaTagihan = $jumlahTagihan - (int) $tagihan->tmp;

            if ($sisaTagihan < $trx->amount_paid) {
                Log::error('MidtransNotification: OVERPAYMENT_BLOCKED', [
                    'order_id' => $trx->order_id,
                    'amount_paid' => $trx->amount_paid,
                    'sisa_tagihan' => $sisaTagihan,
                ]);

                throw new OverpaymentBlockedException(
                    $trx->order_id,
                    $trx->amount_paid,
                    $sisaTagihan,
                );
            }
        }

        $pembayaran = Pembayaran::create([
            'kode_pembayaran' => GenerateKodePembayaran::generate(),
            'kode_tagihan' => $trx->kode_tagihan,
            'tanggal' => $tanggal,
            'metode' => 'online_midtrans',
            'jumlah' => $trx->amount_paid,
            'pembayar' => $pembayar,
            'branch_id' => $tagihan->branch_id ?? $trx->branch_id,
            'midtrans_order_id' => $trx->order_id,
        ]);

        if ($tagihan) {
            $jumlahTagihan = (int) ($tagihan->jenis_tagihan->jumlah ?? 0);
            $tmpBaru = (int) $tagihan->tmp + (int) $trx->amount_paid;

            $tagihan->update([
                'tmp' => $tmpBaru,
                'status' => $tmpBaru >= $jumlahTagihan ? 'Lunas' : 'Belum Lunas',
            ]);
        }

        PembayaranRecorded::dispatch($pembayaran);
    }
}
