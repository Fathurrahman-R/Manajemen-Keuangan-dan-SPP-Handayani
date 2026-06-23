<?php

namespace Tests\Unit\Services\Midtrans;

use App\Services\Midtrans\MidtransInternalStatus;
use App\Services\Midtrans\StatusTransitionGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StatusTransitionGuardTest extends TestCase
{
    private StatusTransitionGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = new StatusTransitionGuard();
    }

    #[DataProvider('allowedTransitionsProvider')]
    public function test_allows_valid_transitions(
        MidtransInternalStatus $current,
        MidtransInternalStatus $next,
    ): void {
        $this->assertTrue($this->guard->isAllowed($current, $next));
    }

    #[DataProvider('rejectedTransitionsProvider')]
    public function test_rejects_invalid_transitions(
        MidtransInternalStatus $current,
        MidtransInternalStatus $next,
    ): void {
        $this->assertFalse($this->guard->isAllowed($current, $next));
    }

    public static function allowedTransitionsProvider(): array
    {
        return [
            // From pending → all non-refund statuses
            'pending → pending (no-op)' => [MidtransInternalStatus::Pending, MidtransInternalStatus::Pending],
            'pending → settlement' => [MidtransInternalStatus::Pending, MidtransInternalStatus::Settlement],
            'pending → capture' => [MidtransInternalStatus::Pending, MidtransInternalStatus::Capture],
            'pending → deny' => [MidtransInternalStatus::Pending, MidtransInternalStatus::Deny],
            'pending → cancel' => [MidtransInternalStatus::Pending, MidtransInternalStatus::Cancel],
            'pending → expire' => [MidtransInternalStatus::Pending, MidtransInternalStatus::Expire],
            'pending → failure' => [MidtransInternalStatus::Pending, MidtransInternalStatus::Failure],

            // From settlement → refund paths + self
            'settlement → settlement (no-op)' => [MidtransInternalStatus::Settlement, MidtransInternalStatus::Settlement],
            'settlement → capture' => [MidtransInternalStatus::Settlement, MidtransInternalStatus::Capture],
            'settlement → refund' => [MidtransInternalStatus::Settlement, MidtransInternalStatus::Refund],
            'settlement → partial_refund' => [MidtransInternalStatus::Settlement, MidtransInternalStatus::PartialRefund],

            // From capture → refund paths + self
            'capture → capture (no-op)' => [MidtransInternalStatus::Capture, MidtransInternalStatus::Capture],
            'capture → settlement' => [MidtransInternalStatus::Capture, MidtransInternalStatus::Settlement],
            'capture → refund' => [MidtransInternalStatus::Capture, MidtransInternalStatus::Refund],
            'capture → partial_refund' => [MidtransInternalStatus::Capture, MidtransInternalStatus::PartialRefund],

            // From partial_refund → self only
            'partial_refund → partial_refund (no-op)' => [MidtransInternalStatus::PartialRefund, MidtransInternalStatus::PartialRefund],

            // Terminal self-transitions (no-op)
            'deny → deny (no-op)' => [MidtransInternalStatus::Deny, MidtransInternalStatus::Deny],
            'cancel → cancel (no-op)' => [MidtransInternalStatus::Cancel, MidtransInternalStatus::Cancel],
            'expire → expire (no-op)' => [MidtransInternalStatus::Expire, MidtransInternalStatus::Expire],
            'failure → failure (no-op)' => [MidtransInternalStatus::Failure, MidtransInternalStatus::Failure],
            'refund → refund (no-op)' => [MidtransInternalStatus::Refund, MidtransInternalStatus::Refund],
        ];
    }

    public static function rejectedTransitionsProvider(): array
    {
        return [
            // From pending → cannot go to refund/partial_refund directly
            'pending → refund' => [MidtransInternalStatus::Pending, MidtransInternalStatus::Refund],
            'pending → partial_refund' => [MidtransInternalStatus::Pending, MidtransInternalStatus::PartialRefund],

            // Terminal statuses cannot transition to other statuses
            'deny → settlement' => [MidtransInternalStatus::Deny, MidtransInternalStatus::Settlement],
            'deny → pending' => [MidtransInternalStatus::Deny, MidtransInternalStatus::Pending],
            'cancel → settlement' => [MidtransInternalStatus::Cancel, MidtransInternalStatus::Settlement],
            'expire → pending' => [MidtransInternalStatus::Expire, MidtransInternalStatus::Pending],
            'failure → settlement' => [MidtransInternalStatus::Failure, MidtransInternalStatus::Settlement],
            'refund → settlement' => [MidtransInternalStatus::Refund, MidtransInternalStatus::Settlement],

            // settlement cannot go back to pending or deny
            'settlement → pending' => [MidtransInternalStatus::Settlement, MidtransInternalStatus::Pending],
            'settlement → deny' => [MidtransInternalStatus::Settlement, MidtransInternalStatus::Deny],
            'settlement → cancel' => [MidtransInternalStatus::Settlement, MidtransInternalStatus::Cancel],
            'settlement → expire' => [MidtransInternalStatus::Settlement, MidtransInternalStatus::Expire],
            'settlement → failure' => [MidtransInternalStatus::Settlement, MidtransInternalStatus::Failure],

            // capture cannot go back to pending or deny
            'capture → pending' => [MidtransInternalStatus::Capture, MidtransInternalStatus::Pending],
            'capture → deny' => [MidtransInternalStatus::Capture, MidtransInternalStatus::Deny],
            'capture → cancel' => [MidtransInternalStatus::Capture, MidtransInternalStatus::Cancel],

            // partial_refund cannot transition to anything except itself
            'partial_refund → settlement' => [MidtransInternalStatus::PartialRefund, MidtransInternalStatus::Settlement],
            'partial_refund → pending' => [MidtransInternalStatus::PartialRefund, MidtransInternalStatus::Pending],
            'partial_refund → refund' => [MidtransInternalStatus::PartialRefund, MidtransInternalStatus::Refund],
        ];
    }

    public function test_is_terminal_returns_true_for_terminal_statuses(): void
    {
        $terminalStatuses = [
            MidtransInternalStatus::Settlement,
            MidtransInternalStatus::Capture,
            MidtransInternalStatus::Deny,
            MidtransInternalStatus::Cancel,
            MidtransInternalStatus::Expire,
            MidtransInternalStatus::Failure,
            MidtransInternalStatus::Refund,
        ];

        foreach ($terminalStatuses as $status) {
            $this->assertTrue(
                $this->guard->isTerminal($status),
                "Expected {$status->value} to be terminal",
            );
        }
    }

    public function test_is_terminal_returns_false_for_non_terminal_statuses(): void
    {
        $nonTerminalStatuses = [
            MidtransInternalStatus::Pending,
            MidtransInternalStatus::PartialRefund,
        ];

        foreach ($nonTerminalStatuses as $status) {
            $this->assertFalse(
                $this->guard->isTerminal($status),
                "Expected {$status->value} to NOT be terminal",
            );
        }
    }
}
