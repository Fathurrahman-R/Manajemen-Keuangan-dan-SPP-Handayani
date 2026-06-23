<?php

namespace App\Exceptions\Midtrans;

class MidtransDisabledException extends MidtransException
{
    public string $errorCode = 'NOT_FOUND';
    public int $httpStatus = 404;

    public function __construct()
    {
        parent::__construct('Midtrans payment gateway is disabled.');
    }
}
