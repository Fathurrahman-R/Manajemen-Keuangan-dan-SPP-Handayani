<?php

namespace App\Exceptions\Midtrans;

class MidtransNotConfiguredException extends MidtransException
{
    public string $errorCode = 'MIDTRANS_NOT_CONFIGURED';
    public int $httpStatus = 503;

    public function __construct()
    {
        parent::__construct('Midtrans server_key or client_key is not configured.');
    }
}
