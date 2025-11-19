<?php

namespace Tests\Feature;

use App\Models\JenisTagihan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\Wali;
use App\Services\GenerateKodePembayaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PembayaranTest extends TestCase
{
//    private static $kode_pembayaran;
    public function testIndexPembayaran()
    {
        $user = User::factory()->create();
        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create();
        $kategori = Kategori::factory()->create();
        $jt = JenisTagihan::factory()->create([
            'nama'=>'SPP',
            'jumlah'=>100000
        ]);
        $siswa = Siswa::factory()
            ->for($wali,'ayah')
            ->for($wali,'ibu')
            ->for($wali,'wali')
            ->for($kelas,'kelas')
            ->for($kategori,'kategori')
            ->create([
                'jenjang'=>'MI'
            ]);
        $tagihan = Tagihan::factory()
            ->for($siswa, 'siswa')
            ->for($jt,'jenis_tagihan')
            ->create();
        $pembayaran = Pembayaran::factory()
            ->for($tagihan,'tagihan')
            ->create();
        $this->get(uri:'api/pembayaran',headers: ['Authorization'=>$user->token])
        ->assertStatus(200)
        ->assertJson([
            "errors"=>[

            ]
        ]);
    }
    public function testPembayaranCicil()
    {
        $user = User::factory()->create();
        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create();
        $kategori = Kategori::factory()->create();
        $jt = JenisTagihan::factory()->create([
            'nama'=>'SPP Januari',
            'jumlah'=>100000
        ]);
        $siswa = Siswa::factory()->create([
            'wali_id' => $wali->id,
            'kelas_id'=>$kelas->id,
            'kategori_id'=>$kategori->id,
            'jenjang'=>'MI'
        ]);
        $tagihan = Tagihan::factory()->create([
            'nis'=>$siswa->nis,
            'jenis_tagihan_id'=>$jt->id,
            'tmp'=>50000
        ]);
        $this->post('api/pembayaran/bayar/'.$tagihan->kode_tagihan,
            [
                'jumlah'=>100000,
                'metode'=>'Tunai',
                'pembayar'=>$wali->nama
            ],
            [
                'Authorization'=>$user->token
            ])
        ->assertStatus(200)
        ->assertJson([
            "errors"=>[

            ]
        ]);
    }

    public function testPembayaranLunas()
    {
        $user = User::factory()->create();
        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create();
        $kategori = Kategori::factory()->create();
        $jt = JenisTagihan::factory()->create([
            'nama'=>'SPP Januari',
            'jumlah'=>100000
        ]);
        $siswa = Siswa::factory()->create([
            'wali_id' => $wali->id,
            'kelas_id'=>$kelas->id,
            'kategori_id'=>$kategori->id,
            'jenjang'=>'MI'
        ]);
        $tagihan = Tagihan::factory()->create([
            'nis'=>$siswa->nis,
            'jenis_tagihan_id'=>$jt->id,
            'tmp'=>0
        ]);
        $response = $this->post('api/pembayaran/lunas/'.$tagihan->kode_tagihan,
            [
                'metode'=>'Tunai',
                'pembayar'=>$wali->nama
            ],
            [
                'Authorization'=>'test'
            ])
        ->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }

    public function testKwitansiCicil()
    {
        $pembayaran = $this->createPembayaranCicil();
        $this->get('api/pembayaran/kwitansi/'.$pembayaran['pembayaran']->kode_pembayaran,
            [
                'Authorization'=>$pembayaran['user']->token
            ]
        )
        ->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }
    public function testKwitansiLunas()
    {
        $pembayaran = $this->createPembayaranLunas();
        $this->get('api/pembayaran/kwitansi/'.$pembayaran['pembayaran']->kode_pembayaran,
            [
                'Authorization'=>$pembayaran['user']->token
            ]
        )
        ->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }

    public function testDeletePembayaran()
    {
        $pembayaran = $this->createPembayaranCicil();
        $this->delete(uri:'api/pembayaran/'.$pembayaran['pembayaran']->kode_pembayaran,
            headers:[
                'Authorization'=>$pembayaran['user']->token
            ]
        )
        ->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }
}
