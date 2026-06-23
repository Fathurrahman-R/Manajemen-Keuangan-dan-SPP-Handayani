<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Alter `pembayarans` table to support Midtrans online payments:
     * - Change `metode` enum from ['Tunai','Non-Tunai'] to ['offline','online_midtrans']
     * - Add `midtrans_order_id` for traceability to MidtransTransaction
     * - Add index on `metode` for filtering
     *
     * Requirements: 7.2, 12.1, 6.7
     */
    public function up(): void
    {
        // Step 1: Add midtrans_order_id column
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->string('midtrans_order_id', 64)->nullable()->unique()->after('pembayar');
        });

        // Step 2: Modify metode enum - must use raw SQL for MySQL ENUM modification
        // First, backfill existing 'Tunai' and 'Non-Tunai' values to 'offline'
        DB::statement("ALTER TABLE pembayarans MODIFY COLUMN metode ENUM('Tunai','Non-Tunai','offline','online_midtrans') NOT NULL DEFAULT 'offline'");

        // Backfill: map existing values to 'offline'
        DB::table('pembayarans')
            ->whereIn('metode', ['Tunai', 'Non-Tunai'])
            ->update(['metode' => 'offline']);

        // Now remove old enum values since all rows have been migrated
        DB::statement("ALTER TABLE pembayarans MODIFY COLUMN metode ENUM('offline','online_midtrans') NOT NULL DEFAULT 'offline'");

        // Step 3: Add index on metode
        Schema::table('pembayarans', function (Blueprint $table) {
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
        });

        // Revert metode enum back to original values
        // First, expand enum to include old values
        DB::statement("ALTER TABLE pembayarans MODIFY COLUMN metode ENUM('Tunai','Non-Tunai','offline','online_midtrans') NOT NULL DEFAULT 'Tunai'");

        // Map 'offline' back to 'Tunai' (best effort rollback)
        DB::table('pembayarans')
            ->where('metode', 'offline')
            ->update(['metode' => 'Tunai']);

        // Remove online_midtrans rows' metode is problematic on rollback,
        // set them to 'Non-Tunai' as closest equivalent
        DB::table('pembayarans')
            ->where('metode', 'online_midtrans')
            ->update(['metode' => 'Non-Tunai']);

        // Shrink enum back to original
        DB::statement("ALTER TABLE pembayarans MODIFY COLUMN metode ENUM('Tunai','Non-Tunai') NOT NULL DEFAULT 'Tunai'");

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropColumn('midtrans_order_id');
        });
    }
};
