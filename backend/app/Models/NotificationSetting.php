<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
        protected $table = 'notification_settings';

    protected $fillable = [
        'branch_id',
        'tagihan_baru_enabled',
        'reminder_enabled',
        'kwitansi_enabled',
        'overdue_enabled',
        'reminder_days_before',
        'overdue_interval_days',
    ];

    protected $casts = [
        'tagihan_baru_enabled' => 'boolean',
        'reminder_enabled' => 'boolean',
        'kwitansi_enabled' => 'boolean',
        'overdue_enabled' => 'boolean',
        'reminder_days_before' => 'array',
        'overdue_interval_days' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
