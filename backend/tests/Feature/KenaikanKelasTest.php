<?php

namespace Tests\Feature;

use App\Models\BatchPromosi;
use App\Models\Branch;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Tests\TestCase;

class KenaikanKelasTest extends TestCase
{
    private User $admin;

    private int $branchId;

    private Kategori $kategori;

    private TahunAjaran $periodeAktif;

    private TahunAjaran $periodeTujuan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\PermissionEndpointSeeder::class);

        $this->admin = User::factory()->admin()->create();
        $this->branchId = $this->admin->branch_id;

        $this->kategori = Kategori::factory()->create(['branch_id' => $this->branchId]);

        $this->periodeAktif = TahunAjaran::factory()->aktif()->create([
            'branch_id' => $this->branchId,
            'nama' => '2025/2026',
        ]);
        $this->periodeTujuan = TahunAjaran::factory()->create([
            'branch_id' => $this->branchId,
            'status' => 'Non-Aktif',
            'nama' => '2026/2027',
        ]);
    }

    private function kelas(string $jenjang, int $level, string $nama): Kelas
    {
        return Kelas::factory()->create([
            'branch_id' => $this->branchId,
            'jenjang' => $jenjang,
            'level' => $level,
            'nama' => $nama,
        ]);
    }

    /**
     * Creates a student placed in $kelas for the active period — the shape
     * KenaikanKelasService::getEligibleStudents() actually looks for.
     */
    private function siswaDi(Kelas $kelas, array $overrides = []): Siswa
    {
        $siswa = Siswa::factory()->create(array_merge([
            'branch_id' => $this->branchId,
            'kelas_id' => $kelas->id,
            'kategori_id' => $this->kategori->id,
            'jenjang' => $kelas->jenjang,
            'status' => 'Aktif',
        ], $overrides));

        SiswaKelas::factory()->create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelas->id,
            'tahun_ajaran_id' => $this->periodeAktif->id,
        ]);

        return $siswa;
    }

    private function api()
    {
        return $this->actingAs($this->admin, 'sanctum');
    }

    // ==================== BULK PROMOTION ====================

    public function test_bulk_promotion_moves_students_to_next_level_kelas(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $kelas2 = $this->kelas('MI', 2, 'Kelas 2');

        $siswaA = $this->siswaDi($kelas1);
        $siswaB = $this->siswaDi($kelas1);

        $response = $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', [
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertOk();

        $response->assertJsonPath('data.total_success', 2);
        $response->assertJsonPath('data.total_skipped', 0);

        foreach ([$siswaA, $siswaB] as $siswa) {
            $this->assertDatabaseHas('siswa_kelas', [
                'siswa_id' => $siswa->id,
                'kelas_id' => $kelas2->id,
                'tahun_ajaran_id' => $this->periodeTujuan->id,
            ]);
        }

        $this->assertDatabaseHas('batch_promosis', [
            'id' => $response->json('data.batch_id'),
            'batch_type' => 'bulk_promotion',
            'status' => 'completed',
            'branch_id' => $this->branchId,
        ]);
        $this->assertDatabaseCount('batch_promosi_details', 2);
    }

    /**
     * The target period isn't active yet, so siswas.kelas_id must stay pointing
     * at the current class — otherwise the whole app would show students in
     * next year's class a year early.
     */
    public function test_bulk_promotion_does_not_touch_siswa_kelas_id_when_target_period_inactive(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $this->kelas('MI', 2, 'Kelas 2');
        $siswa = $this->siswaDi($kelas1);

        $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', [
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertOk();

        $this->assertEquals($kelas1->id, $siswa->fresh()->kelas_id);
    }

    public function test_bulk_promotion_rejects_same_source_and_target_period(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $this->kelas('MI', 2, 'Kelas 2');
        $this->siswaDi($kelas1);

        $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', [
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeAktif->id,
        ])->assertStatus(422);
    }

    public function test_bulk_promotion_rejects_highest_kelas(): void
    {
        $kelas6 = $this->kelas('MI', 6, 'Kelas 6');
        $this->siswaDi($kelas6);

        $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', [
            'kelas_id' => $kelas6->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertStatus(422)
            ->assertJsonPath('errors.kelas_id.0', 'Tidak ada kelas berikutnya dalam hierarki. Siswa berada di kelas tertinggi, gunakan kelulusan atau pindah jenjang.');
    }

    public function test_bulk_promotion_rejects_kelas_from_other_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        $foreignKelas = Kelas::factory()->create([
            'branch_id' => $otherBranch->id,
            'jenjang' => 'MI',
            'level' => 1,
            'nama' => 'Kelas 1',
        ]);

        $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', [
            'kelas_id' => $foreignKelas->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertStatus(422)
            ->assertJsonPath('errors.kelas_id.0', 'Kelas tidak ditemukan atau bukan milik branch Anda.');
    }

    /**
     * Re-running the same promotion must not double-place a student; the
     * second run should report them as skipped rather than erroring out.
     */
    public function test_bulk_promotion_skips_students_already_placed_in_target_period(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $this->kelas('MI', 2, 'Kelas 2');
        $this->siswaDi($kelas1);

        $payload = [
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ];

        $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', $payload)->assertOk();

        $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', $payload)
            ->assertOk()
            ->assertJsonPath('data.total_success', 0)
            ->assertJsonPath('data.total_skipped', 1)
            ->assertJsonPath('data.skipped.0.reason', 'Sudah memiliki penempatan kelas untuk periode tujuan');
    }

    public function test_bulk_promotion_rejects_kelas_without_eligible_students(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $this->kelas('MI', 2, 'Kelas 2');

        $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', [
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertStatus(422);
    }

    // ==================== RETENTION ====================

    public function test_retention_keeps_student_in_same_kelas_for_target_period(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $this->kelas('MI', 2, 'Kelas 2');
        $siswa = $this->siswaDi($kelas1);

        $this->api()->postJson('api/kenaikan-kelas/retention', [
            'siswa_ids' => [$siswa->id],
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertOk()
            ->assertJsonPath('data.total_success', 1);

        $this->assertDatabaseHas('siswa_kelas', [
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ]);
        $this->assertDatabaseHas('batch_promosis', [
            'batch_type' => 'tinggal_kelas',
            'branch_id' => $this->branchId,
        ]);
    }

    public function test_retention_skips_student_without_placement_in_source_period(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');

        // Deliberately no SiswaKelas row for the active period.
        $siswa = Siswa::factory()->create([
            'branch_id' => $this->branchId,
            'kelas_id' => $kelas1->id,
            'kategori_id' => $this->kategori->id,
            'jenjang' => 'MI',
            'status' => 'Aktif',
        ]);

        $this->api()->postJson('api/kenaikan-kelas/retention', [
            'siswa_ids' => [$siswa->id],
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertOk()
            ->assertJsonPath('data.total_success', 0)
            ->assertJsonPath('data.total_skipped', 1)
            ->assertJsonPath('data.skipped.0.reason', 'Tidak memiliki kelas di periode sumber');
    }

    public function test_retention_skips_student_from_other_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        $otherKategori = Kategori::factory()->create(['branch_id' => $otherBranch->id]);
        $foreignKelas = Kelas::factory()->create([
            'branch_id' => $otherBranch->id,
            'jenjang' => 'MI',
            'level' => 1,
            'nama' => 'Kelas 1',
        ]);
        $foreignSiswa = Siswa::factory()->create([
            'branch_id' => $otherBranch->id,
            'kelas_id' => $foreignKelas->id,
            'kategori_id' => $otherKategori->id,
            'jenjang' => 'MI',
            'status' => 'Aktif',
        ]);

        $this->api()->postJson('api/kenaikan-kelas/retention', [
            'siswa_ids' => [$foreignSiswa->id],
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertOk()
            ->assertJsonPath('data.total_skipped', 1)
            ->assertJsonPath('data.skipped.0.reason', 'Siswa tidak ditemukan atau bukan milik branch Anda');
    }

    // ==================== GRADUATION ====================

    public function test_graduation_marks_highest_kelas_students_as_lulus(): void
    {
        $kelas6 = $this->kelas('MI', 6, 'Kelas 6');
        $siswa = $this->siswaDi($kelas6);

        $this->api()->postJson('api/kenaikan-kelas/graduation', [
            'siswa_ids' => [$siswa->id],
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertOk()
            ->assertJsonPath('data.total_graduated', 1);

        $siswa->refresh();
        $this->assertEquals('Lulus', $siswa->status);
        $this->assertNull($siswa->kelas_id);

        // Graduates must not be placed into the next period.
        $this->assertDatabaseMissing('siswa_kelas', [
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ]);
    }

    public function test_graduation_skips_student_not_in_highest_kelas(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $this->kelas('MI', 2, 'Kelas 2');
        $siswa = $this->siswaDi($kelas1);

        $this->api()->postJson('api/kenaikan-kelas/graduation', [
            'siswa_ids' => [$siswa->id],
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertOk()
            ->assertJsonPath('data.total_graduated', 0)
            ->assertJsonPath('data.skipped.0.reason', 'bukan kelas tertinggi');

        $this->assertEquals('Aktif', $siswa->fresh()->status);
    }

    // ==================== CROSS-LEVEL TRANSFER ====================

    public function test_cross_level_transfer_moves_graduated_tk_student_to_mi(): void
    {
        $tkB = $this->kelas('TK', 2, 'TK B');
        $mi1 = $this->kelas('MI', 1, 'Kelas 1');

        $siswa = $this->siswaDi($tkB, ['jenjang' => 'TK']);
        $siswa->update(['status' => 'Lulus', 'kelas_id' => null]);

        $this->api()->postJson('api/kenaikan-kelas/cross-level-transfer', [
            'siswa_id' => $siswa->id,
            'target_kelas_id' => $mi1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertOk()
            ->assertJsonPath('data.previous_jenjang', 'TK')
            ->assertJsonPath('data.new_jenjang', 'MI');

        $siswa->refresh();
        $this->assertEquals('MI', $siswa->jenjang);
        $this->assertEquals('Aktif', $siswa->status);

        $this->assertDatabaseHas('siswa_kelas', [
            'siswa_id' => $siswa->id,
            'kelas_id' => $mi1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ]);
    }

    public function test_cross_level_transfer_rejects_non_graduated_student(): void
    {
        $tkB = $this->kelas('TK', 2, 'TK B');
        $mi1 = $this->kelas('MI', 1, 'Kelas 1');
        $siswa = $this->siswaDi($tkB, ['jenjang' => 'TK']);

        $this->api()->postJson('api/kenaikan-kelas/cross-level-transfer', [
            'siswa_id' => $siswa->id,
            'target_kelas_id' => $mi1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertStatus(422);
    }

    /**
     * Only KB→TK and TK→MI are allowed; KB→MI skips a jenjang and must fail.
     */
    public function test_cross_level_transfer_rejects_disallowed_jenjang_jump(): void
    {
        $kb = $this->kelas('KB', 1, 'KB');
        $mi1 = $this->kelas('MI', 1, 'Kelas 1');

        $siswa = $this->siswaDi($kb, ['jenjang' => 'KB']);
        $siswa->update(['status' => 'Lulus', 'kelas_id' => null]);

        $this->api()->postJson('api/kenaikan-kelas/cross-level-transfer', [
            'siswa_id' => $siswa->id,
            'target_kelas_id' => $mi1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Transisi jenjang tidak diperbolehkan');
    }

    // ==================== UNDO ====================

    public function test_undo_reverses_bulk_promotion(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $this->kelas('MI', 2, 'Kelas 2');
        $siswa = $this->siswaDi($kelas1);

        $batchId = $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', [
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertOk()->json('data.batch_id');

        $this->api()->postJson("api/kenaikan-kelas/{$batchId}/undo")
            ->assertOk()
            ->assertJsonPath('data.total_restored', 1);

        $this->assertDatabaseMissing('siswa_kelas', [
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ]);
        $this->assertDatabaseHas('batch_promosis', ['id' => $batchId, 'status' => 'undone']);
        $this->assertEquals($kelas1->id, $siswa->fresh()->kelas_id);
    }

    public function test_undo_restores_graduated_student_status(): void
    {
        $kelas6 = $this->kelas('MI', 6, 'Kelas 6');
        $siswa = $this->siswaDi($kelas6);

        $batchId = $this->api()->postJson('api/kenaikan-kelas/graduation', [
            'siswa_ids' => [$siswa->id],
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertOk()->json('data.batch_id');

        $this->api()->postJson("api/kenaikan-kelas/{$batchId}/undo")->assertOk();

        $siswa->refresh();
        $this->assertEquals('Aktif', $siswa->status);
        $this->assertEquals($kelas6->id, $siswa->kelas_id);
    }

    public function test_undo_rejects_already_undone_batch(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $this->kelas('MI', 2, 'Kelas 2');
        $this->siswaDi($kelas1);

        $batchId = $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', [
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->json('data.batch_id');

        $this->api()->postJson("api/kenaikan-kelas/{$batchId}/undo")->assertOk();

        $this->api()->postJson("api/kenaikan-kelas/{$batchId}/undo")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Batch sudah dibatalkan sebelumnya.');
    }

    /**
     * If someone manually re-assigned the student after the batch ran, undo
     * must leave that manual placement alone instead of silently deleting it.
     */
    public function test_undo_skips_student_whose_placement_was_changed_afterwards(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $this->kelas('MI', 2, 'Kelas 2');
        $kelas3 = $this->kelas('MI', 3, 'Kelas 3');
        $siswa = $this->siswaDi($kelas1);

        $batchId = $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', [
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->json('data.batch_id');

        SiswaKelas::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $this->periodeTujuan->id)
            ->update(['kelas_id' => $kelas3->id]);

        $this->api()->postJson("api/kenaikan-kelas/{$batchId}/undo")
            ->assertOk()
            ->assertJsonPath('data.total_restored', 0)
            ->assertJsonPath('data.total_skipped', 1)
            ->assertJsonPath('data.skipped.0.reason', 'penempatan kelas sudah diubah');

        $this->assertDatabaseHas('siswa_kelas', [
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelas3->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ]);
    }

    public function test_undo_rejects_batch_from_other_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        $batch = BatchPromosi::factory()->create([
            'branch_id' => $otherBranch->id,
            'status' => 'completed',
            // Factory-nya membuat User baru untuk processed_by, dan UserFactory
            // memakai username tetap 'admin' yang bentrok dengan admin di setUp().
            'processed_by' => User::factory()->create(['username' => 'admin_cabang_lain'])->id,
        ]);

        $this->api()->postJson("api/kenaikan-kelas/{$batch->id}/undo")
            ->assertStatus(404);
    }

    // ==================== READ ENDPOINTS ====================

    public function test_eligible_students_returns_only_active_students_in_kelas(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $aktif = $this->siswaDi($kelas1);
        $lulus = $this->siswaDi($kelas1, ['status' => 'Lulus']);

        $response = $this->api()->getJson(
            'api/kenaikan-kelas/eligible-students?kelas_id='.$kelas1->id.'&tahun_ajaran_id='.$this->periodeAktif->id
        )->assertOk();

        // Lulusan sengaja ikut ditampilkan: pindah jenjang mensyaratkan status
        // "Lulus", jadi menyembunyikan mereka membuat aksi itu tak terjangkau UI.
        $ids = array_column($response->json('data'), 'id');
        sort($ids);
        $expected = [$aktif->id, $lulus->id];
        sort($expected);

        $this->assertEquals($expected, $ids);
    }

    /**
     * Lulusan boleh terlihat di daftar, tapi tidak boleh ikut terpromosikan.
     */
    public function test_bulk_promotion_ignores_graduated_students(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $kelas2 = $this->kelas('MI', 2, 'Kelas 2');

        $aktif = $this->siswaDi($kelas1);
        $lulus = $this->siswaDi($kelas1, ['status' => 'Lulus']);

        $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', [
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertOk()
            ->assertJsonPath('data.total_success', 1);

        $this->assertDatabaseHas('siswa_kelas', [
            'siswa_id' => $aktif->id,
            'kelas_id' => $kelas2->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ]);
        $this->assertDatabaseMissing('siswa_kelas', [
            'siswa_id' => $lulus->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ]);
    }

    /**
     * Regresi: siswa yang ditandai tinggal kelas ikut terpromosikan karena
     * endpoint bulk-promotion mengabaikan pilihan per-siswa. Akibatnya batch
     * mencatat mereka sebagai naik_kelas dan undo jadi salah.
     */
    public function test_bulk_promotion_only_promotes_the_given_students(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $kelas2 = $this->kelas('MI', 2, 'Kelas 2');

        $naik = $this->siswaDi($kelas1);
        $tinggal = $this->siswaDi($kelas1);

        $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', [
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
            'siswa_ids' => [$naik->id],
        ])->assertOk()
            ->assertJsonPath('data.total_success', 1);

        $this->assertDatabaseHas('siswa_kelas', [
            'siswa_id' => $naik->id,
            'kelas_id' => $kelas2->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ]);
        $this->assertDatabaseMissing('batch_promosi_details', [
            'siswa_id' => $tinggal->id,
            'action' => 'naik_kelas',
        ]);
    }

    /**
     * Batch yang tidak mengubah apa pun tidak boleh menyisakan baris kosong
     * di Riwayat Proses.
     */
    public function test_bulk_promotion_does_not_record_batch_when_nothing_changed(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $this->kelas('MI', 2, 'Kelas 2');
        $this->siswaDi($kelas1);

        $payload = [
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ];

        $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', $payload)->assertOk();

        $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', $payload)
            ->assertOk()
            ->assertJsonPath('data.total_success', 0)
            ->assertJsonPath('data.batch_id', null);

        $this->assertDatabaseCount('batch_promosis', 1);
    }

    public function test_eligible_students_requires_both_query_parameters(): void
    {
        $this->api()->getJson('api/kenaikan-kelas/eligible-students')
            ->assertStatus(422);
    }

    public function test_class_hierarchy_is_ordered_by_level(): void
    {
        $this->kelas('MI', 3, 'Kelas 3');
        $this->kelas('MI', 1, 'Kelas 1');
        $this->kelas('MI', 2, 'Kelas 2');

        $response = $this->api()->getJson('api/kenaikan-kelas/class-hierarchy?jenjang=MI')
            ->assertOk();

        $levels = array_column($response->json('data'), 'level');
        $this->assertEquals([1, 2, 3], $levels);
    }

    public function test_list_batches_is_scoped_to_own_branch(): void
    {
        $kelas1 = $this->kelas('MI', 1, 'Kelas 1');
        $this->kelas('MI', 2, 'Kelas 2');
        $this->siswaDi($kelas1);

        $this->api()->postJson('api/kenaikan-kelas/bulk-promotion', [
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $this->periodeTujuan->id,
        ])->assertOk();

        BatchPromosi::factory()->create([
            'branch_id' => Branch::factory()->create()->id,
            'status' => 'completed',
            'processed_by' => User::factory()->create(['username' => 'admin_cabang_lain'])->id,
        ]);

        $response = $this->api()->getJson('api/kenaikan-kelas/batches')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->branchId, $response->json('data.0.branch_id'));
    }
}
