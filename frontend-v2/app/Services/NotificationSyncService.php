<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSyncService
{
    /**
     * Sync backend notifications to filament_notifications table.
     * Uses the exact JSON format Filament v4 expects from sendToDatabase().
     */
    public static function syncFromApi(array $apiNotifications, int $userId): void
    {
        foreach ($apiNotifications as $n) {
            $id = 'backend-' . $n['id'];

            // Skip if already synced
            $existing = DB::table('filament_notifications')->where('id', $id)->exists();
            if ($existing) {
                continue;
            }

            // Filament v4 database notification format (matches Notification::make()->getDatabaseMessage())
            $data = json_encode([
                'actions'  => [],
                'body'     => $n['message'],
                'color'    => null,
                'duration' => 'persistent',
                'icon'     => 'heroicon-o-bell',
                'iconColor'=> 'info',
                'status'   => 'info',
                'title'    => $n['title'],
                'view'     => 'filament-notifications::notification',
                'viewData' => [],
                'format'   => 'filament',
            ]);

            DB::table('filament_notifications')->insert([
                'id'              => $id,
                'type'            => 'Filament\\Notifications\\DatabaseNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id'   => $userId,
                'data'            => $data,
                'read_at'         => $n['is_read'] ? ($n['created_at'] ?? now()) : null,
                'created_at'      => $n['created_at'] ?? now(),
                'updated_at'      => now(),
            ]);
        }
    }
}
