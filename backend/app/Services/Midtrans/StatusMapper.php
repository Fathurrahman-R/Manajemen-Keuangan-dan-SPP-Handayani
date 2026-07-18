<?php

namespace App\Services\Midtrans;

class StatusMapper
{
    /**
     * Map Midtrans transaction_status and fraud_status to internal status.
     *
     * Mapping per Requirement 5 AC 5:
     * - capture + accept → Capture
     * - settlement → Settlement
     * - pending → Pending
     * - deny → Deny
     * - cancel → Cancel
     * - expire → Expire
     * - failure → Failure
     * - refund → Refund
     * - partial_refund → PartialRefund
     *
     * For capture without fraud_status = 'accept', maps to Deny (fraud challenge/deny).
     */
    public function map(string $transactionStatus, ?string $fraudStatus = null): MidtransInternalStatus
    {
        return match ($transactionStatus) {
            'capture' => $fraudStatus === 'accept'
                ? MidtransInternalStatus::Capture
                : MidtransInternalStatus::Deny,
            'settlement' => MidtransInternalStatus::Settlement,
            'pending' => MidtransInternalStatus::Pending,
            'deny' => MidtransInternalStatus::Deny,
            'cancel' => MidtransInternalStatus::Cancel,
            'expire' => MidtransInternalStatus::Expire,
            'failure' => MidtransInternalStatus::Failure,
            'refund' => MidtransInternalStatus::Refund,
            'partial_refund' => MidtransInternalStatus::PartialRefund,
            default => MidtransInternalStatus::Pending,
        };
    }
}
