<?php

namespace App\Services\Midtrans\Dto;

final readonly class MidtransStatusResponse
{
    public function __construct(
        public string $transactionStatus,
        public ?string $fraudStatus,
        public string $grossAmount,
        public string $orderId,
        public string $statusCode,
        public ?string $paymentType,
        public ?string $settlementTime,
        public string $signatureKey,
    ) {}

    /**
     * Create from raw Midtrans Status API response array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionStatus: $data['transaction_status'],
            fraudStatus: $data['fraud_status'] ?? null,
            grossAmount: $data['gross_amount'],
            orderId: $data['order_id'],
            statusCode: $data['status_code'],
            paymentType: $data['payment_type'] ?? null,
            settlementTime: $data['settlement_time'] ?? null,
            signatureKey: $data['signature_key'],
        );
    }
}
