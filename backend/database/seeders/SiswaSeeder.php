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
            'nis'=>'000001',
            'nisn'=>'000001',
            'nama'=>'Siswa',
            'jenis_kelamin'=>'Laki-laki',
            'tempat_lahir'=>'Bandung',
            'tanggal_lahir'=>'1990-01-01',
            'agama'=>'Islam',
            'alamat'=>'Jln. Raya Bandung',
            'ayah_id'=>null,
            'ibu_id'=>null,
            'wali_id'=>1,
            'jenjang'=>'MI',
            'kelas_id'=>1,
            'kategori_id'=>1,
            'asal_sekolah'=>'MI Handayani',
            'kelas_diterima'=>'Kelas 1',
            'tahun_diterima'=>null,
            'status'=>null,
            'keterangan'=>null
        ]);
    }
}
