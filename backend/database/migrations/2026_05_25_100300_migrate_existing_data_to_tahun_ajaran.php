<?php

use App\Models\Branch;
use App\Models\JenisTagihan;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $branches = Branch::all();

        foreach ($branches as $branch) {
            DB::transaction(function () use ($branch) {
                // Determine academic year from current date
                $now = now();
                $startYear = $now->month >= 7 ? $now->year : $now->year - 1;
                $endYear = $startYear + 1;
                $nama = "{$startYear}/{$endYear}";

                // 1. Create Legacy_Period (or use existing)
                $tahunAjaran = TahunAjaran::firstOrCreate(
                    ['nama' => $nama, 'branch_id' => $branch->id],
                    [
                        'tanggal_mulai' => "{$startYear}-07-01",
                        'tanggal_selesai' => "{$endYear}-06-30",
                        'status' => 'Aktif',
                    ]
                );

                // 2. Assign to existing tagihans with NULL tahun_ajaran_id
                Tagihan::where('branch_id', $branch->id)
                    ->whereNull('tahun_ajaran_id')
                    ->update(['tahun_ajaran_id' => $tahunAjaran->id]);

                // 3. Assign to existing jenis_tagihans with NULL tahun_ajaran_id
                JenisTagihan::where('branch_id', $branch->id)
                    ->whereNull('tahun_ajaran_id')
                    ->update(['tahun_ajaran_id' => $tahunAjaran->id]);

                // 4. Create SiswaKelas for active students with non-null kelas_id
                $siswas = Siswa::where('branch_id', $branch->id)
                    ->where('status', 'Aktif')
                    ->whereNotNull('kelas_id')
                    ->get();

                foreach ($siswas as $siswa) {
                    SiswaKelas::firstOrCreate(
                        [
                            'siswa_id' => $siswa->id,
                            'tahun_ajaran_id' => $tahunAjaran->id,
                        ],
                        [
                            'kelas_id' => $siswa->kelas_id,
                        ]
                    );
                }

                // 5. Log warning for students with null kelas_id
                $skippedSiswas = Siswa::where('branch_id', $branch->id)
                    ->where('status', 'Aktif')
                    ->whereNull('kelas_id')
                    ->get();

                foreach ($skippedSiswas as $siswa) {
                    Log::warning("Migration: Skipped SiswaKelas creation for siswa NIS={$siswa->nis}, branch_id={$branch->id} (kelas_id is null)");
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Remove all SiswaKelas records
        DB::table('siswa_kelas')->truncate();

        // Reset tahun_ajaran_id on tagihans
        DB::table('tagihans')->update(['tahun_ajaran_id' => null]);

        // Reset tahun_ajaran_id on jenis_tagihans
        DB::table('jenis_tagihans')->update(['tahun_ajaran_id' => null]);

        // Remove all TahunAjaran records
        DB::table('tahun_ajarans')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
