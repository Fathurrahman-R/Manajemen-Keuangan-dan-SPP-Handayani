<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MidtransTransactionLog extends Model
{
    protected $table = 'midtrans_transaction_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'direction',
        'http_status',
        'raw_payload',
        'remote_ip',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(MidtransTransaction::class, 'order_id', 'order_id');
    }
}
