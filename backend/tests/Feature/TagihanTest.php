<?php

namespace Tests\Feature;

use App\Models\JenisTagihan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\Wali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TagihanTest extends TestCase
{
    public function testIndexTagihanSuccess()
    {
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create();
//        $jt = JenisTagihan::factory()->create();
        $tagihan = Tagihan::factory()->create([
            'nis'=>$siswa->nis,
        ]);
        $this->get(uri:'api/tagihan',headers: ['Authorization' => $user->token])
        ->assertStatus(200)
        ->assertJson([
            'errors'=>[
            ]
        ]);
    }

    public function testCreateTagihanSuccess()
    {
        $user = User::factory()->create();
        $jt = JenisTagihan::factory()->create([
            'nama'=>'Pendaftaran',
            'jumlah'=>50000
        ]);
        $kelas = Kelas::factory()->create();
        $kategori = Kategori::factory()->create();
        $wali = Wali::factory()->create();
        $siswa = Siswa::factory(2)->create([
            'wali_id' => $wali->id,
            'jenjang'=>'MI',
            'kelas_id'=>$kelas->id,
            'kategori_id'=>$kategori->id,
        ]);
        $payload = [
            'jenis_tagihan_id' => $jt->id,
            'jenjang'=>'MI',
            'kelas_id'=>$kelas->id,
            'kategori_id'=>$kategori->id,
        ];
        $this->post('api/tagihan',$payload,['Authorization' => $user->token])
        ->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }
    public function testBayarTagihanSuccess()
    {
        $user = User::factory()->create();
        $jenisTagihan = JenisTagihan::factory()->create();
        $siswa = Siswa::factory()->create();
        $tagihan = Tagihan::factory()->create([
            'jenis_tagihan_id'=>$jenisTagihan->id,
            'nis'=>$siswa->nis,
        ]);
        $this->patch(uri:'api/tagihan/bayar/'.$tagihan->kode_tagihan,
            data: [
                'jumlah'=>10000
            ],
            headers: ['Authorization' => $user->token])
        ->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }
}
