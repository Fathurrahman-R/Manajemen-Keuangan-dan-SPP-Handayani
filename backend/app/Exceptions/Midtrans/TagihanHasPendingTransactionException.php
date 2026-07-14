<?php

namespace App\Exceptions\Midtrans;

class TagihanHasPendingTransactionException extends MidtransException
{
    public string $errorCode = 'TAGIHAN_HAS_PENDING_TRANSACTION';

    public int $httpStatus = 409;

    /**
     * @param  array{order_id: string, snap_token: string, redirect_url: string, amount_paid: int, fee_amount: int, gross_amount: int}  $pendingData
     */
    public function __construct(public readonly array $pendingData)
    {
        parent::__construct('Tagihan memiliki transaksi pending yang masih aktif.');
    }
}
