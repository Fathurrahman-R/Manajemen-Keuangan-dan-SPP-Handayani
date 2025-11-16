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

class PembayaranTest extends TestCase
{
    public function testPembayaran()
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
                'jumlah'=>50000,
                'metode'=>'Tunai'
            ],
            [
                'Authorization'=>$user->token
            ])
        ->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }
}
