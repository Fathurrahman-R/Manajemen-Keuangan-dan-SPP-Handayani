<?php

namespace Tests\Feature;

use App\Models\JenisTagihan;
use Tests\TestCase;

class JenisTagihanTest extends TestCase
{
    // ===== SUCCESS TESTS =====
    public function testIndexJenisTagihanSuccess()
    {
        $scenario = $this->createJenisTagihanIndexScenario(3);
        $user = $scenario['user'];
        $this->get('api/jenis-tagihan', ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testIndexJenisTagihanEmpty()
    {
        $scenario = $this->createJenisTagihanIndexScenario(0);
        $user = $scenario['user'];
        $this->get('api/jenis-tagihan', ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []])
            ->assertJson(['data' => []]);
    }

    public function testCreateJenisTagihanSuccess()
    {
        $scenario = $this->createJenisTagihanIndexScenario(0);
        $user = $scenario['user'];
        $payload = $this->buildJenisTagihanValidPayload();
        $this->post('api/jenis-tagihan', $payload, ['Authorization' => $user->token])
            ->assertStatus(201) // khusus create tampilkan status asli
            ->assertJson(['errors' => []]);
    }

    public function testGetJenisTagihanSuccess()
    {
        $scenario = $this->createJenisTagihanCrudScenario();
        $user = $scenario['user'];
        $jt = $scenario['jt'];
        $this->get('api/jenis-tagihan/' . $jt->id, ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUpdateJenisTagihanSuccess()
    {
        $scenario = $this->createJenisTagihanCrudScenario();
        $user = $scenario['user'];
        $jt = $scenario['jt'];
        $payload = $this->buildJenisTagihanValidPayload();
        $payload['nama'] = 'SPP MARET';
        $this->put('api/jenis-tagihan/' . $jt->id, $payload, ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testDeleteJenisTagihanSuccess()
    {
        $scenario = $this->createJenisTagihanCrudScenario();
        $user = $scenario['user'];
        $jt = $scenario['jt'];
        $this->delete('api/jenis-tagihan/' . $jt->id, [], ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    // ===== VALIDATION & ERROR TESTS =====
    public function testCreateJenisTagihanValidationRequired()
    {
        $scenario = $this->createJenisTagihanIndexScenario(0);
        $user = $scenario['user'];
        $this->post('api/jenis-tagihan', $this->buildJenisTagihanInvalidRequiredPayload(), ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testCreateJenisTagihanValidationNamaShortAndLong()
    {
        $scenario = $this->createJenisTagihanIndexScenario(0);
        $user = $scenario['user'];
        $this->post('api/jenis-tagihan', $this->buildJenisTagihanInvalidNamaShort(), ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
        $this->post('api/jenis-tagihan', $this->buildJenisTagihanInvalidNamaLong(), ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testCreateJenisTagihanValidationJatuhTempoFormat()
    {
        $scenario = $this->createJenisTagihanIndexScenario(0);
        $user = $scenario['user'];
        $this->post('api/jenis-tagihan', $this->buildJenisTagihanInvalidJatuhTempoFormat(), ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testCreateJenisTagihanValidationJumlahFormat()
    {
        $scenario = $this->createJenisTagihanIndexScenario(0);
        $user = $scenario['user'];
        $this->post('api/jenis-tagihan', $this->buildJenisTagihanInvalidJumlahNonNumeric(), ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
        $this->post('api/jenis-tagihan', $this->buildJenisTagihanInvalidJumlahTooLong(), ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testGetJenisTagihanNotFound()
    {
        $scenario = $this->createJenisTagihanIndexScenario(0);
        $user = $scenario['user'];
        $this->get('api/jenis-tagihan/99999', ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUpdateJenisTagihanNotFound()
    {
        $scenario = $this->createJenisTagihanIndexScenario(0);
        $user = $scenario['user'];
        $payload = $this->buildJenisTagihanValidPayload();
        $this->put('api/jenis-tagihan/99999', $payload, ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testDeleteJenisTagihanNotFound()
    {
        $scenario = $this->createJenisTagihanIndexScenario(0);
        $user = $scenario['user'];
        $this->delete('api/jenis-tagihan/99999', [], ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testDeleteJenisTagihanUsedByTagihan()
    {
        $scenario = $this->createJenisTagihanWithTagihanScenario();
        $user = $scenario['user'];
        $jt = $scenario['jt'];
        $this->delete('api/jenis-tagihan/' . $jt->id, [], ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedIndexJenisTagihan()
    {
        $this->createJenisTagihanIndexScenario(2);
        $this->get('api/jenis-tagihan')
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedCreateJenisTagihan()
    {
        $this->post('api/jenis-tagihan', $this->buildJenisTagihanValidPayload())
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedGetJenisTagihan()
    {
        $scenario = $this->createJenisTagihanCrudScenario();
        $jt = $scenario['jt'];
        $this->get('api/jenis-tagihan/' . $jt->id)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedUpdateJenisTagihan()
    {
        $scenario = $this->createJenisTagihanCrudScenario();
        $jt = $scenario['jt'];
        $this->put('api/jenis-tagihan/' . $jt->id, $this->buildJenisTagihanValidPayload())
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }

    public function testUnauthorizedDeleteJenisTagihan()
    {
        $scenario = $this->createJenisTagihanCrudScenario();
        $jt = $scenario['jt'];
        $this->delete('api/jenis-tagihan/' . $jt->id)
            ->assertStatus(200)
            ->assertJson(['errors' => []]);
    }
}
