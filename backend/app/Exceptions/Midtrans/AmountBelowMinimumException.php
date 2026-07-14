<?php

namespace App\Exceptions\Midtrans;

class AmountBelowMinimumException extends MidtransException
{
    public string $errorCode = 'AMOUNT_BELOW_MINIMUM';

    public int $httpStatus = 422;

    public function __construct(int $amountPaid, int $minimum)
    {
        parent::__construct('Nominal Rp '.number_format($amountPaid, 0, ',', '.').' di bawah minimum Rp '.number_format($minimum, 0, ',', '.').'.');
    }
}
