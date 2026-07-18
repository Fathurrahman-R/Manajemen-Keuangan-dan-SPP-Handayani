<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom tahun_ajaran_id ke pengeluarans agar pengeluaran dapat
     * difilter per periode ajaran (mengikuti pola tagihans/jenis_tagihans).
     *
     * Backfill otomatis: untuk setiap pengeluaran yang belum punya periode,
     * pakai TahunAjaran yang range tanggalnya mencakup `tanggal` pengeluaran.
     */
    public function up(): void
    {
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->foreignId('tahun_ajaran_id')
                ->nullable()
                ->after('jumlah')
                ->constrained('tahun_ajarans')
                ->nullOnDelete();
            $table->index('tahun_ajaran_id', 'idx_pengeluarans_tahun_ajaran');
        });

        // Backfill: petakan pengeluaran ke tahun ajaran berdasarkan tanggal.
        $tahunAjarans = DB::table('tahun_ajarans')->get();
        foreach ($tahunAjarans as $ta) {
            DB::table('pengeluarans')
                ->whereNull('tahun_ajaran_id')
                ->where('branch_id', $ta->branch_id)
                ->whereBetween('tanggal', [$ta->tanggal_mulai, $ta->tanggal_selesai])
                ->update(['tahun_ajaran_id' => $ta->id]);
        }
    }

    public function down(): void
    {
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->dropIndex('idx_pengeluarans_tahun_ajaran');
            $table->dropConstrainedForeignId('tahun_ajaran_id');
        });
    }
};
