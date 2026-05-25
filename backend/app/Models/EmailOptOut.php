<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailOptOut extends Model
{
    protected $table = 'email_opt_outs';

    protected $fillable = [
        'email',
        'notification_type',
        'token',
    ];

    /**
     * Check if an email has opted out of a specific notification type.
     * Returns true if the email has opted out of the specific type OR 'all'.
     */
    public static function isOptedOut(string $email, string $notificationType): bool
    {
        return static::where('email', $email)
            ->whereIn('notification_type', [$notificationType, 'all'])
            ->exists();
    }

    /**
     * Generate a signed unsubscribe URL for the given email and notification type.
     */
    public static function generateUnsubscribeUrl(string $email, string $notificationType = 'all'): string
    {
        $optOut = static::firstOrCreate(
            ['email' => $email, 'notification_type' => $notificationType],
            ['token' => Str::random(64)]
        );

        return url("/api/unsubscribe/{$optOut->token}");
    }
}
