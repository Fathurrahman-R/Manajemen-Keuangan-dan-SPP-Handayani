<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel notifikasi database Filament untuk frontend-v2.
     *
     * Frontend-v2 panel Filament memakai `databaseNotifications()`. Karena
     * tabel `notifications` di backend sudah dipakai untuk skema in-app
     * notification kustom (kolom `user_id`, `title`, `message`, `is_read`,
     * dst), Filament dipindah ke tabel terpisah `filament_notifications`
     * yang mengikuti schema bawaan Laravel database notifications.
     *
     * Model `App\Models\FilamentDatabaseNotification` di frontend-v2
     * memetakan `protected $table = 'filament_notifications'`.
     */
    public function up(): void
    {
        Schema::create('filament_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filament_notifications');
    }
};
