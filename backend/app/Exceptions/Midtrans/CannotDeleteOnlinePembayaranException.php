<?php

namespace App\Exceptions\Midtrans;

class CannotDeleteOnlinePembayaranException extends MidtransException
{
    public string $errorCode = 'CANNOT_DELETE_ONLINE_PEMBAYARAN';

    public int $httpStatus = 409;

    public function __construct(string $kodePembayaran)
    {
        parent::__construct("Pembayaran online '{$kodePembayaran}' tidak dapat dihapus.");
    }
}
