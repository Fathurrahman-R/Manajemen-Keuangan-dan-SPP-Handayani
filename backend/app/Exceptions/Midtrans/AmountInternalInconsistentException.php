<?php

namespace App\Exceptions\Midtrans;

class AmountInternalInconsistentException extends MidtransException
{
    public string $errorCode = 'AMOUNT_INTERNAL_INCONSISTENT';
    public int $httpStatus = 422;

    public function __construct(int $amountPaid, int $feeAmount, int $grossAmount)
    {
        parent::__construct("Inkonsistensi internal: amount_paid({$amountPaid}) + fee_amount({$feeAmount}) != gross_amount({$grossAmount}).");
    }
}
