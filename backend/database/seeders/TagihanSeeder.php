<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\JenisTagihan;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class TagihanSeeder extends Seeder
{
    private int $tagihanCounter = 1;

    public function run(): void
    {
        foreach (Branch::all() as $branch) {
            $tahunAjarans = TahunAjaran::where('branch_id', $branch->id)->get();
            $aktiveTahunAjaran = $tahunAjarans->firstWhere('status', 'Aktif');

            if (! $aktiveTahunAjaran) {
                continue;
            }

            $jenisTagihans = JenisTagihan::where('branch_id', $branch->id)
                ->where('tahun_ajaran_id', $aktiveTahunAjaran->id)
                ->get();

            if ($jenisTagihans->isEmpty()) {
                continue;
            }

            $students = Siswa::where('branch_id', $branch->id)
                ->where('jenjang', 'MI')
                ->limit(20)
                ->get();

            foreach ($students as $siswa) {
                $selectedJenis = fake()->randomElements($jenisTagihans, rand(2, 4));

                foreach ($selectedJenis as $jenis) {
                    $kodeTagihan = 'TAG-'.now()->format('ym').'-'.str_pad($this->tagihanCounter++, 4, '0', STR_PAD_LEFT);

                    $status = fake()->randomElement(['Lunas', 'Belum Lunas', 'Belum Dibayar', 'Belum Dibayar']);
                    $tmp = 0;

                    if ($status === 'Lunas') {
                        $tmp = $jenis->jumlah;
                    } elseif ($status === 'Belum Lunas') {
                        $tmp = round($jenis->jumlah * fake()->randomFloat(2, 0.2, 0.8), -3);
                    }

                    Tagihan::create([
                        'kode_tagihan' => $kodeTagihan,
                        'jenis_tagihan_id' => $jenis->id,
                        'nis' => $siswa->nis,
                        'tmp' => $tmp,
                        'status' => $status,
                        'branch_id' => $branch->id,
                        'tahun_ajaran_id' => $aktiveTahunAjaran->id,
                    ]);
                }
            }
        }
    }
}
