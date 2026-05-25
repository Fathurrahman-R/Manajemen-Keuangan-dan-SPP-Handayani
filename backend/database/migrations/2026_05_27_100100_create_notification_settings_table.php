<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained('branches')->cascadeOnDelete()->cascadeOnUpdate();
            $table->boolean('tagihan_baru_enabled')->default(true);
            $table->boolean('reminder_enabled')->default(true);
            $table->boolean('kwitansi_enabled')->default(true);
            $table->boolean('overdue_enabled')->default(true);
            $table->json('reminder_days_before')->nullable();
            $table->integer('overdue_interval_days')->default(7);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
