<?php

namespace Tests\Feature;

use App\Models\Siswa;
use Database\Seeders\KategoriSeeder;
use Database\Seeders\KelasSeeder;
use Database\Seeders\SiswaSeeder;
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

    public function testCreateSiswaFailed()
    {
        $this->seed(UserSeeder::class);
        $this->seed(KelasSeeder::class);
        $this->seed(WaliSeeder::class);
        $this->seed(KategoriSeeder::class);
        $this->seed(SiswaSeeder::class);

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
            ->assertStatus(400)
            ->assertJson([
                'errors'=>[
                    'message'=>[
                        'siswa dengan nis/nisn tersebut sudah terdaftar.'
                    ]
                ]
            ]);
    }

    public function testUpdateSuccess()
    {
        $this->seed(UserSeeder::class);
        $this->seed(KelasSeeder::class);
        $this->seed(WaliSeeder::class);
        $this->seed(KategoriSeeder::class);
        $this->seed(SiswaSeeder::class);
        $siswa = Siswa::select('id')->where('nis','000000')->first();

        $this->put('api/siswas/mi/'.$siswa->id,
        [
            'nis'=>'000000',
            'nisn'=>'000000',
            'nama'=>'Fathur',
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
        ])->assertStatus(200)
        ->assertJson([
            'data'=>[
                'nis'=>'000000',
                'nisn'=>'000000',
                'nama'=>'Fathur',
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

    public function testGetSuccess()
    {
        $this->seed(UserSeeder::class);
        $this->seed(KelasSeeder::class);
        $this->seed(WaliSeeder::class);
        $this->seed(KategoriSeeder::class);
        $this->seed(SiswaSeeder::class);
        $siswa = Siswa::where('nama','Siswa')->first();
        $this->get(uri: 'api/siswas/mi/'.$siswa->id, headers:
        [
            'Authorization'=>'test'
        ])->assertStatus(200)
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

    public function testDeleteSuccess()
    {
        $this->seed(UserSeeder::class);
        $this->seed(KelasSeeder::class);
        $this->seed(WaliSeeder::class);
        $this->seed(KategoriSeeder::class);
        $this->seed(SiswaSeeder::class);
        $siswa = Siswa::where('nama','Siswa')->first();

        $this->delete(uri: 'api/siswas/mi/'.$siswa->id,headers:
        [
            'Authorization'=>'test'
        ])->assertStatus(200)
        ->assertJson([
            'data'=>[
                true
            ]
        ]);
    }
}
