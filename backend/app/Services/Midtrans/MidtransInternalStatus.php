<?php

namespace App\Services\Midtrans;

enum MidtransInternalStatus: string
{
    case Pending = 'pending';
    case Settlement = 'settlement';
    case Capture = 'capture';
    case Deny = 'deny';
    case Cancel = 'cancel';
    case Expire = 'expire';
    case Failure = 'failure';
    case Refund = 'refund';
    case PartialRefund = 'partial_refund';

    /**
     * Whether this status is terminal (no further transitions allowed except refund from settlement/capture).
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Settlement,
            self::Capture,
            self::Deny,
            self::Cancel,
            self::Expire,
            self::Failure,
            self::Refund => true,
            default => false,
        };
    }

    /**
     * Whether this status represents a successful payment.
     */
    public function isSuccess(): bool
    {
        return match ($this) {
            self::Settlement, self::Capture => true,
            default => false,
        };
    }
}
