<?php

namespace App\Exceptions\Midtrans;

class MidtransUnavailableException extends MidtransException
{
    public string $errorCode = 'MIDTRANS_UNAVAILABLE';

    public int $httpStatus = 502;

    public function __construct(?string $reason = null)
    {
        parent::__construct($reason ?? 'Midtrans service is unavailable.');
    }
}
