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
            'branch_id'=>1
        ]);
        $yatim = Kategori::factory()->create([
            'id'=>2,
            'nama'=>'Yatim',
            'branch_id'=>1
        ]);
        $piatu = Kategori::factory()->create([
            'id'=>3,
            'nama'=>'Piatu',
            'branch_id'=>1
        ]);
        $yatimpiatu = Kategori::factory()->create([
            'id'=>4,
            'nama'=>'Yatim Piatu',
            'branch_id'=>1
        ]);
        $reguler = Kategori::factory()->create([
            'id'=>5,
            'nama'=>'Reguler',
            'branch_id'=>1
        ]);
    }
}
