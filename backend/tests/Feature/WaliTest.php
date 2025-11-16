<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Wali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WaliTest extends TestCase
{
    public function testIndexSuccess()
    {
        $user = User::factory()->create();
        $kelas = Kelas::factory()->create();
        $kategori = Kategori::factory()->create();
        $wali = Wali::factory()->create();
        $siswa = Siswa::factory()->create(
            [
                'jenjang'=>'TK',
                'kelas_id' => $kelas->id,
                'kategori_id' => $kategori->id,
                'ayah_id' => $wali->id,
                'ibu_id' => $wali->id,
                'wali_id' => $wali->id,
            ]
        );
        $this->get(uri: 'api/wali',headers:
        [
            'Authorization' => $user->token
        ])->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }
    public function testCreateSuccess()
    {
        $payload = [
            'nama'=>'Ayah',
            'jenis_kelamin'=>'Laki-laki',
            'agama'=>'Islam',
            'pendidikan_terakhir'=>'SMA',
            'pekerjaan'=>'Wiraswasta',
            'alamat'=>'Pontianak',
            'no_hp'=>'081122334455',
            'keterangan'=>null
        ];

        $user = User::factory()->create();
        $this->post(uri: 'api/wali',
            data: $payload,
            headers: ['Authorization' => $user->token])
        ->assertStatus(201)
        ->assertJson([
            'errors'=>[]
        ]);
    }
    public function testGetSuccess()
    {
        $user = User::factory()->create();
        $wali = Wali::factory()->create();
        $this->get(uri:  'api/wali/'.$wali->id,
            headers:['Authorization' => $user->token]
        )
        ->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }
    public function testUpdateSuccess()
    {
        $payload = [
            'nama'=>'Ayah',
            'jenis_kelamin'=>'Laki-laki',
            'agama'=>'Islam',
            'pendidikan_terakhir'=>'SMA',
            'pekerjaan'=>'Wiraswasta',
            'alamat'=>'Pontianak',
            'no_hp'=>'081122334455',
            'keterangan'=>null
        ];
        $user = User::factory()->create();
        $wali = Wali::factory()->create();
        $this->put(uri: 'api/wali/'.$wali->id,
        data: $payload,
        headers: ['Authorization' => $user->token])
        ->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }
    public function testDeleteSuccess()
    {
        $user = User::factory()->create();
        $wali = Wali::factory()->create();
        $this->delete(uri: 'api/wali/'.$wali->id,
        headers: ['Authorization' => $user->token])
        ->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }
}
