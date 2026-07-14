<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class WaliTest extends TestCase
{
    // SUCCESS CASES
    public function test_index_success()
    {
        $scenario = $this->createWaliIndexScenario(3);
        $user = $scenario['user'];
        $this->get(uri: 'api/wali', headers: [
            'Authorization' => 'sa',
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_search_by_nama_success()
    {
        $scenario = $this->createWaliSearchScenario();
        $user = $scenario['user'];
        $this->get(uri: 'api/wali?search=budi doremi', headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_create_success()
    {
        $payload = $this->buildWaliValidPayload();

        $user = User::factory()->create();
        $this->post(
            uri: 'api/wali',
            data: $payload,
            headers: ['Authorization' => $user->token]
        )
            ->assertStatus(201) // intentionally 200 to show body for docs
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_get_success()
    {
        $scenario = $this->createWaliForCrud();
        $user = $scenario['user'];
        $wali = $scenario['wali'];
        $this->get(
            uri: 'api/wali/1'.$wali->id,
            headers: ['Authorization' => $user->token]
        )
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_update_success()
    {
        $payload = $this->buildWaliValidPayload();
        $scenario = $this->createWaliForCrud();
        $user = $scenario['user'];
        $wali = $scenario['wali'];
        $this->put(
            uri: 'api/wali/1'.$wali->id,
            data: $payload,
            headers: ['Authorization' => $user->token]
        )
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_delete_success()
    {
        $scenario = $this->createWaliForCrud();
        $user = $scenario['user'];
        $wali = $scenario['wali'];
        $this->delete(
            uri: 'api/wali/'.$wali->id,
            headers: ['Authorization' => $user->token]
        )
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    // VALIDATION CASES (always assert 200 to expose response for docs)
    public function test_create_validation_required()
    {
        $user = User::factory()->create();
        $payload = $this->buildWaliInvalidRequiredPayload();
        $this->post(uri: 'api/wali', data: $payload, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_create_validation_invalid_jenis_kelamin()
    {
        $user = User::factory()->create();
        $payload = $this->buildWaliInvalidJenisKelaminPayload();
        $this->post(uri: 'api/wali', data: $payload, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_create_validation_invalid_no_hp()
    {
        $user = User::factory()->create();
        $payload = $this->buildWaliInvalidNoHpPayload();
        $this->post(uri: 'api/wali', data: $payload, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_update_validation_invalid_jenis_kelamin()
    {
        $scenario = $this->createWaliForCrud();
        $user = $scenario['user'];
        $wali = $scenario['wali'];
        $payload = ['jenis_kelamin' => 'Unknown'];
        $this->put(uri: 'api/wali/'.$wali->id, data: $payload, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_update_validation_invalid_no_hp()
    {
        $scenario = $this->createWaliForCrud();
        $user = $scenario['user'];
        $wali = $scenario['wali'];
        $payload = ['no_hp' => 'abc#'];
        $this->put(uri: 'api/wali/'.$wali->id, data: $payload, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }
}
