<?php

namespace Tests\Stubs;

use App\Exceptions\Midtrans\MidtransStatusUnavailableException;
use App\Exceptions\Midtrans\MidtransUnavailableException;
use App\Services\Midtrans\Dto\MidtransStatusResponse;
use App\Services\Midtrans\Dto\SnapPayload;
use App\Services\Midtrans\MidtransClient;

class FakeMidtransClient implements MidtransClient
{
    private ?array $snapResponse = null;

    private ?MidtransStatusResponse $statusResponse = null;

    private bool $shouldFail = false;

    /**
     * Configure the fake to return a specific Snap response.
     */
    public function shouldReturnSnap(string $token = 'fake-token', string $url = 'https://fake.midtrans.com/snap'): self
    {
        $this->snapResponse = [
            'token' => $token,
            'redirect_url' => $url,
        ];
        $this->shouldFail = false;

        return $this;
    }

    /**
     * Configure the fake to throw an exception on any call.
     */
    public function shouldFail(): self
    {
        $this->shouldFail = true;

        return $this;
    }

    /**
     * Configure the fake to return a specific status response.
     */
    public function shouldReturnStatus(
        string $transactionStatus,
        ?string $fraudStatus = null,
        string $grossAmount = '50000.00',
        string $orderId = 'HDY-TEST-001',
        string $statusCode = '200',
        ?string $paymentType = 'bank_transfer',
        ?string $settlementTime = null,
        string $signatureKey = 'fake-signature-key',
    ): self {
        $this->statusResponse = new MidtransStatusResponse(
            transactionStatus: $transactionStatus,
            fraudStatus: $fraudStatus,
            grossAmount: $grossAmount,
            orderId: $orderId,
            statusCode: $statusCode,
            paymentType: $paymentType,
            settlementTime: $settlementTime,
            signatureKey: $signatureKey,
        );
        $this->shouldFail = false;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function createSnapTransaction(SnapPayload $payload): array
    {
        if ($this->shouldFail) {
            throw new MidtransUnavailableException('Fake Midtrans client forced failure.');
        }

        return $this->snapResponse ?? [
            'token' => 'fake-token-' . $payload->orderId,
            'redirect_url' => 'https://fake.midtrans.com/snap/' . $payload->orderId,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getStatus(string $orderId): MidtransStatusResponse
    {
        if ($this->shouldFail) {
            throw new MidtransStatusUnavailableException($orderId);
        }

        if ($this->statusResponse !== null) {
            return $this->statusResponse;
        }

        // Return a default pending status if nothing configured
        return new MidtransStatusResponse(
            transactionStatus: 'pending',
            fraudStatus: null,
            grossAmount: '50000.00',
            orderId: $orderId,
            statusCode: '201',
            paymentType: 'bank_transfer',
            settlementTime: null,
            signatureKey: 'fake-signature-key',
        );
    }

    /**
     * @inheritDoc
     */
    public function isConfigured(): bool
    {
        return true;
    }
}
