<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Tests\TestCase;

class TahunAjaranActivateTest extends TestCase
{
    public function test_activate_resyncs_siswa_kelas_id_from_target_period_placement()
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\PermissionEndpointSeeder::class);

        $admin = User::factory()->admin()->create();
        $branchId = $admin->branch_id;

        $sourceKelas = Kelas::factory()->create(['branch_id' => $branchId, 'jenjang' => 'MI', 'level' => 3]);
        $targetKelas = Kelas::factory()->create(['branch_id' => $branchId, 'jenjang' => 'MI', 'level' => 4]);

        $sourcePeriod = TahunAjaran::factory()->aktif()->create(['branch_id' => $branchId, 'nama' => '2025/2026']);
        $targetPeriod = TahunAjaran::factory()->create(['branch_id' => $branchId, 'status' => 'Non-Aktif', 'nama' => '2026/2027']);

        $kategori = Kategori::factory()->create(['branch_id' => $branchId]);

        $siswa = Siswa::factory()->create([
            'branch_id' => $branchId,
            'kelas_id' => $sourceKelas->id,
            'kategori_id' => $kategori->id,
        ]);

        // Simulates KenaikanKelasService promoting the student to a future (not-yet-active) period:
        // a SiswaKelas placement is created for the target period, but siswa.kelas_id is left
        // untouched because the target period wasn't active at promotion time.
        SiswaKelas::factory()->create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $targetKelas->id,
            'tahun_ajaran_id' => $targetPeriod->id,
        ]);

        $this->assertEquals($sourceKelas->id, $siswa->fresh()->kelas_id);

        $this->actingAs($admin, 'sanctum')
            ->patch("api/tahun-ajaran/{$targetPeriod->id}/activate", [], ['Authorization' => 'test'])
            ->assertStatus(200);

        $this->assertEquals($targetKelas->id, $siswa->fresh()->kelas_id);
    }
}
