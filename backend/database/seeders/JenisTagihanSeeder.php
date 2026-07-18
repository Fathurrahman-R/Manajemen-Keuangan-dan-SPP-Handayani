<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\JenisTagihan;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class JenisTagihanSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();
        $jenisTagihanData = [
            ['nama' => 'SPP Bulan Ini',     'jatuh_tempo' => $today->copy()->addDays(3)->format('Y-m-d'),  'jumlah' => 150000],
            ['nama' => 'SPP Bulan Depan',   'jatuh_tempo' => $today->copy()->addDays(33)->format('Y-m-d'), 'jumlah' => 150000],
            ['nama' => 'SPP 2 Bulan Lagi',  'jatuh_tempo' => $today->copy()->addDays(63)->format('Y-m-d'), 'jumlah' => 150000],
            ['nama' => 'SPP 3 Bulan Lagi',  'jatuh_tempo' => $today->copy()->addDays(93)->format('Y-m-d'), 'jumlah' => 150000],
            ['nama' => 'SPP 4 Bulan Lagi',  'jatuh_tempo' => $today->copy()->addDays(123)->format('Y-m-d'), 'jumlah' => 150000],
            ['nama' => 'SPP 5 Bulan Lagi',  'jatuh_tempo' => $today->copy()->addDays(153)->format('Y-m-d'), 'jumlah' => 150000],
            ['nama' => 'Pendaftaran Ulang', 'jatuh_tempo' => $today->copy()->subDays(15)->format('Y-m-d'), 'jumlah' => 500000],
            ['nama' => 'Seragam',           'jatuh_tempo' => $today->copy()->subDays(30)->format('Y-m-d'), 'jumlah' => 350000],
            ['nama' => 'Buku Paket',        'jatuh_tempo' => $today->copy()->subDays(45)->format('Y-m-d'), 'jumlah' => 200000],
        ];

        foreach (Branch::all() as $branch) {
            $tahunAjarans = TahunAjaran::where('branch_id', $branch->id)->get();
            $aktiveTahunAjaran = $tahunAjarans->firstWhere('status', 'Aktif');

            if (! $aktiveTahunAjaran) {
                continue;
            }

            foreach ($jenisTagihanData as $data) {
                JenisTagihan::firstOrCreate([
                    'nama' => $data['nama'],
                    'branch_id' => $branch->id,
                    'tahun_ajaran_id' => $aktiveTahunAjaran->id,
                ], [
                    'jatuh_tempo' => $data['jatuh_tempo'],
                    'jumlah' => $data['jumlah'],
                ]);
            }
        }
    }
}
