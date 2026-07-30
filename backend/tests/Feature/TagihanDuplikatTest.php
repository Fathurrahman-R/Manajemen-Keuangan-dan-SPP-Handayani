<?php

namespace Tests\Feature;

use App\Models\JenisTagihan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Tests\TestCase;

/**
 * Jalur import menolak kombinasi NIS + jenis tagihan yang sudah ada di periode
 * aktif. Pembuatan massal lewat form harus menerapkan aturan yang sama —
 * kalau tidak, siswa bisa tertagih dua kali untuk hal yang sama.
 */
class TagihanDuplikatTest extends TestCase
{
    private User $admin;

    private Kelas $kelas;

    private Kategori $kategori;

    private JenisTagihan $jenisTagihan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\PermissionEndpointSeeder::class);

        $this->admin = User::factory()->admin()->create();
        $branchId = $this->admin->branch_id;

        $periode = TahunAjaran::factory()->aktif()->create(['branch_id' => $branchId]);
        $this->kelas = Kelas::factory()->create(['branch_id' => $branchId, 'jenjang' => 'MI', 'level' => 1]);
        $this->kategori = Kategori::factory()->create(['branch_id' => $branchId]);
        $this->jenisTagihan = JenisTagihan::factory()->create([
            'nama' => 'Seragam',
            'branch_id' => $branchId,
            'tahun_ajaran_id' => $periode->id,
        ]);

        Siswa::factory()->create([
            'nis' => '000001',
            'branch_id' => $branchId,
            'kelas_id' => $this->kelas->id,
            'kategori_id' => $this->kategori->id,
            'jenjang' => 'MI',
            'status' => 'Aktif',
        ]);
    }

    private function payload(): array
    {
        return [
            'jenis_tagihan_id' => $this->jenisTagihan->id,
            'kelas_id' => [$this->kelas->id],
            'kategori_id' => [$this->kategori->id],
            'jenjang' => 'MI',
        ];
    }

    public function test_creates_tagihan_on_first_run(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('api/tagihan', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('meta.created_count', 1)
            ->assertJsonPath('meta.skipped_count', 0);

        $this->assertDatabaseCount('tagihans', 1);
    }

    public function test_rejects_when_every_selected_student_already_has_the_tagihan(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('api/tagihan', $this->payload())
            ->assertStatus(201);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('api/tagihan', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.message.0',
                'Semua siswa terpilih sudah memiliki tagihan jenis ini pada periode tersebut.'
            );

        $this->assertDatabaseCount('tagihans', 1);
    }

    public function test_skips_only_the_students_that_already_have_it(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('api/tagihan', $this->payload())
            ->assertStatus(201);

        Siswa::factory()->create([
            'nis' => '000002',
            'branch_id' => $this->admin->branch_id,
            'kelas_id' => $this->kelas->id,
            'kategori_id' => $this->kategori->id,
            'jenjang' => 'MI',
            'status' => 'Aktif',
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('api/tagihan', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('meta.created_count', 1)
            ->assertJsonPath('meta.skipped_count', 1)
            ->assertJsonPath('meta.skipped_nis.0', '000001');

        $this->assertDatabaseCount('tagihans', 2);
    }
}
