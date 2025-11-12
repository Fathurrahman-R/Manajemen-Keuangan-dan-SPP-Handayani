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
        $admin = User::factory()->admin()->create();

        // buat data referensi relasi
        $ayah = \App\Models\Wali::factory()->create();
        $ibu = \App\Models\Wali::factory()->create();
        $wali = \App\Models\Wali::factory()->create();
        $kelas = \App\Models\Kelas::factory()->create();
        $kategori = \App\Models\Kategori::factory()->create();

        $response = $this->post('api/siswas/mi', [
            'nis' => '000001',
            'nisn' => '000001',
            'nama' => 'Siswa Factory',
            'jenis_kelamin' => 'Laki-laki',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2010-01-01',
            'agama' => 'Islam',
            'alamat' => 'Jln. Raya Bandung',
            'ayah_id' => $ayah->id,
            'ibu_id' => $ibu->id,
            'wali_id' => $wali->id,
            'jenjang'=> 'MI',
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
        ], [
            'Authorization' => 'test' // bisa diganti token login kalau kamu pakai auth middleware
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'errors' => [

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
        User::factory()->admin()->create();
        $ayah = Wali::factory()->create();
        $ibu = Wali::factory()->create();
        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create();
        $kategori = Kategori::factory()->create();
        $siswa = Siswa::factory()->create([
            'ayah_id' => $ayah->id,
            'ibu_id' => $ibu->id,
            'wali_id' => $wali->id,
            'jenjang'=> 'MI',
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
            'asal_sekolah'=>'MI Handayani',
            'kelas_diterima'=>'1',
            'tahun_diterima'=>'2020',
            'keterangan'=>null
        ]);

        $this->put('api/siswas/mi/'.$siswa->id,
        [
            'nis'=>$siswa->nis,
            'nisn'=>$siswa->nisn,
            'nama'=>'Fathurrahman',
            'jenis_kelamin'=>$siswa->jenis_kelamin,
            'tempat_lahir'=>$siswa->tempat_lahir,
            'tanggal_lahir'=>$siswa->tanggal_lahir,
            'agama'=>$siswa->agama,
            'alamat'=>$siswa->alamat,
            'ayah_id'=>$siswa->ayah_id,
            'ibu_id'=>$siswa->ibu_id,
            'wali_id'=>$siswa->wali_id,
            'jenjang'=>'MI',
            'kelas_id'=>$siswa->kelas_id,
            'kategori_id'=>$siswa->kategori_id,
            'asal_sekolah'=>$siswa->asal_sekolah,
            'kelas_diterima'=>$siswa->kelas_diterima,
            'tahun_diterima'=>$siswa->tahun_diterima,
            'status'=>$siswa->status,
            'keterangan'=>$siswa->keterangan
        ],
        [
            'Authorization'=>'test'
        ])->assertStatus(200)
        ->assertJson([
            'errors'=>[

            ]
        ]);
    }

    public function testGetSuccess()
    {
        $ayah = Wali::factory()->create();
        $ibu = Wali::factory()->create();
        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create();
        $kategori = Kategori::factory()->create();
        $admin = User::factory()->admin()->create();

        // Buat beberapa siswa dengan relasi yang sudah dibuat
        $siswa = Siswa::factory()->create([
            'jenjang' => 'MI',
            'ayah_id' => $ayah->id,
            'ibu_id' => $ibu->id,
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
        ]);
        $this->get(uri: 'api/siswas/mi/'.$siswa->id, headers:
        [
            'Authorization'=>'test'
        ])->assertStatus(200)
        ->assertJson([
            'errors'=>[

            ]
        ]);
    }

    public function testDeleteSuccess()
    {

        User::factory()->admin()->create();
        $ayah = Wali::factory()->create();
        $ibu = Wali::factory()->create();
        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create();
        $kategori = Kategori::factory()->create();
        $siswa = Siswa::factory()->create([
            'ayah_id' => $ayah->id,
            'ibu_id' => $ibu->id,
            'wali_id' => $wali->id,
            'jenjang'=> 'MI',
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
            'asal_sekolah'=>'MI Handayani',
            'kelas_diterima'=>'1',
            'tahun_diterima'=>'2020',
            'keterangan'=>null
        ]);

        $this->delete(uri: 'api/siswas/mi/'.$siswa->id,headers:
        [
            'Authorization'=>'test'
        ])->assertStatus(200)
        ->assertJson([
            'errors'=>[
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

        $this->get(uri: 'api/siswa/mi', headers:
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
