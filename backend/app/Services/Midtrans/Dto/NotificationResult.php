<?php

namespace App\Services\Midtrans\Dto;

final readonly class NotificationResult
{
    private function __construct(
        public bool $success,
        public int $httpStatus,
        public ?string $errorCode = null,
    ) {}

    /**
     * Create a successful result (HTTP 200).
     */
    public static function ok(): self
    {
        return new self(success: true, httpStatus: 200);
    }

    /**
     * Create a rejected result with an error code (HTTP 4xx).
     */
    public static function rejected(int $httpStatus, string $errorCode): self
    {
        return new self(success: false, httpStatus: $httpStatus, errorCode: $errorCode);
    }
}
