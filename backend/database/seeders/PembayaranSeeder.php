<?php

namespace Database\Seeders;

use App\Models\Pembayaran;
use Illuminate\Database\Seeder;

class PembayaranSeeder extends Seeder
{
    public function run(): void
    {
        Pembayaran::create([
            'kode_pembayaran' => 'PAY-INIT-0001',
            'kode_tagihan' => 'TAG-INIT-0001',
            'tanggal' => now()->format('Y-m-d'),
            'metode' => 'Tunai',
            'jumlah' => 100000,
            'pembayar' => 'Wali 1'
        ]);
    }
}

