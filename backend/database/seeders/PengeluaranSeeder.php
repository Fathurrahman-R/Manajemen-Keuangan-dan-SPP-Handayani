<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Pengeluaran;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Database\Seeders\Support\TarifDemo;
use Illuminate\Database\Seeder;

/**
 * Pengeluaran operasional bulanan sepanjang dua tahun ajaran, supaya Laporan
 * Kas Harian dan Rekap Bulanan punya sisi pengeluaran yang berjalan seiring
 * penerimaan — bukan hanya sepuluh baris acak dalam tiga bulan terakhir
 * seperti versi sebelumnya.
 *
 * Gaji dan tagihan utilitas dibayar tiap bulan pada tanggal yang tetap, persis
 * seperti operasional sekolah sungguhan. Nominalnya digoyang beberapa persen
 * tiap bulan supaya tidak terbaca sebagai satu angka yang disalin berulang —
 * tagihan listrik yang sama persis dua belas bulan berturut-turut adalah
 * penanda data buatan yang paling mudah dikenali.
 */
class PengeluaranSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Branch::all() as $branch) {
            $tahunAjarans = TahunAjaran::where('branch_id', $branch->id)
                ->whereIn('nama', ['2025/2026', '2026/2027'])
                ->get();

            // Belanja operasional mengikuti ukuran cabang. Tanpa penyesuaian
            // ini, cabang terkecil membelanjakan angka cabang terbesar dan
            // laporan kasnya selalu merugi — bukan karena datanya menarik,
            // melainkan karena salah skala.
            $skala = $this->skalaCabang($branch->location);

            foreach ($tahunAjarans as $tahunAjaran) {
                $this->buatPengeluaranBulanan($branch->id, $tahunAjaran, $skala);
            }
        }
    }

    /**
     * Rasio jumlah siswa cabang ini terhadap cabang terbesar, yang menjadi
     * acuan nominal di TarifDemo::PENGELUARAN_RUTIN.
     */
    private function skalaCabang(string $lokasi): float
    {
        $jumlah = fn (array $per) => array_sum($per);

        $terbesar = max(array_map($jumlah, TarifDemo::JUMLAH_SISWA));
        $cabangIni = $jumlah(TarifDemo::JUMLAH_SISWA[$lokasi] ?? TarifDemo::JUMLAH_SISWA['Darma Putra']);

        return $terbesar > 0 ? $cabangIni / $terbesar : 1.0;
    }

    private function buatPengeluaranBulanan(int $branchId, TahunAjaran $tahunAjaran, float $skala): void
    {
        $mulai = Carbon::parse($tahunAjaran->tanggal_mulai)->startOfMonth();
        $selesai = Carbon::parse($tahunAjaran->tanggal_selesai)->startOfMonth();
        $hariIni = Carbon::today()->startOfMonth();

        if ($selesai->greaterThan($hariIni)) {
            $selesai = $hariIni;
        }

        $baris = [];

        for ($bulan = $mulai->copy(); $bulan->lessThanOrEqualTo($selesai); $bulan->addMonth()) {
            foreach (TarifDemo::PENGELUARAN_RUTIN as $item) {
                $tanggal = $bulan->copy()->day(fake()->numberBetween(1, 5));

                if ($tanggal->isFuture()) {
                    continue;
                }

                $baris[] = [
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'uraian' => $item['uraian'].' '.$bulan->translatedFormat('F Y'),
                    'jumlah' => $this->goyang((int) round($item['jumlah'] * $skala)),
                    'branch_id' => $branchId,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'pengeluaran_request_id' => null,
                    'created_at' => $tanggal,
                    'updated_at' => $tanggal,
                ];
            }
        }

        foreach (array_chunk($baris, 500) as $potongan) {
            Pengeluaran::insert($potongan);
        }
    }

    /**
     * Variasi +/- 12% dibulatkan ke ribuan terdekat.
     */
    private function goyang(int $jumlah): float
    {
        $hasil = $jumlah * fake()->randomFloat(3, 0.88, 1.12);

        return round($hasil / 1_000) * 1_000;
    }
}
