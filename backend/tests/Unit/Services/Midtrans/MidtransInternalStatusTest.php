<?php

namespace Tests\Unit\Services\Midtrans;

use App\Services\Midtrans\MidtransInternalStatus;
use PHPUnit\Framework\TestCase;

class MidtransInternalStatusTest extends TestCase
{
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
            $this->assertTrue($status->isTerminal(), "{$status->value} should be terminal");
        }
    }

    public function test_is_terminal_returns_false_for_non_terminal_statuses(): void
    {
        $this->assertFalse(MidtransInternalStatus::Pending->isTerminal());
        $this->assertFalse(MidtransInternalStatus::PartialRefund->isTerminal());
    }

    public function test_is_success_returns_true_for_settlement_and_capture(): void
    {
        $this->assertTrue(MidtransInternalStatus::Settlement->isSuccess());
        $this->assertTrue(MidtransInternalStatus::Capture->isSuccess());
    }

    public function test_is_success_returns_false_for_non_success_statuses(): void
    {
        $nonSuccess = [
            MidtransInternalStatus::Pending,
            MidtransInternalStatus::Deny,
            MidtransInternalStatus::Cancel,
            MidtransInternalStatus::Expire,
            MidtransInternalStatus::Failure,
            MidtransInternalStatus::Refund,
            MidtransInternalStatus::PartialRefund,
        ];

        foreach ($nonSuccess as $status) {
            $this->assertFalse($status->isSuccess(), "{$status->value} should NOT be success");
        }
    }

    public function test_enum_has_all_nine_cases(): void
    {
        $this->assertCount(9, MidtransInternalStatus::cases());
    }

    public function test_enum_backed_values_are_lowercase(): void
    {
        foreach (MidtransInternalStatus::cases() as $case) {
            $this->assertSame(strtolower($case->value), $case->value);
        }
    }
}
