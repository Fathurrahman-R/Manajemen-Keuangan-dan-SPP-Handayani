<?php

namespace App\Exceptions\Midtrans;

class TagihanForbiddenException extends MidtransException
{
    public string $errorCode = 'TAGIHAN_FORBIDDEN';
    public int $httpStatus = 403;

    public function __construct(string $kodeTagihan)
    {
        parent::__construct("Anda tidak memiliki akses ke tagihan '{$kodeTagihan}'.");
    }
}
