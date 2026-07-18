<?php

namespace App\Services\Midtrans\Dto;

final readonly class SnapPayload
{
    /**
     * @param  array<int, array{id: string, name: string, price: int, quantity: int}>  $itemDetails
     * @param  array{first_name: string, last_name: string, email: string}  $customerDetails
     * @param  array{start_time: string, unit: string, duration: int}  $expiry
     * @param  array{finish?: string, unfinish?: string, error?: string}|null  $callbacks
     * @param  list<string>|null  $enabledPayments  Snap codes to limit available channels
     */
    public function __construct(
        public string $orderId,
        public int $grossAmount,
        public array $itemDetails,
        public array $customerDetails,
        public array $expiry,
        public ?array $callbacks = null,
        public ?array $enabledPayments = null,
    ) {}
}
