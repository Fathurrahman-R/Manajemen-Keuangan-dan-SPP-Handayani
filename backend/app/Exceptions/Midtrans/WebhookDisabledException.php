<?php

namespace App\Exceptions\Midtrans;

class WebhookDisabledException extends MidtransException
{
    public string $errorCode = 'WEBHOOK_DISABLED';
    public int $httpStatus = 503;

    public function __construct()
    {
        parent::__construct('Midtrans webhook is disabled.');
    }
}
