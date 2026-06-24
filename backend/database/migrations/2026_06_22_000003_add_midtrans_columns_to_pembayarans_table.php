<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tambah kolom `midtrans_order_id` untuk traceability ke MidtransTransaction
     * dan index `metode` untuk filtering. Enum `metode` itu sendiri sudah final
     * di migration create_pembayarans_table (`offline` / `online_midtrans`).
     *
     * Requirements: 7.2, 12.1, 6.7
     */
    public function up(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->string('midtrans_order_id', 64)->nullable()->unique()->after('pembayar');
            $table->index('metode', 'idx_pembayarans_metode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropIndex('idx_pembayarans_metode');
            $table->dropColumn('midtrans_order_id');
        });
    }
};
