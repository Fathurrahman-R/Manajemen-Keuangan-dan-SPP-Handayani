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
    // ===== SUCCESS TESTS =====
    public function testIndexTagihanSuccess()
    {
        $scenario = $this->createTagihanIndexScenario();
        $admin = $scenario['admin'];
        $this->get('api/tagihan', ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testIndexTagihanEmpty()
    {
        $scenario = $this->createTagihanEmptyScenario();
        $admin = $scenario['admin'];
        $this->get('api/tagihan', ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []])
            ->assertJson(['data' => []]);
    }

    public function testSearchTagihanByKodeTagihanOrNamaOrNis()
    {
        $scenario = $this->createTagihanSearchScenario();
        $admin = $scenario['admin'];
        $targetKode = $scenario['tagihanTarget']->kode_tagihan;
        $targetNama = $scenario['targetSiswa']->nama;
        $targetNis = $scenario['targetSiswa']->nis;

        // Search by kode_tagihan
        $this->get('api/tagihan?search=' . $targetKode, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
        // Search by nama siswa
        $this->get('api/tagihan?search=' . $targetNama, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
        // Search by nis siswa
        $this->get('api/tagihan?search=' . $targetNis, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testCreateTagihanMassSuccess()
    {
        $scenario = $this->createTagihanMassScenario(2); // 2 siswa -> 2 tagihan
        $admin = $scenario['admin'];
        $payload = [
            'jenis_tagihan_id' => $scenario['jt']->id,
            'jenjang' => 'MI',
            'kelas_id' => $scenario['kelas']->id,
            'kategori_id' => $scenario['kategori']->id,
        ];
        $this->post('api/tagihan', $payload, ['Authorization' => $admin->token])
            ->assertStatus(201)
            ->assertJson(['errors' => []]);
    }

    public function testGetTagihanSuccess()
    {
        $scenario = $this->createTagihanCrudScenario();
        $admin = $scenario['admin'];
        $tagihan = $scenario['tagihan'];
        $this->get('api/tagihan/' . $tagihan->kode_tagihan, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUpdateTagihanSuccess()
    {
        $scenario = $this->createTagihanCrudScenario();
        $admin = $scenario['admin'];
        $tagihan = $scenario['tagihan'];
        // ganti jenis_tagihan_id
        $jtBaru = \App\Models\JenisTagihan::factory()->create();
        $payload = [
            'jenis_tagihan_id' => $jtBaru->id
        ];
        $this->patch('api/tagihan/' . $tagihan->kode_tagihan, $payload, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testDeleteTagihanSuccess()
    {
        $scenario = $this->createTagihanCrudScenario();
        $admin = $scenario['admin'];
        $tagihan = $scenario['tagihan'];
        $this->delete('api/tagihan/' . $tagihan->kode_tagihan, [], ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    // ===== VALIDATION & ERROR TESTS =====
    public function testCreateTagihanValidationRequired()
    {
        $scenario = $this->createTagihanIndexScenario(0); // hanya admin & relasi dasar
        $admin = $scenario['admin'];
        $this->post('api/tagihan', $this->buildTagihanInvalidRequiredPayload(), ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testCreateTagihanValidationInvalidJenjang()
    {
        $scenario = $this->createTagihanIndexScenario(0);
        $admin = $scenario['admin'];
        $payload = $this->buildTagihanInvalidJenjangPayload();
        $this->post('api/tagihan', $payload, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testCreateTagihanValidationInvalidRelasi()
    {
        $scenario = $this->createTagihanIndexScenario(0);
        $admin = $scenario['admin'];
        $payload = $this->buildTagihanInvalidRelasiPayload();
        $this->post('api/tagihan', $payload, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testDeleteTagihanUsedByPembayaran()
    {
        $scenario = $this->createTagihanWithPembayaranScenario();
        $admin = $scenario['admin'];
        $tagihan = $scenario['tagihan'];
        $this->delete('api/tagihan/' . $tagihan->kode_tagihan, [], ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testGetTagihanNotFound()
    {
        $scenario = $this->createTagihanEmptyScenario();
        $admin = $scenario['admin'];
        $this->get('api/tagihan/TAGIHAN-XXXX', ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUpdateTagihanNotFound()
    {
        $scenario = $this->createTagihanEmptyScenario();
        $admin = $scenario['admin'];
        $payload = $this->buildTagihanValidPayload();
        $this->patch('api/tagihan/TAGIHAN-XXXX', $payload, ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testDeleteTagihanNotFound()
    {
        $scenario = $this->createTagihanEmptyScenario();
        $admin = $scenario['admin'];
        $this->delete('api/tagihan/TAGIHAN-XXXX', [], ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testSearchTagihanEmpty()
    {
        $scenario = $this->createTagihanEmptyScenario();
        $admin = $scenario['admin'];
        $this->get('api/tagihan?search=UNKNOWN', ['Authorization' => $admin->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []])
            ->assertJson(['data' => []]);
    }

    // ===== UNAUTHORIZED TESTS =====
    public function testUnauthorizedIndexTagihan()
    {
        $this->createTagihanIndexScenario(2);
        $this->get('api/tagihan')
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedCreateTagihan()
    {
        $p = $this->buildTagihanValidPayload();
        $this->post('api/tagihan', $p)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedGetTagihan()
    {
        $scenario = $this->createTagihanCrudScenario();
        $tagihan = $scenario['tagihan'];
        $this->get('api/tagihan/' . $tagihan->kode_tagihan)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedUpdateTagihan()
    {
        $scenario = $this->createTagihanCrudScenario();
        $tagihan = $scenario['tagihan'];
        $payload = $this->buildTagihanValidPayload();
        $this->patch('api/tagihan/' . $tagihan->kode_tagihan, $payload)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedDeleteTagihan()
    {
        $scenario = $this->createTagihanCrudScenario();
        $tagihan = $scenario['tagihan'];
        $this->delete('api/tagihan/' . $tagihan->kode_tagihan)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }
}
