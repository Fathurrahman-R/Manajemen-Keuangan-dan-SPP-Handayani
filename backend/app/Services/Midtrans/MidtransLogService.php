<?php

namespace App\Services\Midtrans;

use App\Models\MidtransTransactionLog;
use Illuminate\Support\Facades\Log;

class MidtransLogService
{
    /**
     * Record an inbound webhook notification.
     */
    public function recordInbound(string $rawBody, ?string $remoteIp, ?string $orderId = null): void
    {
        try {
            $maskedPayload = $this->mask($rawBody);

            if ($maskedPayload === null) {
                return; // Safety net triggered; do not persist
            }

            MidtransTransactionLog::create([
                'order_id' => $orderId,
                'direction' => 'inbound_notification',
                'http_status' => null,
                'raw_payload' => $maskedPayload,
                'remote_ip' => $remoteIp,
            ]);
        } catch (\Throwable $e) {
            Log::error('MidtransLogService::recordInbound failed', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ]);
        }
    }

    /**
     * Record an outbound API call (charge or status).
     */
    public function recordOutbound(string $direction, string $orderId, ?int $httpStatus, string $rawPayload): void
    {
        try {
            $maskedPayload = $this->mask($rawPayload);

            if ($maskedPayload === null) {
                return; // Safety net triggered; do not persist
            }

            MidtransTransactionLog::create([
                'order_id' => $orderId,
                'direction' => $direction,
                'http_status' => $httpStatus,
                'raw_payload' => $maskedPayload,
                'remote_ip' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('MidtransLogService::recordOutbound failed', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'direction' => $direction,
            ]);
        }
    }

    /**
     * Mask sensitive fields in a JSON payload.
     *
     * Returns the masked string, or null if the safety net is triggered
     * (meaning the server_key literal was still found after masking).
     */
    private function mask(string $payload): ?string
    {
        $decoded = json_decode($payload, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Mask known sensitive keys
            $sensitiveKeys = ['server_key', 'signature_key'];

            array_walk_recursive($decoded, function (&$value, $key) use ($sensitiveKeys) {
                if (in_array($key, $sensitiveKeys, true)) {
                    $value = '***MASKED***';
                }
            });

            $masked = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            // Not valid JSON — attempt string-level masking
            $masked = $payload;

            // Mask any known patterns
            $masked = preg_replace('/"server_key"\s*:\s*"[^"]*"/', '"server_key":"***MASKED***"', $masked);
            $masked = preg_replace('/"signature_key"\s*:\s*"[^"]*"/', '"signature_key":"***MASKED***"', $masked);
        }

        // Safety net: check if the literal server_key value is still present
        $serverKey = config('midtrans.server_key');

        if (! empty($serverKey) && str_contains($masked, $serverKey)) {
            Log::critical('Midtrans log masking safety net triggered', [
                'reason' => 'server_key literal found in masked output',
            ]);

            return null;
        }

        return $masked;
    }
}
