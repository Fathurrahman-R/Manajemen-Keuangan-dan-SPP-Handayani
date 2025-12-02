<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bersaudara = Kategori::factory()->create([
            'id'=>1,
            'nama'=>'Bersaudara',
        ]);
        $yatim = Kategori::factory()->create([
            'id'=>2,
            'nama'=>'Yatim',
        ]);
        $piatu = Kategori::factory()->create([
            'id'=>3,
            'nama'=>'Piatu',
        ]);
        $yatimpiatu = Kategori::factory()->create([
            'id'=>4,
            'nama'=>'Yatim Piatu',
        ]);
        $reguler = Kategori::factory()->create([
            'id'=>5,
            'nama'=>'Reguler',
        ]);
    }
}
