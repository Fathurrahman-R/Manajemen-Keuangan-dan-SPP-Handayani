<?php

namespace Tests\Feature;

use App\Models\Siswa;
use Tests\TestCase;

class SiswaTest extends TestCase
{
    public function test_create_siswa_success()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];
        $ayah = $scenario['ayah'];
        $ibu = $scenario['ibu'];
        $wali = $scenario['wali'];
        $kelas = $scenario['kelas'];
        $kategori = $scenario['kategori'];

        $response = $this->post('api/siswa/mi', [
            'nis' => '000001',
            'nisn' => '000001',
            'nama' => 'Siswa Factory',
            'jenis_kelamin' => 'Laki-laki',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2010-01-01',
            'agama' => 'Islam',
            'alamat' => 'Jln. Raya Bandung',
            'ayah_id' => $ayah->id,
            'ibu_id' => $ibu->id,
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
        ], [
            'Authorization' => $admin->token,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_create_siswa_validation_failed()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];

        $this->post(
            'api/siswa/mi',
            [
                // kirim payload kosong / tidak lengkap supaya FormRequest gagal
            ],
            [
                'Authorization' => $admin->token,
            ]
        )
            ->assertStatus(400)
            ->assertJsonStructure(
                []
            )
            ->assertStatus(200)
            ->assertJsonStructure([
                'errors' => [],
            ]);
    }

    public function test_create_siswa_duplicate_nis_failed()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];
        $existing = $scenario['siswa'];

        $this->post(
            'api/siswa/mi',
            [
                'nis' => $existing->nis,
                'nisn' => $existing->nisn,
                'nama' => 'Siswa Duplikat',
                'jenis_kelamin' => $existing->jenis_kelamin,
                'tempat_lahir' => $existing->tempat_lahir,
                'tanggal_lahir' => $existing->tanggal_lahir,
                'agama' => $existing->agama,
                'alamat' => $existing->alamat,
                'ayah_id' => $existing->ayah_id,
                'ibu_id' => $existing->ibu_id,
                'wali_id' => $existing->wali_id,
                'kelas_id' => $existing->kelas_id,
                'kategori_id' => $existing->kategori_id,
            ],
            [
                'Authorization' => $admin->token,
            ]
        )
            ->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'Siswa dengan NIS tersebut sudah terdaftar.',
                    ],
                ],
            ]);
    }

    public function test_update_success()
    {
        $scenario = $this->createSiswaTkScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];

        $this->put(
            'api/siswa/tk/'.$siswa->id,
            [
                'nama' => 'Fathurrahman',
                'jenis_kelamin' => $siswa->jenis_kelamin,
                'tempat_lahir' => $siswa->tempat_lahir,
                'tanggal_lahir' => $siswa->tanggal_lahir,
                'agama' => $siswa->agama,
                'alamat' => $siswa->alamat,
                'ayah_id' => $siswa->ayah_id,
                'ibu_id' => $siswa->ibu_id,
                'wali_id' => $siswa->wali_id,
                'kelas_id' => $siswa->kelas_id,
                'kategori_id' => $siswa->kategori_id,
                'asal_sekolah' => $siswa->asal_sekolah,
                'kelas_diterima' => $siswa->kelas_diterima,
                'tahun_diterima' => $siswa->tahun_diterima,
                'status' => $siswa->status,
                'keterangan' => $siswa->keterangan,
            ],
            [
                'Authorization' => $admin->token,
            ]
        )->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_update_not_found()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];

        $this->put('api/siswa/mi/999999', [
            'nis' => $siswa->nis,
            'nisn' => $siswa->nisn,
            'nama' => 'Fathurrahman',
            'jenis_kelamin' => $siswa->jenis_kelamin,
            'tempat_lahir' => $siswa->tempat_lahir,
            'tanggal_lahir' => $siswa->tanggal_lahir,
            'agama' => $siswa->agama,
            'alamat' => $siswa->alamat,
            'ayah_id' => $siswa->ayah_id,
            'ibu_id' => $siswa->ibu_id,
            'wali_id' => $siswa->wali_id,
            'jenjang' => 'MI',
            'kelas_id' => $siswa->kelas_id,
            'kategori_id' => $siswa->kategori_id,
            'asal_sekolah' => $siswa->asal_sekolah,
            'kelas_diterima' => $siswa->kelas_diterima,
            'tahun_diterima' => $siswa->tahun_diterima,
            'status' => $siswa->status,
            'keterangan' => $siswa->keterangan,
        ], [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'siswa tidak ditemukan.',
                    ],
                ],
            ]);
    }

    public function test_get_success()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];
        $this->get(uri: 'api/siswa/mi/'.$siswa->id, headers: [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_get_not_found()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];

        $this->get('api/siswa/mi/999999', [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'siswa tidak ditemukan.',
                    ],
                ],
            ]);
    }

    public function test_delete_success()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];

        $this->delete(uri: 'api/siswa/mi/'.$siswa->id, headers: [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }

    public function test_delete_not_found()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];

        $this->delete(uri: 'api/siswa/mi/999999', headers: [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'siswa tidak ditemukan.',
                    ],
                ],
            ]);
    }

    public function test_indexing_siswa()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];

        $this->get(uri: 'api/siswa/tk', headers: [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }

    public function test_indexing_siswa_failed()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];

        $this->get(uri: 'api/siswa/tk', headers: [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'belum ada data siswa dengan jenjang tersebut.',
                    ],
                ],
            ]);
    }

    public function test_search_siswa_success()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];

        // gunakan nama siswa sebagai keyword search
        $this->get('api/siswa/kb?search='.urlencode($siswa->nama), [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }

    public function test_search_siswa_failed()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];

        // keyword yang dipastikan tidak ada
        $this->get('api/siswa/mi?search=___tidak_akan_ditemukan___', [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }

    // TK scenarios

    public function test_create_siswa_tk_success()
    {
        $scenario = $this->createSiswaTkScenario();
        $admin = $scenario['admin'];
        $wali = $scenario['wali'];
        $kelas = $scenario['kelas'];
        $kategori = $scenario['kategori'];

        $this->post('api/siswa/tk', [
            'nis' => '000001',
            'nama' => 'Siswa TK',
            'jenis_kelamin' => 'Laki-laki',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2018-01-01',
            'agama' => 'Islam',
            'alamat' => 'Alamat TK',
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
        ], [
            'Authorization' => $admin->token,
        ])->assertStatus(201)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_create_siswa_tk_validation_failed()
    {
        $scenario = $this->createSiswaTkScenario();
        $admin = $scenario['admin'];

        $this->post('api/siswa/tk', [], [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJsonStructure([
                'errors' => [],
            ]);
    }

    public function test_get_siswa_tk_success()
    {
        $scenario = $this->createSiswaTkScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];

        $this->get('api/siswa/tk/'.$siswa->id, [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_get_siswa_tk_not_found()
    {
        $scenario = $this->createSiswaTkScenario();
        $admin = $scenario['admin'];

        $this->get('api/siswa/tk/999999', [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'siswa tidak ditemukan.',
                    ],
                ],
            ]);
    }

    public function test_index_siswa_tk()
    {
        $scenario = $this->createSiswaTkScenario();
        $admin = $scenario['admin'];
        // sudah ada minimal satu siswa TK dari skenario

        $this->get('api/siswa/tk', [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }

    // KB scenarios

    public function test_create_siswa_kb_success()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];
        $wali = $scenario['wali'];
        $kelas = $scenario['kelas'];
        $kategori = $scenario['kategori'];

        $this->post('api/siswa/kb', [
            'nis' => '000001',
            'nama' => 'Siswa KB',
            'jenis_kelamin' => 'Perempuan',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2019-01-01',
            'agama' => 'Islam',
            'alamat' => 'Alamat KB',
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
        ], [
            'Authorization' => $admin->token,
        ])->assertStatus(201)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_create_siswa_kb_validation_failed()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];

        $this->post('api/siswa/kb', [], [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJsonStructure([
                'errors' => [],
            ]);
    }

    public function test_get_siswa_kb_success()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];

        $this->get('api/siswa/kb/'.$siswa->id, [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [],
            ]);
    }

    public function test_get_siswa_kb_not_found()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];

        $this->get('api/siswa/kb/999999', [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'siswa tidak ditemukan.',
                    ],
                ],
            ]);
    }

    public function test_index_siswa_kb()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];

        $this->get('api/siswa/kb', [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }

    public function test_unathorized()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];

        $this->get('api/siswa/kb', [
            'Authorization' => 'salah',
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }

    public function test_create_siswa_does_not_auto_create_account_and_appears_in_unregistered_list()
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\PermissionEndpointSeeder::class);

        $admin = \App\Models\User::factory()->admin()->create();
        \Laravel\Sanctum\Sanctum::actingAs($admin, ['*']);
        $ayah = \App\Models\Ayah::factory()->create();
        $ibu = \App\Models\Ibu::factory()->create();
        $wali = \App\Models\Wali::factory()->create();
        $kelas = \App\Models\Kelas::factory()->create(['branch_id' => $admin->branch_id]);
        $kategori = \App\Models\Kategori::factory()->create(['branch_id' => $admin->branch_id]);

        $response = $this->post('api/siswa/mi', [
            'nis' => '000002',
            'nisn' => '000002',
            'nama' => 'Siswa Belum Terdaftar',
            'jenis_kelamin' => 'Laki-laki',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2010-01-01',
            'agama' => 'Islam',
            'alamat' => 'Jln. Raya Bandung',
            'ayah_id' => $ayah->id,
            'ibu_id' => $ibu->id,
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
        ]);
        $response->assertStatus(201);
        $siswaId = $response->json('data.id');

        $this->assertDatabaseMissing('users', ['siswa_id' => $siswaId]);

        $this->get('api/akun-siswa/unregistered')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $siswaId]);
    }

    public function test_akun_siswa_index_scoped_to_branch()
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\PermissionEndpointSeeder::class);

        $admin = \App\Models\User::factory()->admin()->create();
        \Laravel\Sanctum\Sanctum::actingAs($admin, ['*']);

        $kelasOwn = \App\Models\Kelas::factory()->create(['branch_id' => $admin->branch_id]);
        $kategoriOwn = \App\Models\Kategori::factory()->create(['branch_id' => $admin->branch_id]);
        $siswaOwn = \App\Models\Siswa::factory()->create(['branch_id' => $admin->branch_id, 'kelas_id' => $kelasOwn->id, 'kategori_id' => $kategoriOwn->id]);
        $ownUser = \App\Models\User::factory()->create(['username' => 'siswa-own', 'branch_id' => $admin->branch_id, 'siswa_id' => $siswaOwn->id]);
        $ownUser->assignRole('siswa');

        $otherBranch = \App\Models\Branch::factory()->create();
        $kelasOther = \App\Models\Kelas::factory()->create(['branch_id' => $otherBranch->id]);
        $kategoriOther = \App\Models\Kategori::factory()->create(['branch_id' => $otherBranch->id]);
        $siswaOther = \App\Models\Siswa::factory()->create(['branch_id' => $otherBranch->id, 'kelas_id' => $kelasOther->id, 'kategori_id' => $kategoriOther->id]);
        $otherUser = \App\Models\User::factory()->create(['username' => 'siswa-other', 'branch_id' => $otherBranch->id, 'siswa_id' => $siswaOther->id]);
        $otherUser->assignRole('siswa');

        $this->get('api/akun-siswa')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $ownUser->id])
            ->assertJsonMissing(['id' => $otherUser->id]);
    }
}
