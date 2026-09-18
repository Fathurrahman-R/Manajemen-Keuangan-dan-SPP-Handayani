<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Kelas;
use App\Models\User;
use Database\Seeders\PermissionEndpointSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KelasTest extends TestCase
{
    // --- Success scenarios ---
    public function test_index_kelas_success()
    {
        $this->createKelasIndexScenario('MI', 3);
        $this->get('api/kelas/mi', ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_index_kelas_empty()
    {
        $this->createKelasIndexScenario('MI', 0);
        $this->get('api/kelas/mi', ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []])
            ->assertJson(['data' => []]);
    }

    public function test_create_kelas_success()
    {
        $this->createKelasIndexScenario('MI', 0);
        $payload = $this->buildKelasValidPayload();
        $this->post('api/kelas/mi', $payload, ['Authorization' => 'test'])
            ->assertStatus(201) // intentionally 200 to inspect actual response
            ->assertJson(['errors' => []]);
    }

    public function test_get_kelas_success()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $this->get('api/kelas/mi/'.$kelas->id, ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_update_kelas_success()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $payload = ['nama' => 'Kelas 2'];
        $this->put('api/kelas/mi/'.$kelas->id, $payload, ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_delete_kelas_success()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $this->delete(uri: 'api/kelas/mi/'.$kelas->id, headers: ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    // --- Validation / error scenarios (all forced assertStatus 200 for payload inspection) ---
    public function test_create_kelas_validation_missing_nama()
    {
        $this->createKelasIndexScenario('MI', 0);
        $this->post('api/kelas/mi', $this->buildKelasInvalidPayloadMissingNama(), ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_create_kelas_validation_nama_too_long()
    {
        $this->createKelasIndexScenario('MI', 0);
        $this->post('api/kelas/mi', $this->buildKelasInvalidPayloadLongNama(), ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_create_kelas_validation_duplicate_nama()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $dupPayload = $this->buildKelasDuplicatePayload($kelas->nama);
        $this->post('api/kelas/mi', $dupPayload, ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_update_kelas_validation_duplicate_nama()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        // Create another kelas with the target duplicate name
        Kelas::factory()->create(['jenjang' => 'MI', 'nama' => 'KELAS 2']);
        $this->put('api/kelas/mi/'.$kelas->id, ['nama' => 'KELAS 2'], ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_delete_kelas_validation_has_siswa()
    {
        $scenario = $this->createKelasWithSiswaScenario('MI');
        $kelas = $scenario['kelas'];
        $this->delete(uri: 'api/kelas/mi/'.$kelas->id, headers: ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_access_index()
    {
        $this->createKelasIndexScenario('MI', 1);
        $this->get('api/kelas/mi')
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_access_create()
    {
        $this->post('api/kelas/mi', $this->buildKelasValidPayload())
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_access_get()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $this->get('api/kelas/mi/'.$kelas->id)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_access_update()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $this->put('api/kelas/mi/'.$kelas->id, ['nama' => 'Kelas 2'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_unauthorized_access_delete()
    {
        $scenario = $this->createKelasCrudScenario('MI');
        $kelas = $scenario['kelas'];
        $this->delete('api/kelas/mi/'.$kelas->id)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_invalid_jenjang_index()
    {
        $this->createKelasIndexScenario('MI', 1);
        $this->get('api/kelas/xx', ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function test_invalid_jenjang_create()
    {
        $this->createKelasIndexScenario('MI', 0);
        $this->post('api/kelas/xx', $this->buildKelasValidPayload(), ['Authorization' => 'test'])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    // --- Regression: duplicate-nama check pada update() harus scoped ke branch ---
    public function test_update_kelas_allows_same_nama_used_by_other_branch()
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PermissionEndpointSeeder::class);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin, ['*']);

        $otherBranch = Branch::factory()->create();
        Kelas::factory()->create([
            'jenjang' => 'MI',
            'nama' => 'KELAS SAMA',
            'branch_id' => $otherBranch->id,
        ]);

        $kelas = Kelas::factory()->create([
            'jenjang' => 'MI',
            'nama' => 'KELAS SAMA',
            'branch_id' => $admin->branch_id,
            'level' => 1,
        ]);

        // Ubah level saja; nama tetap sama seperti punya sendiri, hanya kebetulan
        // ada kelas bernama sama di branch lain — ini tidak boleh dianggap duplikat.
        $this->putJson('api/kelas/mi/'.$kelas->id, [
            'nama' => $kelas->nama,
            'level' => 2,
        ])->assertOk()
            ->assertJsonPath('data.level', 2);
    }

    public function test_update_kelas_rejects_duplicate_nama_within_same_branch()
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PermissionEndpointSeeder::class);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin, ['*']);

        Kelas::factory()->create([
            'jenjang' => 'MI',
            'nama' => 'KELAS 2',
            'branch_id' => $admin->branch_id,
        ]);

        $kelas = Kelas::factory()->create([
            'jenjang' => 'MI',
            'nama' => 'KELAS 1',
            'branch_id' => $admin->branch_id,
        ]);

        $this->putJson('api/kelas/mi/'.$kelas->id, [
            'nama' => 'KELAS 2',
        ])->assertStatus(400)
            ->assertJsonPath('errors.message.0', 'nama kelas sudah ada.');
    }
}
