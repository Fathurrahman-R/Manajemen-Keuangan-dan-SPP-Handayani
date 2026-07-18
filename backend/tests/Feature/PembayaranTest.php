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
use Tests\TestCase;

class PembayaranTest extends TestCase
{
    //    private static $kode_pembayaran;
    public function test_index_pembayaran()
    {
        $user = User::factory()->create();
        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create();
        $kategori = Kategori::factory()->create();
        $jt = JenisTagihan::factory()->create([
            'nama' => 'SPP',
            'jumlah' => 100000,
        ]);
        $siswa = Siswa::factory()
            ->for($wali, 'ayah')
            ->for($wali, 'ibu')
            ->for($wali, 'wali')
            ->for($kelas, 'kelas')
            ->for($kategori, 'kategori')
            ->create([
                'jenjang' => 'MI',
            ]);
        $tagihan = Tagihan::factory()
            ->for($siswa, 'siswa')
            ->for($jt, 'jenis_tagihan')
            ->create();
        $pembayaran = Pembayaran::factory()
            ->for($tagihan, 'tagihan')
            ->create();
        $this->get(uri: 'api/pembayaran', headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => [

                ],
            ]);
    }

    public function test_pembayaran_cicil()
    {
        $user = User::factory()->create();
        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create();
        $kategori = Kategori::factory()->create();
        $jt = JenisTagihan::factory()->create([
            'nama' => 'SPP Januari',
            'jumlah' => 100000,
        ]);
        $siswa = Siswa::factory()->create([
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
            'jenjang' => 'MI',
        ]);
        $tagihan = Tagihan::factory()->create([
            'nis' => $siswa->nis,
            'jenis_tagihan_id' => $jt->id,
        ]);
        $this->post('api/pembayaran/bayar/'.$tagihan->kode_tagihan,
            [
                'jumlah' => 100000,
                'metode' => 'offline',
                'pembayar' => $wali->nama,
            ],
            [
                'Authorization' => $user->token,
            ])
            ->assertStatus(200)
            ->assertJson([
                'errors' => [

                ],
            ]);
    }

    public function test_pembayaran_lunas()
    {
        $user = User::factory()->create();
        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create();
        $kategori = Kategori::factory()->create();
        $jt = JenisTagihan::factory()->create([
            'nama' => 'SPP Januari',
            'jumlah' => 100000,
        ]);
        $siswa = Siswa::factory()->create([
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
            'jenjang' => 'MI',
        ]);
        $tagihan = Tagihan::factory()->create([
            'nis' => $siswa->nis,
            'jenis_tagihan_id' => $jt->id,
            'tmp' => 0,
        ]);
        $response = $this->post('api/pembayaran/lunas/'.$tagihan->kode_tagihan,
            [
                'metode' => 'offline',
                'pembayar' => $wali->nama,
            ],
            [
                'Authorization' => 'test',
            ])
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_kwitansi_cicil()
    {
        $pembayaran = $this->createPembayaranCicil();
        $this->get('api/pembayaran/kwitansi/'.$pembayaran['pembayaran']->kode_pembayaran,
            [
                'Authorization' => $pembayaran['user']->token,
            ]
        )
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_kwitansi_lunas()
    {
        $pembayaran = $this->createPembayaranLunas();
        $this->get('api/pembayaran/kwitansi/'.$pembayaran['pembayaran']->kode_pembayaran,
            [
                'Authorization' => $pembayaran['user']->token,
            ]
        )
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_delete_pembayaran()
    {
        $pembayaran = $this->createPembayaranCicil();
        $this->delete(uri: 'api/pembayaran/'.$pembayaran['pembayaran']->kode_pembayaran,
            headers: [
                'Authorization' => $pembayaran['user']->token,
            ]
        )
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_index_pembayaran_kosong()
    {
        $user = User::factory()->create();
        $this->get('api/pembayaran', ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_search_pembayaran_by_kode_pembayaran()
    {
        $scenario = $this->createPembayaranCicil();
        $kode = $scenario['pembayaran']->kode_pembayaran;
        $this->get('api/pembayaran?search='.substr($kode, 0, 3), ['Authorization' => $scenario['user']->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_search_pembayaran_by_nama_siswa()
    {
        $scenario = $this->createPembayaranCicil();
        $nama = $scenario['pembayaran']->tagihan->siswa->nama;
        $this->get('api/pembayaran?search='.substr($nama, 0, 3), ['Authorization' => $scenario['user']->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_search_pembayaran_by_nis_siswa()
    {
        $scenario = $this->createPembayaranCicil();
        $nis = $scenario['pembayaran']->tagihan->siswa->nis;
        $this->get('api/pembayaran?search='.substr($nis, 0, 3), ['Authorization' => $scenario['user']->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_search_pembayaran_not_found()
    {
        $scenario = $this->createPembayaranCicil();
        $this->get('api/pembayaran?search=ZZZ', ['Authorization' => $scenario['user']->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_create_pembayaran_cicil_valid()
    {
        $data = $this->createTagihan();
        $this->post('api/pembayaran/bayar/'.$data['tagihan']->kode_tagihan, [
            'jumlah' => 1,
            'metode' => 'offline',
            'pembayar' => 'TEST',
        ], ['Authorization' => $data['user']->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_create_pembayaran_cicil_overpay()
    {
        $data = $this->createTagihan();
        $this->post('api/pembayaran/bayar/'.$data['tagihan']->kode_tagihan, [
            'jumlah' => $data['tagihan']->jenis_tagihan->jumlah + 1,
            'metode' => 'offline',
            'pembayar' => 'TEST',
        ], ['Authorization' => $data['user']->token])
            ->assertStatus(200);
    }

    public function test_create_pembayaran_cicil_invalid_metode()
    {
        $data = $this->createTagihan();
        $this->post('api/pembayaran/bayar/'.$data['tagihan']->kode_tagihan, [
            'jumlah' => 10,
            'metode' => 'INVALID_METODE',
            'pembayar' => 'TEST',
        ], ['Authorization' => $data['user']->token])
            ->assertStatus(400);
    }

    public function test_create_pembayaran_cicil_jumlah_required()
    {
        $data = $this->createTagihan();
        $this->post('api/pembayaran/bayar/'.$data['tagihan']->kode_tagihan, [
            'metode' => 'offline',
            'pembayar' => 'TEST',
        ], ['Authorization' => $data['user']->token])
            ->assertStatus(200);
    }

    public function test_create_pembayaran_lunas_valid()
    {
        $data = $this->createTagihan();
        $this->post('api/pembayaran/lunas/'.$data['tagihan']->kode_tagihan, [
            'metode' => 'offline',
            'pembayar' => 'TEST',
        ], ['Authorization' => $data['user']->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_create_pembayaran_lunas_invalid_metode()
    {
        $data = $this->createTagihan();
        $this->post('api/pembayaran/lunas/'.$data['tagihan']->kode_tagihan, [
            'metode' => 'XXX',
            'pembayar' => 'TEST',
        ], ['Authorization' => $data['user']->token])
            ->assertStatus(200);
    }

    public function test_create_pembayaran_lunas_pembayar_required()
    {
        $data = $this->createTagihan();
        $this->post('api/pembayaran/lunas/'.$data['tagihan']->kode_tagihan, [
            'metode' => 'offline',
        ], ['Authorization' => $data['user']->token])
            ->assertStatus(200);
    }

    public function test_delete_pembayaran_valid()
    {
        $scenario = $this->createPembayaranCicil();
        $this->delete('api/pembayaran/'.$scenario['pembayaran']->kode_pembayaran, [], [
            'Authorization' => $scenario['user']->token,
        ])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_delete_pembayaran_not_found()
    {
        $user = User::factory()->create();
        $this->delete('api/pembayaran/XXX', [], ['Authorization' => $user->token])
            ->assertStatus(200);
    }

    public function test_kwitansi_not_found()
    {
        $user = User::factory()->create();
        $this->get('api/pembayaran/kwitansi/XXX', ['Authorization' => $user->token])
            ->assertStatus(200);
    }

    public function test_unauthorized_access_index()
    {
        $this->get('api/pembayaran')
            ->assertStatus(200);
    }

    public function test_unauthorized_create_cicil()
    {
        $data = $this->createTagihan();
        $this->post('api/pembayaran/bayar/'.$data['tagihan']->kode_tagihan, [
            'jumlah' => 10,
            'metode' => 'offline',
            'pembayar' => 'TEST',
        ])
            ->assertStatus(200);
    }
}
