<?php

namespace App\Services\Midtrans;

class SignatureVerifier
{
    /**
     * Compute the expected signature for a Midtrans notification.
     *
     * Formula: SHA-512(order_id + status_code + gross_amount + server_key)
     */
    public function compute(string $orderId, string $statusCode, string $grossAmount, string $serverKey): string
    {
        return hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
    }

    /**
     * Verify that a Midtrans notification payload has a valid signature.
     *
     * Uses constant-time comparison to prevent timing attacks.
     */
    public function verify(array $payload, string $serverKey): bool
    {
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        $computed = $this->compute($orderId, $statusCode, $grossAmount, $serverKey);

        return hash_equals($computed, $signatureKey);
    }
}
