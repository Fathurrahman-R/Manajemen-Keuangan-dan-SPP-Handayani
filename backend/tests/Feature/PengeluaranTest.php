<?php

namespace Tests\Feature;

use App\Models\Pengeluaran;
use Tests\TestCase;

class PengeluaranTest extends TestCase
{
    public function test_index_pengeluaran()
    {
        $admin = $this->createAdminWithToken();
        Pengeluaran::factory()->count(3)->create();

        $this->get('/api/pengeluaran', [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJsonStructure([
                'data' => [],
            ])
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_create_pengeluaran_success()
    {
        $admin = $this->createAdminWithToken();

        $this->post('/api/pengeluaran', [
            'tanggal' => '2025-01-01',
            'uraian' => 'Pembelian ATK',
            'jumlah' => 100000000000,
        ], [
            'Authorization' => $admin->token,
        ])->assertStatus(201)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_create_pengeluaran_invalid_tanggal()
    {
        $admin = $this->createAdminWithToken();

        $this->post('/api/pengeluaran', [
            'tanggal' => '01-01-2025', // invalid format
            'uraian' => 'Pembelian ATK',
            'jumlah' => 500000,
        ], [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_create_pengeluaran_invalid_jumlah()
    {
        $admin = $this->createAdminWithToken();

        $this->post('/api/pengeluaran', [
            'tanggal' => '2025-01-01',
            'uraian' => 'Pembelian ATK',
            'jumlah' => -1, // negative not allowed
        ], [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_create_pengeluaran_jumlah_not_numeric()
    {
        $admin = $this->createAdminWithToken();

        $this->post('/api/pengeluaran', [
            'tanggal' => '2025-01-01',
            'uraian' => 'Pembelian ATK',
            'jumlah' => 'lima ribu',
        ], [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_create_pengeluaran_validation_failed()
    {
        $admin = $this->createAdminWithToken();

        $this->post('/api/pengeluaran', [], [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJsonStructure([
                'errors' => [],
            ]);
    }

    public function test_show_pengeluaran_success()
    {
        $admin = $this->createAdminWithToken();
        $pengeluaran = Pengeluaran::factory()->create();

        $this->get('/api/pengeluaran/'.$pengeluaran->id, [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_show_pengeluaran_not_found()
    {
        $admin = $this->createAdminWithToken();

        $this->get('/api/pengeluaran/999999', [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'pengeluaran tidak ditemukan.',
                    ],
                ],
            ]);
    }

    public function test_update_pengeluaran_success()
    {
        $admin = $this->createAdminWithToken();
        $pengeluaran = Pengeluaran::factory()->create();

        $this->put('/api/pengeluaran/'.$pengeluaran->id, [
            'uraian' => 'Perubahan uraian',
        ], [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_update_pengeluaran_invalid_tanggal()
    {
        $admin = $this->createAdminWithToken();
        $pengeluaran = Pengeluaran::factory()->create();

        $this->put('/api/pengeluaran/'.$pengeluaran->id, [
            'tanggal' => '31/12/2025',
        ], [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_update_pengeluaran_invalid_jumlah()
    {
        $admin = $this->createAdminWithToken();
        $pengeluaran = Pengeluaran::factory()->create();

        $this->put('/api/pengeluaran/'.$pengeluaran->id, [
            'jumlah' => -10,
        ], [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_update_pengeluaran_not_found()
    {
        $admin = $this->createAdminWithToken();

        $this->put('/api/pengeluaran/999999', [
            'uraian' => 'Perubahan uraian',
        ], [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'pengeluaran tidak ditemukan.',
                    ],
                ],
            ]);
    }

    public function test_delete_pengeluaran_success()
    {
        $admin = $this->createAdminWithToken();
        $pengeluaran = Pengeluaran::factory()->create();

        $this->delete(uri: '/api/pengeluaran/'.$pengeluaran->id, headers: [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'data' => true,
            ])
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_delete_pengeluaran_not_found()
    {
        $admin = $this->createAdminWithToken();

        $this->delete(uri: '/api/pengeluaran/999999', headers: [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'pengeluaran tidak ditemukan.',
                    ],
                ],
            ]);
    }

    public function test_index_pengeluaran_with_date_filters()
    {
        $admin = $this->createAdminWithToken();
        Pengeluaran::factory()->create(['tanggal' => '2025-01-01']);
        Pengeluaran::factory()->create(['tanggal' => '2025-02-01']);

        $this->get('/api/pengeluaran?start_date=2025-01-01&end_date=2025-01-31', [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }
}
