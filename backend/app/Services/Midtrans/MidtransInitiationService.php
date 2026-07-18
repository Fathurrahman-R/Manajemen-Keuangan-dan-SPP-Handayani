<?php

namespace App\Services\Midtrans;

use App\Exceptions\Midtrans\AmountBelowMinimumException;
use App\Exceptions\Midtrans\AmountExceedsSisaException;
use App\Exceptions\Midtrans\MidtransDisabledException;
use App\Exceptions\Midtrans\MidtransNotConfiguredException;
use App\Exceptions\Midtrans\MidtransUnavailableException;
use App\Exceptions\Midtrans\TagihanForbiddenException;
use App\Exceptions\Midtrans\TagihanHasPendingTransactionException;
use App\Exceptions\Midtrans\TagihanNotFoundException;
use App\Exceptions\Midtrans\TagihanSudahLunasException;
use App\Models\MidtransTransaction;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\Midtrans\Dto\InitiationResult;
use App\Services\Midtrans\Dto\SnapPayload;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MidtransInitiationService
{
    public function __construct(
        private MidtransClient $client,
        private MidtransFeeService $feeService,
        private MidtransLogService $logService,
        private OrderIdGenerator $orderIdGenerator,
    ) {}

    /**
     * Initiate a Midtrans Snap payment for a Tagihan.
     *
     * @throws MidtransDisabledException
     * @throws MidtransNotConfiguredException
     * @throws TagihanNotFoundException
     * @throws TagihanForbiddenException
     * @throws TagihanSudahLunasException
     * @throws AmountBelowMinimumException
     * @throws AmountExceedsSisaException
     * @throws TagihanHasPendingTransactionException
     * @throws MidtransUnavailableException
     */
    public function initiate(User $user, string $kodeTagihan, int $amountPaid, ?string $paymentChannel = null): InitiationResult
    {
        // 1. Check feature flag
        if (! config('midtrans.enabled')) {
            throw new MidtransDisabledException;
        }

        // 2. Check client configuration
        if (! $this->client->isConfigured()) {
            throw new MidtransNotConfiguredException;
        }

        return DB::transaction(function () use ($user, $kodeTagihan, $amountPaid, $paymentChannel) {
            // 3. Load Tagihan FOR UPDATE
            $tagihan = Tagihan::with('jenis_tagihan')
                ->where('kode_tagihan', $kodeTagihan)
                ->lockForUpdate()
                ->first();

            if (! $tagihan) {
                throw new TagihanNotFoundException($kodeTagihan);
            }

            // 4. Verify ownership: user's siswa NIS must match tagihan.nis
            $userNis = $user->siswa->nis ?? null;
            if ($userNis === null || $userNis !== $tagihan->nis) {
                throw new TagihanForbiddenException($kodeTagihan);
            }

            // 5. Calculate Sisa_Tagihan
            // NOTE: Tagihan.tmp is the TOTAL ALREADY PAID (cumulative), NOT the bill amount.
            // The bill amount lives at jenis_tagihan.jumlah. So sisa = jumlah - tmp.
            $jumlahTagihan = (int) ($tagihan->jenis_tagihan->jumlah ?? 0);
            $sisaTagihan = $jumlahTagihan - (int) $tagihan->tmp;

            if ($sisaTagihan <= 0) {
                throw new TagihanSudahLunasException($kodeTagihan);
            }

            // 6. Validate amount
            $minAmount = (int) config('midtrans.min_amount');
            if ($amountPaid < $minAmount) {
                throw new AmountBelowMinimumException($amountPaid, $minAmount);
            }

            if ($amountPaid > $sisaTagihan) {
                throw new AmountExceedsSisaException($amountPaid, $sisaTagihan);
            }

            // 7. Check pending in-flight transaction
            $pendingTrx = MidtransTransaction::pendingInFlight()
                ->where('kode_tagihan', $kodeTagihan)
                ->first();

            if ($pendingTrx) {
                throw new TagihanHasPendingTransactionException([
                    'order_id' => $pendingTrx->order_id,
                    'amount_paid' => $pendingTrx->amount_paid,
                    'fee_amount' => $pendingTrx->fee_amount,
                    'gross_amount' => $pendingTrx->gross_amount,
                    'snap_token' => $pendingTrx->snap_token,
                    'redirect_url' => $pendingTrx->snap_redirect_url,
                ]);
            }

            // 8. Compute fee and gross, assert invariant
            $feeAmount = $this->feeService->computeFee($amountPaid, $paymentChannel);
            $grossAmount = $amountPaid + $feeAmount;
            $this->feeService->assertGrossInvariant($amountPaid, $feeAmount, $grossAmount);

            // 9. Generate order ID and persist MidtransTransaction
            $orderId = $this->orderIdGenerator->generate($kodeTagihan);
            $expiredAt = CarbonImmutable::now()->addMinutes((int) config('midtrans.expiry_minutes'));

            $trx = MidtransTransaction::create([
                'order_id' => $orderId,
                'kode_tagihan' => $kodeTagihan,
                'nis' => $tagihan->nis,
                'amount_paid' => $amountPaid,
                'fee_amount' => $feeAmount,
                'gross_amount' => $grossAmount,
                'currency' => 'IDR',
                'status' => MidtransInternalStatus::Pending->value,
                // Selected channel as a hint until Midtrans webhook overwrites
                // payment_type with the actual channel used by the buyer.
                'payment_type' => $paymentChannel ?: null,
                'expired_at' => $expiredAt,
                'initiator_user_id' => $user->id,
                'branch_id' => $tagihan->branch_id,
            ]);

            // 10. Build SnapPayload
            $siswa = $tagihan->siswa;
            $jenisTagihan = $tagihan->jenis_tagihan;

            $itemName = $jenisTagihan
                ? $jenisTagihan->nama
                : 'Tagihan';

            $itemDetails = [
                [
                    'id' => $kodeTagihan,
                    'name' => $itemName,
                    'price' => $amountPaid,
                    'quantity' => 1,
                ],
                [
                    'id' => 'FEE_MIDTRANS',
                    'name' => 'Biaya Admin Pembayaran Online',
                    'price' => $feeAmount,
                    'quantity' => 1,
                ],
            ];

            // Customer details
            $namaParts = $siswa ? explode(' ', trim($siswa->nama), 2) : [''];
            $firstName = $namaParts[0] ?? '';
            $lastName = $namaParts[1] ?? '';

            $email = '';
            if ($siswa) {
                $wali = $siswa->wali;
                $email = $wali->email ?? '';
            }

            $customerDetails = [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ];

            // Midtrans rejects empty/invalid emails. Only include the key when
            // we actually have a non-empty, syntactically valid address.
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $customerDetails['email'] = $email;
            }

            // Expiry
            $expiry = [
                'start_time' => now()->format('Y-m-d H:i:s +0700'),
                'unit' => 'minute',
                'duration' => (int) config('midtrans.expiry_minutes'),
            ];

            $snapPayload = new SnapPayload(
                orderId: $orderId,
                grossAmount: $grossAmount,
                itemDetails: $itemDetails,
                customerDetails: $customerDetails,
                expiry: $expiry,
                callbacks: $this->resolveSnapCallbacks(),
                enabledPayments: $this->resolveEnabledPayments($paymentChannel),
            );

            // 11. Call Midtrans Snap API
            try {
                $snapResponse = $this->client->createSnapTransaction($snapPayload);

                $trx->update([
                    'snap_token' => $snapResponse['token'],
                    'snap_redirect_url' => $snapResponse['redirect_url'],
                ]);

                // 12. Log outbound
                $this->logService->recordOutbound(
                    'outbound_charge',
                    $orderId,
                    200,
                    json_encode($snapResponse),
                );
            } catch (MidtransUnavailableException $e) {
                // 13. On Snap failure: mark transaction as failure, log, and re-throw
                $trx->update(['status' => MidtransInternalStatus::Failure->value]);

                $this->logService->recordOutbound(
                    'outbound_charge',
                    $orderId,
                    null,
                    json_encode(['error' => $e->getMessage()]),
                );

                throw $e;
            }

            return new InitiationResult(
                orderId: $orderId,
                snapToken: $snapResponse['token'],
                redirectUrl: $snapResponse['redirect_url'],
                amountPaid: $amountPaid,
                feeAmount: $feeAmount,
                grossAmount: $grossAmount,
                expiredAt: $expiredAt,
            );
        });
    }

    /**
     * Initiate a Midtrans Snap payment that settles multiple Tagihan in one
     * checkout. Each item in `$kodeTagihanList` is paid in full (lunas) — the
     * caller already validated the list. A single admin fee is added.
     *
     * @param  list<string>  $kodeTagihanList
     *
     * @throws MidtransDisabledException|MidtransNotConfiguredException
     * @throws TagihanNotFoundException|TagihanForbiddenException|TagihanSudahLunasException
     * @throws TagihanHasPendingTransactionException|MidtransUnavailableException
     */
    public function initiateBatch(User $user, array $kodeTagihanList, ?string $paymentChannel = null): InitiationResult
    {
        if (! config('midtrans.enabled')) {
            throw new MidtransDisabledException;
        }

        if (! $this->client->isConfigured()) {
            throw new MidtransNotConfiguredException;
        }

        $kodeTagihanList = array_values(array_unique(array_filter($kodeTagihanList)));

        if (empty($kodeTagihanList)) {
            throw new TagihanNotFoundException('-');
        }

        return DB::transaction(function () use ($user, $kodeTagihanList, $paymentChannel) {
            $userNis = $user->siswa->nis ?? null;
            if ($userNis === null) {
                throw new TagihanForbiddenException(implode(',', $kodeTagihanList));
            }

            // Lock all selected Tagihan up front (deterministic order to avoid deadlocks).
            $tagihanList = Tagihan::with('jenis_tagihan')
                ->whereIn('kode_tagihan', $kodeTagihanList)
                ->orderBy('kode_tagihan')
                ->lockForUpdate()
                ->get();

            if ($tagihanList->count() !== count($kodeTagihanList)) {
                throw new TagihanNotFoundException(implode(',', $kodeTagihanList));
            }

            $amountPaid = 0;
            $batchItems = [];
            $primary = null;

            foreach ($tagihanList as $tagihan) {
                if ($tagihan->nis !== $userNis) {
                    throw new TagihanForbiddenException($tagihan->kode_tagihan);
                }

                $jumlah = (int) ($tagihan->jenis_tagihan->jumlah ?? 0);
                $sisa = $jumlah - (int) $tagihan->tmp;

                if ($sisa <= 0) {
                    throw new TagihanSudahLunasException($tagihan->kode_tagihan);
                }

                // Reject if any tagihan in the batch has an in-flight pending tx.
                $pendingTrx = MidtransTransaction::pendingInFlight()
                    ->where('kode_tagihan', $tagihan->kode_tagihan)
                    ->first();
                if ($pendingTrx) {
                    throw new TagihanHasPendingTransactionException([
                        'order_id' => $pendingTrx->order_id,
                        'amount_paid' => $pendingTrx->amount_paid,
                        'fee_amount' => $pendingTrx->fee_amount,
                        'gross_amount' => $pendingTrx->gross_amount,
                        'snap_token' => $pendingTrx->snap_token,
                        'redirect_url' => $pendingTrx->snap_redirect_url,
                    ]);
                }

                $amountPaid += $sisa;
                $batchItems[] = [
                    'kode_tagihan' => $tagihan->kode_tagihan,
                    'amount' => $sisa,
                ];
                $primary ??= $tagihan;
            }

            $minAmount = (int) config('midtrans.min_amount');
            if ($amountPaid < $minAmount) {
                throw new AmountBelowMinimumException($amountPaid, $minAmount);
            }

            // Single fee for the whole batch (per user choice).
            $feeAmount = $this->feeService->computeFee($amountPaid, $paymentChannel);
            $grossAmount = $amountPaid + $feeAmount;
            $this->feeService->assertGrossInvariant($amountPaid, $feeAmount, $grossAmount);

            $orderId = $this->orderIdGenerator->generate($primary->kode_tagihan);
            $expiredAt = CarbonImmutable::now()->addMinutes((int) config('midtrans.expiry_minutes'));

            $trx = MidtransTransaction::create([
                'order_id' => $orderId,
                'kode_tagihan' => $primary->kode_tagihan,
                'batch_items' => $batchItems,
                'nis' => $primary->nis,
                'amount_paid' => $amountPaid,
                'fee_amount' => $feeAmount,
                'gross_amount' => $grossAmount,
                'currency' => 'IDR',
                'status' => MidtransInternalStatus::Pending->value,
                'payment_type' => $paymentChannel ?: null,
                'expired_at' => $expiredAt,
                'initiator_user_id' => $user->id,
                'branch_id' => $primary->branch_id,
            ]);

            // Build Snap line items: one per tagihan + one fee row.
            $itemDetails = [];
            foreach ($tagihanList as $tagihan) {
                $jumlah = (int) ($tagihan->jenis_tagihan->jumlah ?? 0);
                $sisa = $jumlah - (int) $tagihan->tmp;
                $itemDetails[] = [
                    'id' => $tagihan->kode_tagihan,
                    'name' => $tagihan->jenis_tagihan->nama ?? 'Tagihan',
                    'price' => $sisa,
                    'quantity' => 1,
                ];
            }
            $itemDetails[] = [
                'id' => 'FEE_MIDTRANS',
                'name' => 'Biaya Admin Pembayaran Online',
                'price' => $feeAmount,
                'quantity' => 1,
            ];

            $siswa = $primary->siswa;
            $namaParts = $siswa ? explode(' ', trim($siswa->nama), 2) : [''];
            $customerDetails = [
                'first_name' => $namaParts[0] ?? '',
                'last_name' => $namaParts[1] ?? '',
            ];
            $email = $siswa?->wali?->email ?? '';
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $customerDetails['email'] = $email;
            }

            $snapPayload = new SnapPayload(
                orderId: $orderId,
                grossAmount: $grossAmount,
                itemDetails: $itemDetails,
                customerDetails: $customerDetails,
                expiry: [
                    'start_time' => now()->format('Y-m-d H:i:s +0700'),
                    'unit' => 'minute',
                    'duration' => (int) config('midtrans.expiry_minutes'),
                ],
                callbacks: $this->resolveSnapCallbacks(),
                enabledPayments: $this->resolveEnabledPayments($paymentChannel),
            );

            try {
                $snapResponse = $this->client->createSnapTransaction($snapPayload);
                $trx->update([
                    'snap_token' => $snapResponse['token'],
                    'snap_redirect_url' => $snapResponse['redirect_url'],
                ]);
                $this->logService->recordOutbound('outbound_charge', $orderId, 200, json_encode($snapResponse));
            } catch (MidtransUnavailableException $e) {
                $trx->update(['status' => MidtransInternalStatus::Failure->value]);
                $this->logService->recordOutbound('outbound_charge', $orderId, null, json_encode(['error' => $e->getMessage()]));
                throw $e;
            }

            return new InitiationResult(
                orderId: $orderId,
                snapToken: $snapResponse['token'],
                redirectUrl: $snapResponse['redirect_url'],
                amountPaid: $amountPaid,
                feeAmount: $feeAmount,
                grossAmount: $grossAmount,
                expiredAt: $expiredAt,
            );
        });
    }

    /**
     * Resolve Snap callback URLs so siswa returns to the portal beranda after
     * finishing, cancelling, or hitting an error in Snap. Reads
     * `MIDTRANS_FINISH_URL` from `.env` and falls back to the configured
     * frontend `APP_URL` + portal path.
     */
    private function resolveSnapCallbacks(): ?array
    {
        $finish = config('midtrans.finish_url');

        if (empty($finish)) {
            return null;
        }

        return [
            'finish' => $finish,
            'unfinish' => $finish,
            'error' => $finish,
        ];
    }

    /**
     * Map our internal fee-channel key to the Snap `enabled_payments` codes so
     * the user sees only the channel they picked in the "Bayar Online" modal.
     *
     * Returns `null` (= show all channels) when the chosen key is unknown or
     * has no mapping.
     *
     * @return list<string>|null
     */
    private function resolveEnabledPayments(?string $channel): ?array
    {
        if ($channel === null || $channel === '') {
            return null;
        }

        // Mapping key kita -> Snap payment codes.
        // Lihat https://docs.midtrans.com/docs/snap-customization-snap-only-show-specific-payment-channel
        $map = [
            'qris' => ['other_qris'],
            'bank_transfer' => ['bca_va', 'bni_va', 'bri_va', 'mandiri_va', 'permata_va', 'cimb_va', 'other_va', 'echannel'],
            'gopay' => ['gopay'],
            'shopeepay' => ['shopeepay'],
            'credit_card' => ['credit_card'],
        ];

        if (! isset($map[$channel]) || empty($map[$channel])) {
            return null;
        }

        return $map[$channel];
    }
}
