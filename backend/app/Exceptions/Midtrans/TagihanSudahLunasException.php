<?php

namespace App\Exceptions\Midtrans;

class TagihanSudahLunasException extends MidtransException
{
    public string $errorCode = 'TAGIHAN_SUDAH_LUNAS';

    public int $httpStatus = 422;

    public function __construct(string $kodeTagihan)
    {
        parent::__construct("Tagihan '{$kodeTagihan}' sudah lunas.");
    }
}
