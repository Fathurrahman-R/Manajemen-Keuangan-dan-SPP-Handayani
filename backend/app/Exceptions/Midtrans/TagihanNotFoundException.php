<?php

namespace App\Exceptions\Midtrans;

class TagihanNotFoundException extends MidtransException
{
    public string $errorCode = 'TAGIHAN_NOT_FOUND';

    public int $httpStatus = 404;

    public function __construct(string $kodeTagihan)
    {
        parent::__construct("Tagihan '{$kodeTagihan}' tidak ditemukan.");
    }
}
