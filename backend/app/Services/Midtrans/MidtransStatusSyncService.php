<?php

namespace App\Services\Midtrans;

use App\Exceptions\Midtrans\MidtransStatusUnavailableException;
use App\Exceptions\Midtrans\TransactionAlreadyFinalException;
use App\Models\MidtransTransaction;
use App\Services\Midtrans\Dto\NotificationResult;

class MidtransStatusSyncService
{
    public function __construct(
        private MidtransClient $client,
        private MidtransNotificationService $notificationService,
        private MidtransLogService $logService,
        private SignatureVerifier $signatureVerifier,
    ) {}

    /**
     * Manually sync a transaction's status by querying Midtrans Status API.
     *
     * @throws TransactionAlreadyFinalException
     * @throws MidtransStatusUnavailableException
     */
    public function syncManual(MidtransTransaction $trx): NotificationResult
    {
        // 1. If transaction status is terminal → do NOT call Midtrans
        $currentStatus = MidtransInternalStatus::from($trx->status);
        if ($currentStatus->isTerminal()) {
            throw new TransactionAlreadyFinalException($trx->order_id, $currentStatus->value);
        }

        // 2. Call Midtrans Status API
        try {
            $statusResponse = $this->client->getStatus($trx->order_id);
        } catch (MidtransStatusUnavailableException $e) {
            throw $e;
        }

        // 3. Log outbound
        $this->logService->recordOutbound(
            'outbound_status',
            $trx->order_id,
            (int) $statusResponse->statusCode,
            json_encode([
                'transaction_status' => $statusResponse->transactionStatus,
                'fraud_status' => $statusResponse->fraudStatus,
                'gross_amount' => $statusResponse->grossAmount,
                'order_id' => $statusResponse->orderId,
                'status_code' => $statusResponse->statusCode,
                'payment_type' => $statusResponse->paymentType,
                'settlement_time' => $statusResponse->settlementTime,
            ]),
        );

        // 4. Delegate to notification service which manages its own DB
        // transaction + locking. Synthesize a webhook-shape payload so the
        // shared `processTransaction` flow can run unchanged.
        $payload = [
            'order_id' => $statusResponse->orderId,
            'transaction_status' => $statusResponse->transactionStatus,
            'fraud_status' => $statusResponse->fraudStatus,
            'gross_amount' => $statusResponse->grossAmount,
            'status_code' => $statusResponse->statusCode,
            'payment_type' => $statusResponse->paymentType,
            'settlement_time' => $statusResponse->settlementTime,
            'signature_key' => $statusResponse->signatureKey,
        ];

        return $this->notificationService->processVerifiedPayload($trx, $payload);
    }
}
