<?php

namespace App\Exceptions\Midtrans;

class InvalidSignatureException extends MidtransException
{
    public string $errorCode = 'INVALID_SIGNATURE';

    public int $httpStatus = 403;

    public function __construct()
    {
        parent::__construct('Signature key tidak valid.');
    }
}
