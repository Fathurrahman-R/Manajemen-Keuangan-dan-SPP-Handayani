<?php

namespace App\Exceptions\Midtrans;

class AmountExceedsSisaException extends MidtransException
{
    public string $errorCode = 'AMOUNT_EXCEEDS_SISA';

    public int $httpStatus = 422;

    public function __construct(int $amountPaid, int $sisaTagihan)
    {
        parent::__construct('Nominal Rp '.number_format($amountPaid, 0, ',', '.').' melebihi sisa tagihan Rp '.number_format($sisaTagihan, 0, ',', '.').'.');
    }
}
