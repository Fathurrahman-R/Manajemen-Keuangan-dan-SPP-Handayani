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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->char('kode_pembayaran',30)->primary()->index();
            $table->char('kode_tagihan',30)->nullable(false)->index();
            $table->date('tanggal')->nullable(false)->default(now())->index();
            $table->enum('metode',['Tunai','Non-Tunai'])->default('Tunai')->nullable(false);
            $table->decimal('jumlah',12,2)->nullable()->default(0);
            $table->string('pembayar',100)->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
