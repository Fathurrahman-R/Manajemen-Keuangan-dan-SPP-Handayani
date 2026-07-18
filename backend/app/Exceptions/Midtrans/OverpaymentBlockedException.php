<?php

namespace App\Exceptions\Midtrans;

class OverpaymentBlockedException extends MidtransException
{
    public string $errorCode = 'OVERPAYMENT_BLOCKED';

    public int $httpStatus = 409;

    public function __construct(string $orderId, int $amountPaid, int $sisaTagihan)
    {
        parent::__construct("Pembayaran order '{$orderId}' sebesar Rp ".number_format($amountPaid, 0, ',', '.').' melebihi sisa tagihan Rp '.number_format($sisaTagihan, 0, ',', '.').'.');
    }
}
