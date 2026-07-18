<?php

namespace App\Exceptions\Midtrans;

class MidtransStatusUnavailableException extends MidtransException
{
    public string $errorCode = 'MIDTRANS_STATUS_UNAVAILABLE';

    public int $httpStatus = 502;

    public function __construct(string $orderId)
    {
        parent::__construct("Gagal mengambil status transaksi '{$orderId}' dari Midtrans.");
    }
}
