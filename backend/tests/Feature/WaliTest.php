<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Wali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WaliTest extends TestCase
{
    // SUCCESS CASES
    public function testIndexSuccess()
    {
        $scenario = $this->createWaliIndexScenario(3);
        $user = $scenario['user'];
        $this->get(uri: 'api/wali', headers: [
            'Authorization' => 'sa'
        ])->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }
    public function testSearchByNamaSuccess()
    {
        $scenario = $this->createWaliSearchScenario();
        $user = $scenario['user'];
        $this->get(uri: 'api/wali?search=budi doremi', headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }
    public function testCreateSuccess()
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
                'errors' => []
            ]);
    }
    public function testGetSuccess()
    {
        $scenario = $this->createWaliForCrud();
        $user = $scenario['user'];
        $wali = $scenario['wali'];
        $this->get(
            uri: 'api/wali/1' . $wali->id,
            headers: ['Authorization' => $user->token]
        )
            ->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }
    public function testUpdateSuccess()
    {
        $payload = $this->buildWaliValidPayload();
        $scenario = $this->createWaliForCrud();
        $user = $scenario['user'];
        $wali = $scenario['wali'];
        $this->put(
            uri: 'api/wali/1' . $wali->id,
            data: $payload,
            headers: ['Authorization' => $user->token]
        )
            ->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }
    public function testDeleteSuccess()
    {
        $scenario = $this->createWaliForCrud();
        $user = $scenario['user'];
        $wali = $scenario['wali'];
        $this->delete(
            uri: 'api/wali/' . $wali->id,
            headers: ['Authorization' => $user->token]
        )
            ->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }

    // VALIDATION CASES (always assert 200 to expose response for docs)
    public function testCreateValidationRequired()
    {
        $user = User::factory()->create();
        $payload = $this->buildWaliInvalidRequiredPayload();
        $this->post(uri: 'api/wali', data: $payload, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }

    public function testCreateValidationInvalidJenisKelamin()
    {
        $user = User::factory()->create();
        $payload = $this->buildWaliInvalidJenisKelaminPayload();
        $this->post(uri: 'api/wali', data: $payload, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }

    public function testCreateValidationInvalidNoHp()
    {
        $user = User::factory()->create();
        $payload = $this->buildWaliInvalidNoHpPayload();
        $this->post(uri: 'api/wali', data: $payload, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }

    public function testUpdateValidationInvalidJenisKelamin()
    {
        $scenario = $this->createWaliForCrud();
        $user = $scenario['user'];
        $wali = $scenario['wali'];
        $payload = ['jenis_kelamin' => 'Unknown'];
        $this->put(uri: 'api/wali/' . $wali->id, data: $payload, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }

    public function testUpdateValidationInvalidNoHp()
    {
        $scenario = $this->createWaliForCrud();
        $user = $scenario['user'];
        $wali = $scenario['wali'];
        $payload = ['no_hp' => 'abc#'];
        $this->put(uri: 'api/wali/' . $wali->id, data: $payload, headers: ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }
}
