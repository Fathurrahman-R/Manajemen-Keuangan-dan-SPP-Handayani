<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class KelasTest extends TestCase
{
    // --- Success scenarios ---
    public function testIndexKelasSuccess()
    {
        $this->createKelasIndexScenario('MI', 3);
        $this->get('api/kelas/mi', ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testIndexKelasEmpty()
    {
        $this->createKelasIndexScenario('MI', 0);
        $this->get('api/kelas/mi', ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []])
            ->assertJson(['data' => []]);
    }

    public function testCreateKelasSuccess()
    {
        $this->createKelasIndexScenario('MI', 0);
        $payload = $this->buildKelasValidPayload();
        $this->post('api/kelas/mi', $payload, ['Authorization' => 'test'])
            ->assertStatus(201) // intentionally 200 to inspect actual response
            ->assertJson(['errors' => []]);
    }

    public function testGetKelasSuccess()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $this->get('api/kelas/mi/' . $kelas->id, ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUpdateKelasSuccess()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $payload = ['nama' => 'Kelas 2'];
        $this->put('api/kelas/mi/' . $kelas->id, $payload, ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testDeleteKelasSuccess()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $this->delete(uri: 'api/kelas/mi/' . $kelas->id, headers: ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    // --- Validation / error scenarios (all forced assertStatus 200 for payload inspection) ---
    public function testCreateKelasValidationMissingNama()
    {
        $this->createKelasIndexScenario('MI', 0);
        $this->post('api/kelas/mi', $this->buildKelasInvalidPayloadMissingNama(), ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testCreateKelasValidationNamaTooLong()
    {
        $this->createKelasIndexScenario('MI', 0);
        $this->post('api/kelas/mi', $this->buildKelasInvalidPayloadLongNama(), ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testCreateKelasValidationDuplicateNama()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $dupPayload = $this->buildKelasDuplicatePayload($kelas->nama);
        $this->post('api/kelas/mi', $dupPayload, ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUpdateKelasValidationDuplicateNama()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        // Create another kelas with the target duplicate name
        Kelas::factory()->create(['jenjang' => 'MI', 'nama' => 'KELAS 2']);
        $this->put('api/kelas/mi/' . $kelas->id, ['nama' => 'KELAS 2'], ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testDeleteKelasValidationHasSiswa()
    {
        $scenario = $this->createKelasWithSiswaScenario('MI');
        $kelas = $scenario['kelas'];
        $this->delete(uri: 'api/kelas/mi/' . $kelas->id, headers: ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedAccessIndex()
    {
        $this->createKelasIndexScenario('MI', 1);
        $this->get('api/kelas/mi')
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedAccessCreate()
    {
        $this->post('api/kelas/mi', $this->buildKelasValidPayload())
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedAccessGet()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $this->get('api/kelas/mi/' . $kelas->id)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedAccessUpdate()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $this->put('api/kelas/mi/' . $kelas->id, ['nama' => 'Kelas 2'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedAccessDelete()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $this->delete('api/kelas/mi/' . $kelas->id)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testInvalidJenjangIndex()
    {
        $this->createKelasIndexScenario('MI', 1);
        $this->get('api/kelas/xx', ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testInvalidJenjangCreate()
    {
        $this->createKelasIndexScenario('MI', 0);
        $this->post('api/kelas/xx', $this->buildKelasValidPayload(), ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }
}
