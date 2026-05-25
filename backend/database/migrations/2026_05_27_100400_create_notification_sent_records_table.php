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
        Schema::create('notification_sent_records', function (Blueprint $table) {
            $table->id();
            $table->string('tagihan_kode');
            $table->enum('notification_type', ['tagihan_baru', 'reminder', 'kwitansi', 'overdue']);
            $table->date('sent_date');
            $table->timestamps();

            $table->unique(['tagihan_kode', 'notification_type', 'sent_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_sent_records');
    }
};
