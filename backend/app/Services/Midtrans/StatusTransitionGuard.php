<?php

namespace App\Services\Midtrans;

class StatusTransitionGuard
{
    /**
     * The allowed transitions set T_allowed.
     *
     * From pending: can go to {settlement, capture, deny, cancel, expire, failure, pending}
     * From settlement/capture: can go to {refund, partial_refund, settlement, capture} (self = no-op)
     * From partial_refund: can go to {partial_refund} (self = no-op)
     * From terminal {deny, cancel, expire, failure, refund}: only self (no-op)
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'pending' => [
            'pending',
            'settlement',
            'capture',
            'deny',
            'cancel',
            'expire',
            'failure',
        ],
        'settlement' => [
            'settlement',
            'capture',
            'refund',
            'partial_refund',
        ],
        'capture' => [
            'capture',
            'settlement',
            'refund',
            'partial_refund',
        ],
        'partial_refund' => [
            'partial_refund',
        ],
        'deny' => [
            'deny',
        ],
        'cancel' => [
            'cancel',
        ],
        'expire' => [
            'expire',
        ],
        'failure' => [
            'failure',
        ],
        'refund' => [
            'refund',
        ],
    ];

    /**
     * Check whether a status transition from $current to $next is allowed.
     */
    public function isAllowed(MidtransInternalStatus $current, MidtransInternalStatus $next): bool
    {
        $allowed = self::ALLOWED_TRANSITIONS[$current->value] ?? [];

        return in_array($next->value, $allowed, true);
    }

    /**
     * Check whether a given status is terminal (no further meaningful transitions).
     */
    public function isTerminal(MidtransInternalStatus $status): bool
    {
        return $status->isTerminal();
    }
}
