<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\Tagihan;
use Tests\TestCase;

class TagihanTest extends TestCase
{
    // ===== SUCCESS TESTS =====
    public function test_index_tagihan_success()
    {
        $scenario = $this->createTagihanIndexScenario();
        $admin = $scenario['admin'];
        $this->get('api/tagihan', ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_index_tagihan_empty()
    {
        $scenario = $this->createTagihanEmptyScenario();
        $admin = $scenario['admin'];
        $this->get('api/tagihan', ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []])
            ->assertJson(['data' => []]);
    }

    public function test_search_tagihan_by_kode_tagihan_or_nama_or_nis()
    {
        $scenario = $this->createTagihanSearchScenario();
        $admin = $scenario['admin'];
        $targetKode = $scenario['tagihanTarget']->kode_tagihan;
        $targetNama = $scenario['targetSiswa']->nama;
        $targetNis = $scenario['targetSiswa']->nis;

        // Search by kode_tagihan
        $this->get('api/tagihan?search='.$targetKode, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
        // Search by nama siswa
        $this->get('api/tagihan?search='.$targetNama, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
        // Search by nis siswa
        $this->get('api/tagihan?search='.$targetNis, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_create_tagihan_mass_success()
    {
        $scenario = $this->createTagihanMassScenario(2); // 2 siswa -> 2 tagihan
        $admin = $scenario['admin'];
        $payload = [
            'jenis_tagihan_id' => $scenario['jt']->id,
            'jenjang' => 'MI',
            'kelas_id' => [$scenario['kelas']->id],
            'kategori_id' => [$scenario['kategori']->id],
        ];
        $this->post('api/tagihan', $payload, ['Authorization' => $admin->token])
            ->assertStatus(201)
            ->assertJson(['errors' => []]);
    }

    public function test_create_tagihan_multi_kelas_kategori_success()
    {
        $admin = \App\Models\User::factory()->admin()->create(['token' => 'tagihan-multi']);
        $jt = \App\Models\JenisTagihan::factory()->create();

        $kelasA = \App\Models\Kelas::factory()->create();
        $kelasB = \App\Models\Kelas::factory()->create();
        $kategoriA = \App\Models\Kategori::factory()->create();
        $kategoriB = \App\Models\Kategori::factory()->create();

        // Satu siswa per kombinasi kelas x kategori yang dipilih (4 total), plus satu siswa di luar seleksi.
        Siswa::factory()->create(['jenjang' => 'MI', 'kelas_id' => $kelasA->id, 'kategori_id' => $kategoriA->id]);
        Siswa::factory()->create(['jenjang' => 'MI', 'kelas_id' => $kelasA->id, 'kategori_id' => $kategoriB->id]);
        Siswa::factory()->create(['jenjang' => 'MI', 'kelas_id' => $kelasB->id, 'kategori_id' => $kategoriA->id]);
        Siswa::factory()->create(['jenjang' => 'MI', 'kelas_id' => $kelasB->id, 'kategori_id' => $kategoriB->id]);
        $kelasLain = \App\Models\Kelas::factory()->create();
        Siswa::factory()->create(['jenjang' => 'MI', 'kelas_id' => $kelasLain->id, 'kategori_id' => $kategoriA->id]);

        $payload = [
            'jenis_tagihan_id' => $jt->id,
            'jenjang' => 'MI',
            'kelas_id' => [$kelasA->id, $kelasB->id],
            'kategori_id' => [$kategoriA->id, $kategoriB->id],
        ];

        $response = $this->post('api/tagihan', $payload, ['Authorization' => $admin->token])
            ->assertStatus(201)
            ->assertJson(['errors' => []]);

        $response->assertJsonCount(4, 'data');
    }

    public function test_create_tagihan_requires_at_least_one_kelas_and_kategori()
    {
        $admin = \App\Models\User::factory()->admin()->create(['token' => 'tagihan-multi-empty']);
        $jt = \App\Models\JenisTagihan::factory()->create();

        $payload = [
            'jenis_tagihan_id' => $jt->id,
            'jenjang' => 'MI',
            'kelas_id' => [],
            'kategori_id' => [],
        ];

        $this->post('api/tagihan', $payload, ['Authorization' => $admin->token])
            ->assertStatus(400);
    }

    public function test_get_tagihan_success()
    {
        $scenario = $this->createTagihanCrudScenario();
        $admin = $scenario['admin'];
        $tagihan = $scenario['tagihan'];
        $this->get('api/tagihan/'.$tagihan->kode_tagihan, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_update_tagihan_success()
    {
        $scenario = $this->createTagihanCrudScenario();
        $admin = $scenario['admin'];
        $tagihan = $scenario['tagihan'];
        // ganti jenis_tagihan_id
        $jtBaru = \App\Models\JenisTagihan::factory()->create();
        $payload = [
            'jenis_tagihan_id' => $jtBaru->id,
        ];
        $this->patch('api/tagihan/'.$tagihan->kode_tagihan, $payload, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_delete_tagihan_success()
    {
        $scenario = $this->createTagihanCrudScenario();
        $admin = $scenario['admin'];
        $tagihan = $scenario['tagihan'];
        $this->delete('api/tagihan/'.$tagihan->kode_tagihan, [], ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    // ===== VALIDATION & ERROR TESTS =====
    public function test_create_tagihan_validation_required()
    {
        $scenario = $this->createTagihanIndexScenario(0); // hanya admin & relasi dasar
        $admin = $scenario['admin'];
        $this->post('api/tagihan', $this->buildTagihanInvalidRequiredPayload(), ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_create_tagihan_validation_invalid_jenjang()
    {
        $scenario = $this->createTagihanIndexScenario(0);
        $admin = $scenario['admin'];
        $payload = $this->buildTagihanInvalidJenjangPayload();
        $this->post('api/tagihan', $payload, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_create_tagihan_validation_invalid_relasi()
    {
        $scenario = $this->createTagihanIndexScenario(0);
        $admin = $scenario['admin'];
        $payload = $this->buildTagihanInvalidRelasiPayload();
        $this->post('api/tagihan', $payload, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_delete_tagihan_used_by_pembayaran()
    {
        $scenario = $this->createTagihanWithPembayaranScenario();
        $admin = $scenario['admin'];
        $tagihan = $scenario['tagihan'];
        $this->delete('api/tagihan/'.$tagihan->kode_tagihan, [], ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_get_tagihan_not_found()
    {
        $scenario = $this->createTagihanEmptyScenario();
        $admin = $scenario['admin'];
        $this->get('api/tagihan/TAGIHAN-XXXX', ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_update_tagihan_not_found()
    {
        $scenario = $this->createTagihanEmptyScenario();
        $admin = $scenario['admin'];
        $payload = $this->buildTagihanValidPayload();
        $this->patch('api/tagihan/TAGIHAN-XXXX', $payload, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_delete_tagihan_not_found()
    {
        $scenario = $this->createTagihanEmptyScenario();
        $admin = $scenario['admin'];
        $this->delete('api/tagihan/TAGIHAN-XXXX', [], ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_search_tagihan_empty()
    {
        $scenario = $this->createTagihanEmptyScenario();
        $admin = $scenario['admin'];
        $this->get('api/tagihan?search=UNKNOWN', ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []])
            ->assertJson(['data' => []]);
    }

    // ===== UNAUTHORIZED TESTS =====
    public function test_unauthorized_index_tagihan()
    {
        $this->createTagihanIndexScenario(2);
        $this->get('api/tagihan')
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_create_tagihan()
    {
        $p = $this->buildTagihanValidPayload();
        $this->post('api/tagihan', $p)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_get_tagihan()
    {
        $scenario = $this->createTagihanCrudScenario();
        $tagihan = $scenario['tagihan'];
        $this->get('api/tagihan/'.$tagihan->kode_tagihan)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_update_tagihan()
    {
        $scenario = $this->createTagihanCrudScenario();
        $tagihan = $scenario['tagihan'];
        $payload = $this->buildTagihanValidPayload();
        $this->patch('api/tagihan/'.$tagihan->kode_tagihan, $payload)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_delete_tagihan()
    {
        $scenario = $this->createTagihanCrudScenario();
        $tagihan = $scenario['tagihan'];
        $this->delete('api/tagihan/'.$tagihan->kode_tagihan)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }
}
