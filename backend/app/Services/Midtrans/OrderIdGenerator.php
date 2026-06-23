<?php

namespace App\Services\Midtrans;

use InvalidArgumentException;

class OrderIdGenerator
{
    /**
     * Maximum length allowed by Midtrans for order_id.
     */
    private const MAX_LENGTH = 50;

    /**
     * Valid character pattern for Midtrans order_id: alphanumeric, dash, underscore, dot.
     */
    private const VALID_PATTERN = '/^[a-zA-Z0-9\-_.]+$/';

    /**
     * Generate a unique order ID for Midtrans.
     *
     * Format: HDY-{kode_tagihan}-{epoch_ms}
     */
    public function generate(string $kodeTagihan): string
    {
        $prefix = config('midtrans.order_prefix', 'HDY');
        $epochMs = (int) (microtime(true) * 1000);

        $orderId = sprintf('%s-%s-%d', $prefix, $kodeTagihan, $epochMs);

        $this->validate($orderId);

        return $orderId;
    }

    /**
     * Validate that the generated order_id meets Midtrans constraints.
     *
     * @throws InvalidArgumentException
     */
    private function validate(string $orderId): void
    {
        if (mb_strlen($orderId) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Generated order_id "%s" exceeds maximum length of %d characters (got %d).',
                    $orderId,
                    self::MAX_LENGTH,
                    mb_strlen($orderId),
                )
            );
        }

        if (! preg_match(self::VALID_PATTERN, $orderId)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Generated order_id "%s" contains invalid characters. Only alphanumeric, dash, underscore, and dot are allowed.',
                    $orderId,
                )
            );
        }
    }
}
