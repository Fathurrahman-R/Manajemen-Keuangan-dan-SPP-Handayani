<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\JenisTagihan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\Wali;
use App\Services\DashboardService;
use Tests\TestCase;

/**
 * Dashboard KPI di-cache 5 menit. Setiap method dashboard punya DUA cache key:
 * satu untuk periode tertentu dan satu bersuffix "-all" untuk mode
 * "Semua Periode" (mode default widget saat belum memilih tahun ajaran).
 * Kalau invalidasi cuma menyentuh key periode, angka di dashboard default
 * (termasuk persentase pelunasan) tetap menampilkan nilai lama sesudah
 * pembayaran baru — tidak cocok dengan penghitungan manual.
 */
class DashboardCacheInvalidationTest extends TestCase
{
    /** @return array{0: int, 1: Tagihan, 2: TahunAjaran} */
    private function seedTagihanBelumDibayar(int $jumlahTagihan = 100000): array
    {
        $branch = Branch::factory()->create();

        $tahunAjaran = TahunAjaran::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'Aktif',
        ]);

        $jenisTagihan = JenisTagihan::factory()->create([
            'branch_id' => $branch->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'jumlah' => $jumlahTagihan,
        ]);

        $wali = Wali::factory()->create();

        $siswa = Siswa::factory()
            ->for($wali, 'wali')
            ->for(Kelas::factory()->create(['branch_id' => $branch->id]), 'kelas')
            ->for(Kategori::factory()->create(['branch_id' => $branch->id]), 'kategori')
            ->create([
                'branch_id' => $branch->id,
                'status' => 'Aktif',
                'ayah_id' => null,
                'ibu_id' => null,
            ]);

        $tagihan = Tagihan::factory()->create([
            'jenis_tagihan_id' => $jenisTagihan->id,
            'nis' => $siswa->nis,
            'branch_id' => $branch->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'tmp' => 0,
            'status' => 'Belum Dibayar',
        ]);

        return [$branch->id, $tagihan, $tahunAjaran];
    }

    public function test_summary_semua_periode_ikut_ter_invalidate_setelah_pembayaran_baru(): void
    {
        [$branchId, $tagihan] = $this->seedTagihanBelumDibayar();

        $service = app(DashboardService::class);

        $sebelum = $service->getSummary($branchId, null, allPeriods: true);
        self::assertSame(0.0, (float) $sebelum['total_terbayar']);
        self::assertSame(0.0, (float) $sebelum['persentase_pelunasan']);

        Pembayaran::factory()->create([
            'kode_tagihan' => $tagihan->kode_tagihan,
            'jumlah' => 50000,
            'metode' => 'offline',
            'branch_id' => $branchId,
        ]);

        $sesudah = $service->getSummary($branchId, null, allPeriods: true);

        self::assertSame(50000.0, (float) $sesudah['total_terbayar']);
        self::assertSame(50.0, (float) $sesudah['persentase_pelunasan']);
    }

    public function test_chart_semua_periode_ikut_ter_invalidate_setelah_pembayaran_baru(): void
    {
        [$branchId, $tagihan] = $this->seedTagihanBelumDibayar();

        $service = app(DashboardService::class);

        $sebelum = $service->getChartStatusTagihan($branchId, null, allPeriods: true);
        self::assertSame(1, collect($sebelum)->firstWhere('status', 'Belum Dibayar')['jumlah']);

        Pembayaran::factory()->create([
            'kode_tagihan' => $tagihan->kode_tagihan,
            'jumlah' => 100000,
            'metode' => 'offline',
            'branch_id' => $branchId,
        ]);
        $tagihan->update(['tmp' => 100000, 'status' => 'Lunas']);

        $sesudah = $service->getChartStatusTagihan($branchId, null, allPeriods: true);

        self::assertSame(1, collect($sesudah)->firstWhere('status', 'Lunas')['jumlah']);
        self::assertSame(0, collect($sesudah)->firstWhere('status', 'Belum Dibayar')['jumlah']);
    }

    public function test_kas_summary_dan_all_time_summary_ikut_ter_invalidate(): void
    {
        [$branchId, $tagihan, $tahunAjaran] = $this->seedTagihanBelumDibayar();

        $service = app(DashboardService::class);

        $kasSebelum = $service->getKasSummary($branchId, $tahunAjaran->id);
        self::assertSame(0, $kasSebelum['total_pemasukan']);

        $allTimeSebelum = $service->getAllTimeSummary($branchId);
        self::assertSame(0, $allTimeSebelum['total_pemasukan']);

        Pembayaran::factory()->create([
            'kode_tagihan' => $tagihan->kode_tagihan,
            'jumlah' => 75000,
            'metode' => 'offline',
            'branch_id' => $branchId,
        ]);

        self::assertSame(75000, $service->getKasSummary($branchId, $tahunAjaran->id)['total_pemasukan']);
        self::assertSame(75000, $service->getAllTimeSummary($branchId)['total_pemasukan']);
    }

    public function test_persentase_pelunasan_ikut_berubah_setelah_nominal_jenis_tagihan_diubah(): void
    {
        [$branchId, $tagihan] = $this->seedTagihanBelumDibayar(jumlahTagihan: 100000);

        $service = app(DashboardService::class);

        Pembayaran::factory()->create([
            'kode_tagihan' => $tagihan->kode_tagihan,
            'jumlah' => 50000,
            'metode' => 'offline',
            'branch_id' => $branchId,
        ]);

        self::assertSame(50.0, (float) $service->getSummary($branchId, null, allPeriods: true)['persentase_pelunasan']);

        // Nominal tagihan dikoreksi jadi 200rb: pembayaran 50rb kini cuma 25%.
        JenisTagihan::find($tagihan->jenis_tagihan_id)->update(['jumlah' => 200000]);

        self::assertSame(25.0, (float) $service->getSummary($branchId, null, allPeriods: true)['persentase_pelunasan']);
    }

    public function test_kas_summary_ikut_ter_invalidate_setelah_pengeluaran_baru(): void
    {
        [$branchId, , $tahunAjaran] = $this->seedTagihanBelumDibayar();

        $service = app(DashboardService::class);

        self::assertSame(0, $service->getKasSummary($branchId, $tahunAjaran->id)['total_pengeluaran']);

        Pengeluaran::factory()->create([
            'branch_id' => $branchId,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'jumlah' => 25000,
        ]);

        self::assertSame(25000, $service->getKasSummary($branchId, $tahunAjaran->id)['total_pengeluaran']);
    }
}
