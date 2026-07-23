<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `filament_notifications` was provisioned for Filament's in-app database
     * notifications, but the panel never called `databaseNotifications()` —
     * nothing ever wrote to this table. Notifications are handled via email
     * (see `notification_logs`), so this dead scaffolding is removed.
     */
    public function up(): void
    {
        Schema::dropIfExists('filament_notifications');
    }

    public function down(): void
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
};
