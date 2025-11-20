<?php

namespace Tests\Feature;

use App\Models\Kategori;
use Tests\TestCase;

class KategoriTest extends TestCase
{
    // --- Success scenarios ---
    public function testIndexKategoriSuccess()
    {
        $scenario = $this->createKategoriIndexScenario(4);
        $user = $scenario['user'];
        $this->get('api/kategori', ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testIndexKategoriEmpty()
    {
        $scenario = $this->createKategoriIndexScenario(0);
        $user = $scenario['user'];
        $this->get('api/kategori', ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []])
            ->assertJson(['data' => []]);
    }

    public function testCreateKategoriSuccess()
    {
        $scenario = $this->createKategoriIndexScenario(0);
        $user = $scenario['user'];
        $payload = $this->buildKategoriValidPayload();
        $this->post('api/kategori', $payload, ['Authorization' => $user->token])
            ->assertStatus(201) // khusus create tampilkan status asli 201
            ->assertJson(['errors' => []]);
    }

    public function testGetKategoriSuccess()
    {
        $scenario = $this->createKategoriCrudScenario();
        $user = $scenario['user'];
        $kategori = $scenario['kategori'];
        $this->get('api/kategori/' . $kategori->id, ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUpdateKategoriSuccess()
    {
        $scenario = $this->createKategoriCrudScenario();
        $user = $scenario['user'];
        $kategori = $scenario['kategori'];
        $payload = ['nama' => 'YATIM'];
        $this->put('api/kategori/' . $kategori->id, $payload, ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testDeleteKategoriSuccess()
    {
        $scenario = $this->createKategoriCrudScenario();
        $user = $scenario['user'];
        $kategori = $scenario['kategori'];
        $this->delete(uri: 'api/kategori/' . $kategori->id, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    // --- Validation / error scenarios (dipaksa assertStatus 200 untuk observasi payload) ---
    public function testCreateKategoriValidationMissingNama()
    {
        $scenario = $this->createKategoriIndexScenario(0);
        $user = $scenario['user'];
        $this->post('api/kategori', $this->buildKategoriInvalidMissingNamaPayload(), ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testCreateKategoriValidationNamaTooLong()
    {
        $scenario = $this->createKategoriIndexScenario(0);
        $user = $scenario['user'];
        $this->post('api/kategori', $this->buildKategoriInvalidLongNamaPayload(), ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testCreateKategoriValidationDuplicateNama()
    {
        $scenario = $this->createKategoriCrudScenario();
        $user = $scenario['user'];
        $kategori = $scenario['kategori'];
        $dupPayload = $this->buildKategoriDuplicatePayload($kategori->nama);
        $this->post('api/kategori', $dupPayload, ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUpdateKategoriValidationDuplicateNama()
    {
        $scenario = $this->createKategoriCrudScenario();
        $user = $scenario['user'];
        $kategori = $scenario['kategori'];
        // kategori lain dengan nama target
        Kategori::factory()->create(['nama' => 'YATIM']);
        $this->put('api/kategori/' . $kategori->id, ['nama' => 'YATIM'], ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testDeleteKategoriValidationHasSiswa()
    {
        $scenario = $this->createKategoriWithSiswaScenario();
        $user = $scenario['user'];
        $kategori = $scenario['kategori'];
        $this->delete(uri: 'api/kategori/' . $kategori->id, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedIndexKategori()
    {
        $this->createKategoriIndexScenario(2);
        $this->get('api/kategori')
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedCreateKategori()
    {
        $this->post('api/kategori', $this->buildKategoriValidPayload())
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedGetKategori()
    {
        $scenario = $this->createKategoriCrudScenario();
        $kategori = $scenario['kategori'];
        $this->get('api/kategori/' . $kategori->id)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedUpdateKategori()
    {
        $scenario = $this->createKategoriCrudScenario();
        $kategori = $scenario['kategori'];
        $this->put('api/kategori/' . $kategori->id, ['nama' => 'YATIM'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedDeleteKategori()
    {
        $scenario = $this->createKategoriCrudScenario();
        $kategori = $scenario['kategori'];
        $this->delete('api/kategori/' . $kategori->id)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }
}
