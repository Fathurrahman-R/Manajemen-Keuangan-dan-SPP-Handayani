<?php

namespace App\Services\Midtrans;

use App\Services\Midtrans\Dto\MidtransStatusResponse;
use App\Services\Midtrans\Dto\SnapPayload;

interface MidtransClient
{
    /**
     * Create a Snap transaction via Midtrans API.
     *
     * @return array{token: string, redirect_url: string}
     */
    public function createSnapTransaction(SnapPayload $payload): array;

    /**
     * Get the status of a transaction from Midtrans Status API.
     */
    public function getStatus(string $orderId): MidtransStatusResponse;

    /**
     * Check if Midtrans credentials are fully configured.
     */
    public function isConfigured(): bool;
}
