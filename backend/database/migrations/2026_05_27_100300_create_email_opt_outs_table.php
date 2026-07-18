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
        Schema::create('email_opt_outs', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->enum('notification_type', ['tagihan_baru', 'reminder', 'kwitansi', 'overdue', 'all']);
            $table->string('token')->unique();
            $table->timestamps();

            $table->unique(['email', 'notification_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_opt_outs');
    }
};
