<?php

namespace Database\Seeders;

use App\Models\Tagihan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagihanSeeder extends Seeder
{
    public function run(): void
    {
        Tagihan::create([
            'kode_tagihan' => 'TAG-INIT-0001',
            'jenis_tagihan_id' => 1,
            'nis' => '000000',
            'tmp' => 100000,
            'status' => 'Lunas'
        ]);
    }
}

