<?php

namespace Database\Seeders;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Database\Seeder;

class PembayaranSeeder extends Seeder
{
    private int $pembayaranCounter = 1;

    public function run(): void
    {
        $tagihans = Tagihan::where('tmp', '>', 0)->with('siswa')->get();

        foreach ($tagihans as $tagihan) {
            $kodePembayaran = 'PAY-'.now()->format('ym').'-'.str_pad($this->pembayaranCounter++, 4, '0', STR_PAD_LEFT);
            Pembayaran::create([
                'kode_pembayaran' => $kodePembayaran,
                'kode_tagihan' => $tagihan->kode_tagihan,
                'tanggal' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                'metode' => 'offline',
                'jumlah' => $tagihan->tmp,
                'pembayar' => $tagihan->siswa ? $tagihan->siswa->nama : fake()->name(),
                'branch_id' => $tagihan->branch_id,
            ]);
        }
    }
}
