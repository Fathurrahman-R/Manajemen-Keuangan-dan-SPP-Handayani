<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Kelas;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kelas1 = Kelas::factory()->create([
            'id'=>1,
            'nama'=>'Kelas 1',
            'jenjang'=>'MI',
        ]);
        $kelas2 = Kelas::factory()->create([
            'id'=>2,
            'nama'=>'Kelas 2',
            'jenjang'=>'MI',
        ]);
        $kelas3 = Kelas::factory()->create([
            'id'=>3,
            'nama'=>'Kelas 3',
            'jenjang'=>'MI',
        ]);
        $kelas4 = Kelas::factory()->create([
            'id'=>4,
            'nama'=>'Kelas 4',
            'jenjang'=>'MI',
        ]);
        $kelas5 = Kelas::factory()->create([
            'id'=>5,
            'nama'=>'Kelas 5',
            'jenjang'=>'MI',
        ]);
        $kelas6 = Kelas::factory()->create([
            'id'=>6,
            'nama'=>'Kelas 6',
            'jenjang'=>'MI',
        ]);
    }
}
