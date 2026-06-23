<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a JSON column that captures the per-tagihan breakdown when a
     * MidtransTransaction settles a *batch* of bills in one Snap session.
     *
     * Each entry is `{ "kode_tagihan": string, "amount": int }`. For
     * single-tagihan transactions the column stays NULL.
     *
     * The single `kode_tagihan` FK is retained — for batches it points to the
     * "primary" tagihan (first in the batch) so existing relations keep
     * working. The webhook flow uses `batch_items` to materialise one
     * Pembayaran per included tagihan.
     */
    public function up(): void
    {
        Schema::table('midtrans_transactions', function (Blueprint $table): void {
            $table->json('batch_items')->nullable()->after('kode_tagihan');
        });
    }

    public function down(): void
    {
        Schema::table('midtrans_transactions', function (Blueprint $table): void {
            $table->dropColumn('batch_items');
        });
    }
};
