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
        Schema::create('midtrans_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 64)->nullable()->index();
            $table->enum('direction', ['outbound_charge', 'outbound_status', 'inbound_notification']);
            $table->integer('http_status')->nullable();
            $table->longText('raw_payload')->nullable();
            $table->string('remote_ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('midtrans_transaction_logs');
    }
};
