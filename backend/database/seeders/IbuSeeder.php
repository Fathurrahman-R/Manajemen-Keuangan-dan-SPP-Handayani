<?php

namespace Database\Seeders;

use App\Models\Ibu;
use Illuminate\Database\Seeder;

class IbuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ibu::create([
            'id' => 1,
            'nama' => 'Ibu',
            'pendidikan' => 'SMA',
            'pekerjaan' => 'Ibu Rumah Tangga',
        ]);
    }
}
