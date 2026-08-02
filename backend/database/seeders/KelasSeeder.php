<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Kelas;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        $kelasConfig = [
            'MI' => [
                ['nama' => 'Kelas 1', 'level' => 1],
                ['nama' => 'Kelas 2', 'level' => 2],
                ['nama' => 'Kelas 3', 'level' => 3],
                ['nama' => 'Kelas 4', 'level' => 4],
                ['nama' => 'Kelas 5', 'level' => 5],
                ['nama' => 'Kelas 6', 'level' => 6],
            ],
            'TK' => [
                ['nama' => 'MATAHARI', 'level' => 1],
                ['nama' => 'BINTANG', 'level' => 1],
                ['nama' => 'BULAN', 'level' => 1],
            ],
            'KB' => [
                ['nama' => 'PAUD', 'level' => 1],
            ],
        ];

        foreach (Branch::all() as $branch) {
            foreach ($kelasConfig as $jenjang => $kelasList) {
                foreach ($kelasList as $config) {
                    Kelas::firstOrCreate([
                        'jenjang' => $jenjang,
                        'nama' => $config['nama'],
                        'level' => $config['level'],
                        'branch_id' => $branch->id,
                    ]);
                }
            }
        }
    }
}
