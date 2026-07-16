<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MidtransTransaction extends Model
{
        protected $table = 'midtrans_transactions';

    protected $fillable = [
        'order_id',
        'kode_tagihan',
        'batch_items',
        'nis',
        'amount_paid',
        'fee_amount',
        'gross_amount',
        'currency',
        'status',
        'payment_type',
        'snap_token',
        'snap_redirect_url',
        'expired_at',
        'paid_at',
        'initiator_user_id',
        'branch_id',
        'last_raw_response',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'integer',
            'fee_amount' => 'integer',
            'gross_amount' => 'integer',
            'batch_items' => 'array',
            'expired_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_raw_response' => 'array',
        ];
    }

    /**
     * Whether this transaction settles multiple Tagihan in one Snap payment.
     */
    public function isBatch(): bool
    {
        return is_array($this->batch_items) && count($this->batch_items) > 1;
    }

    /**
     * Scope: pending transactions that have not yet expired.
     */
    public function scopePendingInFlight($query)
    {
        return $query->where('status', 'pending')
            ->where('expired_at', '>', now());
    }

    // ──────────────────────────────────────────────
    // Relations
    // ──────────────────────────────────────────────

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'kode_tagihan', 'kode_tagihan');
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'midtrans_order_id', 'order_id');
    }

    public function logs()
    {
        return $this->hasMany(MidtransTransactionLog::class, 'order_id', 'order_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_user_id');
    }
}
