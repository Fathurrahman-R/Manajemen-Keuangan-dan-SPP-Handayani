<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class PengeluaranSeeder extends Seeder
{
    public function run(): void
    {
        $pengeluaranData = [
            ['uraian' => 'Pembelian ATK', 'jumlah' => 250000],
            ['uraian' => 'Bayar listrik bulan Januari', 'jumlah' => 850000],
            ['uraian' => 'Bayar listrik bulan Februari', 'jumlah' => 780000],
            ['uraian' => 'Pembelian toner printer', 'jumlah' => 150000],
            ['uraian' => 'Biaya kebersihan', 'jumlah' => 500000],
            ['uraian' => 'Perbaikan AC ruang guru', 'jumlah' => 1200000],
            ['uraian' => 'Pembelian air galon', 'jumlah' => 120000],
            ['uraian' => 'Transport rapat dinas', 'jumlah' => 300000],
            ['uraian' => 'Bayar internet bulan Maret', 'jumlah' => 450000],
            ['uraian' => 'Pembelian bahan praktek', 'jumlah' => 680000],
        ];

        foreach (Branch::all() as $branch) {
            $totalPemasukan = Pembayaran::where('branch_id', $branch->id)->sum('jumlah');

            $tahunAjarans = TahunAjaran::where('branch_id', $branch->id)->get();
            $aktiveTahunAjaran = $tahunAjarans->firstWhere('status', 'Aktif');

            if (! $aktiveTahunAjaran) {
                continue;
            }

            $totalPengeluaran = 0;
            foreach ($pengeluaranData as $data) {
                if ($totalPengeluaran + $data['jumlah'] > ($totalPemasukan * 0.7)) {
                    break;
                }
                Pengeluaran::create([
                    'tanggal' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                    'uraian' => $data['uraian'],
                    'jumlah' => $data['jumlah'],
                    'tahun_ajaran_id' => $aktiveTahunAjaran->id,
                    'branch_id' => $branch->id,
                ]);
                $totalPengeluaran += $data['jumlah'];
            }
        }
    }
}
