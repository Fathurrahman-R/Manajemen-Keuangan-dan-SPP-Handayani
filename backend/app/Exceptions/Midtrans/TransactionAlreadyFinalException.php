<?php

namespace App\Exceptions\Midtrans;

class TransactionAlreadyFinalException extends MidtransException
{
    public string $errorCode = 'TRANSACTION_ALREADY_FINAL';

    public int $httpStatus = 409;

    public function __construct(string $orderId, string $currentStatus)
    {
        parent::__construct("Transaksi '{$orderId}' sudah dalam status final '{$currentStatus}'.");
    }
}
