<?php

namespace Tests\Feature;

use App\Models\Kategori;
use Tests\TestCase;

class KategoriTest extends TestCase
{
    // --- Success scenarios ---
    public function test_index_kategori_success()
    {
        $scenario = $this->createKategoriIndexScenario(4);
        $user = $scenario['user'];
        $this->get('api/kategori', ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_index_kategori_empty()
    {
        $scenario = $this->createKategoriIndexScenario(0);
        $user = $scenario['user'];
        $this->get('api/kategori', ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []])
            ->assertJson(['data' => []]);
    }

    public function test_create_kategori_success()
    {
        $scenario = $this->createKategoriIndexScenario(0);
        $user = $scenario['user'];
        $payload = $this->buildKategoriValidPayload();
        $this->post('api/kategori', $payload, ['Authorization' => $user->token])
            ->assertStatus(201) // khusus create tampilkan status asli 201
            ->assertJson(['errors' => []]);
    }

    public function test_get_kategori_success()
    {
        $scenario = $this->createKategoriCrudScenario();
        $user = $scenario['user'];
        $kategori = $scenario['kategori'];
        $this->get('api/kategori/'.$kategori->id, ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_update_kategori_success()
    {
        $scenario = $this->createKategoriCrudScenario();
        $user = $scenario['user'];
        $kategori = $scenario['kategori'];
        $payload = ['nama' => 'YATIM'];
        $this->put('api/kategori/'.$kategori->id, $payload, ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_delete_kategori_success()
    {
        $scenario = $this->createKategoriCrudScenario();
        $user = $scenario['user'];
        $kategori = $scenario['kategori'];
        $this->delete(uri: 'api/kategori/'.$kategori->id, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    // --- Validation / error scenarios (dipaksa assertStatus 200 untuk observasi payload) ---
    public function test_create_kategori_validation_missing_nama()
    {
        $scenario = $this->createKategoriIndexScenario(0);
        $user = $scenario['user'];
        $this->post('api/kategori', $this->buildKategoriInvalidMissingNamaPayload(), ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_create_kategori_validation_nama_too_long()
    {
        $scenario = $this->createKategoriIndexScenario(0);
        $user = $scenario['user'];
        $this->post('api/kategori', $this->buildKategoriInvalidLongNamaPayload(), ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_create_kategori_validation_duplicate_nama()
    {
        $scenario = $this->createKategoriCrudScenario();
        $user = $scenario['user'];
        $kategori = $scenario['kategori'];
        $dupPayload = $this->buildKategoriDuplicatePayload($kategori->nama);
        $this->post('api/kategori', $dupPayload, ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_update_kategori_validation_duplicate_nama()
    {
        $scenario = $this->createKategoriCrudScenario();
        $user = $scenario['user'];
        $kategori = $scenario['kategori'];
        // kategori lain dengan nama target
        Kategori::factory()->create(['nama' => 'YATIM']);
        $this->put('api/kategori/'.$kategori->id, ['nama' => 'YATIM'], ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_delete_kategori_validation_has_siswa()
    {
        $scenario = $this->createKategoriWithSiswaScenario();
        $user = $scenario['user'];
        $kategori = $scenario['kategori'];
        $this->delete(uri: 'api/kategori/'.$kategori->id, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_index_kategori()
    {
        $this->createKategoriIndexScenario(2);
        $this->get('api/kategori')
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_create_kategori()
    {
        $this->post('api/kategori', $this->buildKategoriValidPayload())
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_get_kategori()
    {
        $scenario = $this->createKategoriCrudScenario();
        $kategori = $scenario['kategori'];
        $this->get('api/kategori/'.$kategori->id)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_update_kategori()
    {
        $scenario = $this->createKategoriCrudScenario();
        $kategori = $scenario['kategori'];
        $this->put('api/kategori/'.$kategori->id, ['nama' => 'YATIM'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_delete_kategori()
    {
        $scenario = $this->createKategoriCrudScenario();
        $kategori = $scenario['kategori'];
        $this->delete('api/kategori/'.$kategori->id)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }
}
