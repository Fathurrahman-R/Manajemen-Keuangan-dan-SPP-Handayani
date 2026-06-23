<?php

namespace App\Exceptions\Midtrans;

class OrderNotFoundException extends MidtransException
{
    public string $errorCode = 'ORDER_NOT_FOUND';
    public int $httpStatus = 404;

    public function __construct(string $orderId)
    {
        parent::__construct("Order '{$orderId}' tidak ditemukan.");
    }
}
