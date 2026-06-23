<?php

namespace App\Exceptions\Midtrans;

use RuntimeException;

abstract class MidtransException extends RuntimeException
{
    public string $errorCode;
    public int $httpStatus;

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? $this->errorCode);
    }
}
