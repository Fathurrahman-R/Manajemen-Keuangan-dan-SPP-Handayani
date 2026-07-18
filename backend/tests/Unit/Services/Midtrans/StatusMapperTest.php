<?php

namespace Tests\Unit\Services\Midtrans;

use App\Services\Midtrans\MidtransInternalStatus;
use App\Services\Midtrans\StatusMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StatusMapperTest extends TestCase
{
    private StatusMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new StatusMapper;
    }

    #[DataProvider('statusMappingProvider')]
    public function test_maps_transaction_status_and_fraud_status_correctly(
        string $transactionStatus,
        ?string $fraudStatus,
        MidtransInternalStatus $expected,
    ): void {
        $this->assertSame($expected, $this->mapper->map($transactionStatus, $fraudStatus));
    }

    public static function statusMappingProvider(): array
    {
        return [
            'capture + accept → Capture' => ['capture', 'accept', MidtransInternalStatus::Capture],
            'capture + challenge → Deny' => ['capture', 'challenge', MidtransInternalStatus::Deny],
            'capture + deny → Deny' => ['capture', 'deny', MidtransInternalStatus::Deny],
            'capture + null → Deny' => ['capture', null, MidtransInternalStatus::Deny],
            'settlement → Settlement' => ['settlement', null, MidtransInternalStatus::Settlement],
            'pending → Pending' => ['pending', null, MidtransInternalStatus::Pending],
            'deny → Deny' => ['deny', null, MidtransInternalStatus::Deny],
            'cancel → Cancel' => ['cancel', null, MidtransInternalStatus::Cancel],
            'expire → Expire' => ['expire', null, MidtransInternalStatus::Expire],
            'failure → Failure' => ['failure', null, MidtransInternalStatus::Failure],
            'refund → Refund' => ['refund', null, MidtransInternalStatus::Refund],
            'partial_refund → PartialRefund' => ['partial_refund', null, MidtransInternalStatus::PartialRefund],
        ];
    }

    public function test_unknown_status_defaults_to_pending(): void
    {
        $this->assertSame(
            MidtransInternalStatus::Pending,
            $this->mapper->map('unknown_status', null),
        );
    }
}
