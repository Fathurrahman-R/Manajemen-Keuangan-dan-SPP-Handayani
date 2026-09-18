<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\JenisTagihan;
use App\Models\TahunAjaran;
use Database\Seeders\Support\SkemaTagihan;
use Database\Seeders\Support\TarifDemo;
use Illuminate\Database\Seeder;

/**
 * Jenis tagihan bernama bulan yang sebenarnya ("SPP MI Umum Juli 2026"), bukan
 * penamaan relatif seperti "SPP 2 Bulan Lagi" yang dipakai versi sebelumnya —
 * penamaan relatif langsung terbaca sebagai data uji, dan tidak bisa dipakai
 * menjelaskan laporan bulanan karena namanya berubah makna tiap hari.
 *
 * SPP dipecah per jenjang DAN per kategori keringanan karena tabel
 * `jenis_tagihans` tidak punya kolom untuk keduanya, sementara tarifnya
 * berbeda-beda: MI Umum 100rb sedangkan MI Yatim 50rb. Memecah per kombinasi
 * adalah satu-satunya cara menyatakan perbedaan itu di skema yang ada.
 *
 * Kategori Yatim Piatu dibebaskan sepenuhnya, jadi jenis tagihan SPP untuknya
 * memang tidak pernah dibuat.
 */
class JenisTagihanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Branch::all() as $branch) {
            $tahunAjarans = TahunAjaran::where('branch_id', $branch->id)
                ->whereIn('nama', ['2025/2026', '2026/2027'])
                ->get();

            foreach ($tahunAjarans as $tahunAjaran) {
                $this->buatSppBulanan($branch->id, $tahunAjaran);
                $this->buatTagihanTahunan($branch->id, $tahunAjaran);
            }
        }
    }

    private function buatSppBulanan(int $branchId, TahunAjaran $tahunAjaran): void
    {
        foreach (SkemaTagihan::bulanTerbit($tahunAjaran) as $bulan) {
            foreach (TarifDemo::SPP_BULANAN as $jenjang => $tarifPerKategori) {
                foreach ($tarifPerKategori as $kategori => $jumlah) {
                    JenisTagihan::firstOrCreate([
                        'nama' => SkemaTagihan::namaSpp($jenjang, $kategori, $bulan),
                        'branch_id' => $branchId,
                        'tahun_ajaran_id' => $tahunAjaran->id,
                    ], [
                        'jatuh_tempo' => SkemaTagihan::jatuhTempoSpp($bulan)->format('Y-m-d'),
                        'jumlah' => $jumlah,
                    ]);
                }
            }
        }
    }

    /**
     * Biaya sekali setahun tidak dibedakan per kategori — sekolah hanya
     * menerapkan keringanan pada SPP.
     */
    private function buatTagihanTahunan(int $branchId, TahunAjaran $tahunAjaran): void
    {
        foreach (SkemaTagihan::tagihanTahunan($tahunAjaran) as $item) {
            foreach ($item['tarif'] as $jenjang => $jumlah) {
                JenisTagihan::firstOrCreate([
                    'nama' => SkemaTagihan::namaTahunan($item['label'], $jenjang, $tahunAjaran->nama),
                    'branch_id' => $branchId,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                ], [
                    'jatuh_tempo' => $item['tempo']->format('Y-m-d'),
                    'jumlah' => $jumlah,
                ]);
            }
        }
    }
}
