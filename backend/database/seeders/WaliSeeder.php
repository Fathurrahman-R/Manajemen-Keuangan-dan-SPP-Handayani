<?php

namespace Database\Seeders;

use App\Models\Wali;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WaliSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Wali::create([
            'id' => 1,
            'nama' => 'Wali',
            'pekerjaan'=>'Wiraswasta',
            'alamat'=>'Pontianak',
            'no_hp'=>'081122334455',
            'keterangan'=>''
        ]);
    }
}
