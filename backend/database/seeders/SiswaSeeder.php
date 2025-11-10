<?php

namespace Database\Seeders;

use App\Models\Siswa;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Siswa::create([
            'nis'=>'000000',
            'nisn'=>'000000',
            'nama'=>'Siswa',
            'jenis_kelamin'=>'Laki-laki',
            'tempat_lahir'=>'Bandung',
            'tanggal_lahir'=>'1990-01-01',
            'agama'=>'Islam',
            'alamat'=>'Jln. Raya Bandung',
            'ayah'=>null,
            'ibu'=>null,
            'wali'=>1,
            'jenjang'=>'MI',
            'kelas'=>1,
            'kategori'=>1,
            'asal_sekolah'=>null,
            'kelas_diterima'=>null,
            'tahun_diterima'=>null,
            'status'=>null,
            'keterangan'=>null
        ]);
    }
}
