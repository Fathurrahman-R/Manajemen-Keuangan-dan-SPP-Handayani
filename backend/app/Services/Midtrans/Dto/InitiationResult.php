<?php

namespace App\Services\Midtrans\Dto;

use Carbon\CarbonImmutable;

final readonly class InitiationResult
{
    public function __construct(
        public string $orderId,
        public string $snapToken,
        public string $redirectUrl,
        public int $amountPaid,
        public int $feeAmount,
        public int $grossAmount,
        public CarbonImmutable $expiredAt,
    ) {}
}
