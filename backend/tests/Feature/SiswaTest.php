<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Wali;
use Database\Seeders\KategoriSeeder;
use Database\Seeders\KelasSeeder;
use Database\Seeders\SiswaSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WaliSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SiswaTest extends TestCase
{
    public function testCreateSiswaSuccess()
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
            'Authorization' => $admin->token
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'errors' => []
            ]);
    }

    public function testCreateSiswaValidationFailed()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];

        $this->post(
            'api/siswa/mi',
            [
                // kirim payload kosong / tidak lengkap supaya FormRequest gagal
            ],
            [
                'Authorization' => $admin->token
            ]
        )
            ->assertStatus(400)
            ->assertJsonStructure(
                []
            )
            ->assertStatus(200)
            ->assertJsonStructure([
                'errors' => []
            ]);
    }

    public function testCreateSiswaDuplicateNisFailed()
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
                'Authorization' => $admin->token
            ]
        )
            ->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'Siswa dengan NIS tersebut sudah terdaftar.'
                    ]
                ]
            ]);
    }

    public function testUpdateSuccess()
    {
        $scenario = $this->createSiswaTkScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];

        $this->put(
            'api/siswa/tk/' . $siswa->id,
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
                'keterangan' => $siswa->keterangan
            ],
            [
                'Authorization' => $admin->token
            ]
        )->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }

    public function testUpdateNotFound()
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
            'keterangan' => $siswa->keterangan
        ], [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'siswa tidak ditemukan.'
                    ]
                ]
            ]);
    }

    public function testGetSuccess()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];
        $this->get(uri: 'api/siswa/mi/' . $siswa->id, headers: [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }

    public function testGetNotFound()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];

        $this->get('api/siswa/mi/999999', [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'siswa tidak ditemukan.'
                    ]
                ]
            ]);
    }

    public function testDeleteSuccess()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];

        $this->delete(uri: 'api/siswa/mi/' . $siswa->id, headers: [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }

    public function testDeleteNotFound()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];

        $this->delete(uri: 'api/siswa/mi/999999', headers: [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'siswa tidak ditemukan.'
                    ]
                ]
            ]);
    }

    public function testIndexingSiswa()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];

        $this->get(uri: 'api/siswa/mi', headers: [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }
    public function testIndexingSiswaFailed()
    {
        $scenario = $this->createSiswaMiScenario();
        $admin = $scenario['admin'];

        $this->get(uri: 'api/siswa/tk', headers: [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'belum ada data siswa dengan jenjang tersebut.'
                    ]
                ]
            ]);
    }

    public function testSearchSiswaSuccess()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];

        // gunakan nama siswa sebagai keyword search
        $this->get('api/siswa/kb?search=' . urlencode($siswa->nama), [
            'Authorization' => $admin->token,
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }

    public function testSearchSiswaFailed()
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

    public function testCreateSiswaTkSuccess()
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
            'Authorization' => $admin->token
        ])->assertStatus(201)
            ->assertJson([
                'errors' => []
            ]);
    }

    public function testCreateSiswaTkValidationFailed()
    {
        $scenario = $this->createSiswaTkScenario();
        $admin = $scenario['admin'];

        $this->post('api/siswa/tk', [], [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJsonStructure([
                'errors' => []
            ]);
    }

    public function testGetSiswaTkSuccess()
    {
        $scenario = $this->createSiswaTkScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];

        $this->get('api/siswa/tk/' . $siswa->id, [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }

    public function testGetSiswaTkNotFound()
    {
        $scenario = $this->createSiswaTkScenario();
        $admin = $scenario['admin'];

        $this->get('api/siswa/tk/999999', [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'siswa tidak ditemukan.'
                    ]
                ]
            ]);
    }

    public function testIndexSiswaTk()
    {
        $scenario = $this->createSiswaTkScenario();
        $admin = $scenario['admin'];
        // sudah ada minimal satu siswa TK dari skenario

        $this->get('api/siswa/tk', [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }

    // KB scenarios

    public function testCreateSiswaKbSuccess()
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
            'Authorization' => $admin->token
        ])->assertStatus(201)
            ->assertJson([
                'errors' => []
            ]);
    }

    public function testCreateSiswaKbValidationFailed()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];

        $this->post('api/siswa/kb', [], [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJsonStructure([
                'errors' => []
            ]);
    }

    public function testGetSiswaKbSuccess()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];
        $siswa = $scenario['siswa'];

        $this->get('api/siswa/kb/' . $siswa->id, [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson([
                'errors' => []
            ]);
    }

    public function testGetSiswaKbNotFound()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];

        $this->get('api/siswa/kb/999999', [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'siswa tidak ditemukan.'
                    ]
                ]
            ]);
    }

    public function testIndexSiswaKb()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];

        $this->get('api/siswa/kb', [
            'Authorization' => $admin->token
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }
    public function testUnathorized()
    {
        $scenario = $this->createSiswaKbScenario();
        $admin = $scenario['admin'];

        $this->get('api/siswa/kb', [
            'Authorization' => 'salah'
        ])->assertStatus(200)
            ->assertJson(['errors']);
    }

}
