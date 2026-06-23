<?php

namespace App\Exceptions\Midtrans;

class InvalidStatusTransitionException extends MidtransException
{
    public string $errorCode = 'INVALID_STATUS_TRANSITION';
    public int $httpStatus = 409;

    public function __construct(string $currentStatus, string $targetStatus)
    {
        parent::__construct("Transisi status dari '{$currentStatus}' ke '{$targetStatus}' tidak diizinkan.");
    }
}
