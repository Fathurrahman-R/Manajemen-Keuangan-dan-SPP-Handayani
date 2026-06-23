<?php

namespace App\Exceptions\Midtrans;

/**
 * Thrown when Midtrans Status API returns 404 for an order_id that exists
 * locally. This happens when Snap created a token but the buyer never
 * picked a payment channel — Midtrans does not register the transaction
 * until then.
 */
class TransactionNotYetProcessedException extends MidtransException
{
    public string $errorCode = 'TRANSACTION_NOT_YET_PROCESSED';
    public int $httpStatus = 409;

    public function __construct(string $orderId)
    {
        parent::__construct("Transaksi '{$orderId}' belum diproses Midtrans (siswa belum memilih kanal pembayaran).");
    }
}
