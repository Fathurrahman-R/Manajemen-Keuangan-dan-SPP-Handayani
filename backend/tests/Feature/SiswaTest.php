<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Wali;
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
                    'ayah_id'=>1,
                    'ibu_id'=>1,
                    'wali_id'=>1,
                    'jenjang'=>'MI',
                    'kelas_id'=>1,
                    'kategori_id'=>1,
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
            'errors'=>[
            ]
        ]);
    }

    public function testCreateSiswaFailed()
    {


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

    public function testIndexingSiswa()
    {
        // Buat data referensi relasi
        $ayah = Wali::factory()->create();
        $ibu = Wali::factory()->create();
        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create();
        $kategori = Kategori::factory()->create();
        $admin = User::factory()->admin()->create();

        // Buat beberapa siswa dengan relasi yang sudah dibuat
        Siswa::factory(3)->create([
            'jenjang' => 'MI',
            'ayah_id' => $ayah->id,
            'ibu_id' => $ibu->id,
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
        ]);

        $this->get(uri: 'api/siswas/mi', headers:
        [
            'Authorization'=>'test'
        ])->assertStatus(200)
        ->assertJson([
            'errors'=>[

            ]
        ]);
    }
    public function testIndexingSiswaFailed()
    {


        $this->get(uri: 'api/siswas/tk', headers:
        [
            'Authorization'=>'test'
        ])->assertStatus(404)
        ->assertJson([
            'errors'=>[
                'message'=>[
                    'belum ada data siswa dengan jenjang tersebut.'
                ]
            ]
        ]);
    }
}
