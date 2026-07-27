<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $table = 'notification_logs';

    protected $fillable = [
        'branch_id',
        'recipient_email',
        'notification_type',
        'tagihan_kode',
        'pengeluaran_request_id',
        'workflow_event',
        'status',
        'reason',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
