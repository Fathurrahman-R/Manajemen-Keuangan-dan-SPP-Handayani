<?php

namespace Database\Seeders;

use App\Models\JenisTagihan;
use Illuminate\Database\Seeder;

class JenisTagihanSeeder extends Seeder
{
    public function run(): void
    {
        JenisTagihan::create([
            'id' => 1,
            'nama' => 'SPP',
            'jatuh_tempo' => now()->addMonth()->format('Y-m-d'),
            'jumlah' => 100000,
        ]);
    }
}

