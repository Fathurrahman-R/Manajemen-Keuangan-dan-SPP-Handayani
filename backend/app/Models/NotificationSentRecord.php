<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class NotificationSentRecord extends Model
{
    protected $table = 'notification_sent_records';

    protected $fillable = [
        'tagihan_kode',
        'notification_type',
        'sent_date',
    ];

    protected $casts = [
        'sent_date' => 'date',
    ];

    /**
     * Check if a notification has already been sent for a tagihan on a given date.
     * Defaults to today if no date is provided.
     */
    public static function alreadySent(string $tagihanKode, string $notificationType, ?string $date = null): bool
    {
        $date = $date ?? Carbon::today()->toDateString();

        return static::where('tagihan_kode', $tagihanKode)
            ->where('notification_type', $notificationType)
            ->where('sent_date', $date)
            ->exists();
    }
}
