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
        Schema::create('midtrans_transactions', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PK

            $table->string('order_id', 64)->unique();
            $table->string('kode_tagihan', 64)->nullable(false);
            $table->string('nis', 32)->nullable(false)->index();
            $table->unsignedBigInteger('amount_paid')->nullable(false);
            $table->unsignedBigInteger('fee_amount')->nullable(false);
            $table->unsignedBigInteger('gross_amount')->nullable(false);
            $table->char('currency', 3)->nullable(false)->default('IDR');
            $table->enum('status', [
                'pending',
                'settlement',
                'capture',
                'deny',
                'cancel',
                'expire',
                'failure',
                'refund',
                'partial_refund',
            ])->nullable(false)->default('pending')->index();
            $table->string('payment_type', 64)->nullable();
            $table->string('snap_token', 128)->nullable();
            $table->string('snap_redirect_url', 255)->nullable();
            $table->dateTime('expired_at')->nullable(false)->index();
            $table->dateTime('paid_at')->nullable();
            $table->unsignedBigInteger('initiator_user_id')->nullable();
            $table->integer('branch_id')->nullable();
            $table->json('last_raw_response')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('kode_tagihan')
                ->references('kode_tagihan')
                ->on('tagihans')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('initiator_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Composite indexes
            $table->index(['kode_tagihan', 'status'], 'idx_midtrans_trx_tagihan_status');
            $table->index(['status', 'expired_at'], 'idx_midtrans_trx_status_expired');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('midtrans_transactions');
    }
};
