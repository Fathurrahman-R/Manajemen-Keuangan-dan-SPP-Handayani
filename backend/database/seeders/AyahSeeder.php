<?php

namespace Database\Seeders;

use App\Models\Ayah;
use Illuminate\Database\Seeder;

class AyahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ayah::create([
            'id' => 1,
            'nama' => 'Ayah',
            'pendidikan' => 'SMA',
            'pekerjaan' => 'Wiraswasta',
        ]);
    }
}
