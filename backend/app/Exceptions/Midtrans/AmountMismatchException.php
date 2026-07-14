<?php

namespace App\Exceptions\Midtrans;

class AmountMismatchException extends MidtransException
{
    public string $errorCode = 'AMOUNT_MISMATCH';

    public int $httpStatus = 422;

    public function __construct(string $orderId, int $expected, int $received)
    {
        parent::__construct("Gross amount mismatch untuk order '{$orderId}': expected {$expected}, received {$received}.");
    }
}
