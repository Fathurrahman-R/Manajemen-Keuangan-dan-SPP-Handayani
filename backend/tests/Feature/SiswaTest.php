<?php

namespace Tests\Feature;

use Database\Seeders\KategoriSeeder;
use Database\Seeders\KelasSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WaliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SiswaTest extends TestCase
{
    public function testCreateSiswaSuccess()
    {
        $this->seed(UserSeeder::class);
        $this->seed(KelasSeeder::class);
        $this->seed(WaliSeeder::class);
        $this->seed(KategoriSeeder::class);
        $this->post('api/siswas/mi',
            [
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
            ],
            [
                'Authorization'=>'test'
            ])
        ->assertStatus(201)
        ->assertJson([
            'data'=>[
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
            ]
        ]);
    }
}
