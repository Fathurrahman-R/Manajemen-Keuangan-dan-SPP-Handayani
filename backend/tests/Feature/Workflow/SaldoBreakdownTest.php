<?php

namespace Tests\Feature\Workflow;

use App\Models\Branch;
use App\Models\JenisTagihan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Models\PengeluaranRequest;
use App\Models\PermissionEndpoint;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use App\Services\WorkflowService;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SaldoBreakdownTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        PermissionEndpoint::updateOrCreate(
            ['resource_key' => 'pengeluaran.view'],
            ['permission_id' => null, 'is_active' => true],
        );

        // submit() -> notifyApprovers() looks up users via a Spatie
        // ->permission('approve-pengeluaran') scope, which throws if the
        // permission row doesn't exist at all (not just "nobody has it").
        Permission::firstOrCreate(['name' => 'approve-pengeluaran', 'guard_name' => 'web']);
    }

    protected function tearDown(): void
    {
        // FK order matters: jenis_tagihans/tagihans reference tahun_ajarans,
        // so those must go first or the tahun_ajarans delete violates the
        // constraint (base TestCase::setUp() cleans jenis_tagihans/tagihans
        // for the NEXT test, but that's too late for tahun_ajarans here).
        \Illuminate\Support\Facades\DB::table('pengeluaran_requests')->delete();
        \Illuminate\Support\Facades\DB::table('pengeluarans')->delete();
        \Illuminate\Support\Facades\DB::table('pembayarans')->delete();
        \Illuminate\Support\Facades\DB::table('tagihans')->delete();
        \Illuminate\Support\Facades\DB::table('jenis_tagihans')->delete();
        \Illuminate\Support\Facades\DB::table('tahun_ajarans')->delete();

        parent::tearDown();
    }

    /**
     * Regression: WorkflowService::getSaldoBreakdown() (and by extension
     * assertSaldoMencukupi()) must sum pemasukan/pengeluaran across EVERY
     * tahun ajaran in the branch, not just the currently active/filtered
     * one. Confirmed against code + git history that this was already the
     * case (no tahun_ajaran_id filter was ever present) — this test locks
     * that behavior so it can't regress into a period-scoped calculation.
     */
    public function test_saldo_breakdown_sums_across_multiple_tahun_ajaran(): void
    {
        $branch = Branch::factory()->create();

        $taOld = TahunAjaran::factory()->for($branch)->create([
            'nama' => '2020/2021', 'tanggal_mulai' => '2020-07-01', 'tanggal_selesai' => '2021-06-30', 'status' => 'Non-Aktif',
        ]);
        $taNew = TahunAjaran::factory()->for($branch)->create([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'status' => 'Aktif',
        ]);

        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create(['branch_id' => $branch->id]);
        $kategori = Kategori::factory()->create(['branch_id' => $branch->id]);
        $jt = JenisTagihan::factory()->create(['branch_id' => $branch->id, 'tahun_ajaran_id' => $taOld->id, 'jumlah' => 500000]);

        $siswa = Siswa::factory()->create([
            'branch_id' => $branch->id,
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
            'jenjang' => 'MI',
        ]);

        // Pemasukan di 2 periode berbeda — total harus 300rb (100rb + 200rb).
        $tagihanOld = Tagihan::factory()->for($siswa, 'siswa')->for($jt, 'jenis_tagihan')->create([
            'branch_id' => $branch->id,
            'tahun_ajaran_id' => $taOld->id,
        ]);
        Pembayaran::factory()->for($tagihanOld, 'tagihan')->create([
            'branch_id' => $branch->id,
            'jumlah' => 100000,
        ]);

        $tagihanNew = Tagihan::factory()->for($siswa, 'siswa')->for($jt, 'jenis_tagihan')->create([
            'branch_id' => $branch->id,
            'tahun_ajaran_id' => $taNew->id,
        ]);
        Pembayaran::factory()->for($tagihanNew, 'tagihan')->create([
            'branch_id' => $branch->id,
            'jumlah' => 200000,
        ]);

        // Pengeluaran terealisasi di 2 periode berbeda — total harus 50rb (20rb + 30rb).
        Pengeluaran::factory()->create([
            'branch_id' => $branch->id,
            'tahun_ajaran_id' => $taOld->id,
            'jumlah' => 20000,
        ]);
        Pengeluaran::factory()->create([
            'branch_id' => $branch->id,
            'tahun_ajaran_id' => $taNew->id,
            'jumlah' => 30000,
        ]);

        $breakdown = app(WorkflowService::class)->getSaldoBreakdown($branch->id);

        // total_saldo_cabang = (100rb + 200rb) - (20rb + 30rb) = 250rb, lintas kedua periode.
        $this->assertSame(250000.0, $breakdown['total_saldo_cabang']);
        $this->assertSame(0.0, $breakdown['total_outstanding']);
        $this->assertSame(250000.0, $breakdown['saldo_tersedia']);
    }

    public function test_saldo_breakdown_subtracts_outstanding_requests(): void
    {
        $branch = Branch::factory()->create();
        $ta = TahunAjaran::factory()->for($branch)->create();
        $requester = User::factory()->admin()->create(['branch_id' => $branch->id]);

        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create(['branch_id' => $branch->id]);
        $kategori = Kategori::factory()->create(['branch_id' => $branch->id]);
        $jt = JenisTagihan::factory()->create(['branch_id' => $branch->id, 'tahun_ajaran_id' => $ta->id, 'jumlah' => 500000]);
        $siswa = Siswa::factory()->create([
            'branch_id' => $branch->id,
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
            'jenjang' => 'MI',
        ]);
        $tagihan = Tagihan::factory()->for($siswa, 'siswa')->for($jt, 'jenis_tagihan')->create(['branch_id' => $branch->id]);
        Pembayaran::factory()->for($tagihan, 'tagihan')->create(['branch_id' => $branch->id, 'jumlah' => 500000]);

        PengeluaranRequest::create([
            'uraian' => 'Submitted request', 'jumlah' => 150000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'submitted', 'requester_id' => $requester->id, 'branch_id' => $branch->id,
        ]);
        PengeluaranRequest::create([
            'uraian' => 'Approved request', 'jumlah' => 100000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'approved', 'requester_id' => $requester->id, 'branch_id' => $branch->id,
        ]);
        // Draft/rejected/disbursed must NOT count as outstanding.
        PengeluaranRequest::create([
            'uraian' => 'Draft request', 'jumlah' => 999999,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'draft', 'requester_id' => $requester->id, 'branch_id' => $branch->id,
        ]);

        $breakdown = app(WorkflowService::class)->getSaldoBreakdown($branch->id);

        $this->assertSame(500000.0, $breakdown['total_saldo_cabang']);
        $this->assertSame(250000.0, $breakdown['total_outstanding']);
        $this->assertSame(250000.0, $breakdown['saldo_tersedia']);
    }

    public function test_stats_endpoint_is_scoped_per_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $taB = TahunAjaran::factory()->for($branchB)->create();
        $userA = User::factory()->admin()->create(['branch_id' => $branchA->id]);

        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create(['branch_id' => $branchB->id]);
        $kategori = Kategori::factory()->create(['branch_id' => $branchB->id]);
        $jt = JenisTagihan::factory()->create(['branch_id' => $branchB->id, 'tahun_ajaran_id' => $taB->id, 'jumlah' => 500000]);
        $siswaB = Siswa::factory()->create([
            'branch_id' => $branchB->id,
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
            'jenjang' => 'MI',
        ]);
        $tagihanB = Tagihan::factory()->for($siswaB, 'siswa')->for($jt, 'jenis_tagihan')->create(['branch_id' => $branchB->id]);
        Pembayaran::factory()->for($tagihanB, 'tagihan')->create(['branch_id' => $branchB->id, 'jumlah' => 9000000]);

        Sanctum::actingAs($userA, $userA->getAllPermissions()->pluck('name')->toArray());

        $response = $this->getJson('/api/pengeluaran-request/stats')->assertOk();

        // Branch A has no payments at all — branch B's saldo must not leak in.
        $response->assertJsonPath('data.total_saldo_cabang', 0);
    }

    /**
     * End-to-end proof that WorkflowService::submit() itself (not just the
     * calculation in isolation) enforces saldo across every tahun ajaran.
     * The active period here has zero pemasukan of its own; if the check
     * were wrongly scoped to only the active/filtered period, this submit
     * would be blocked. Because it draws on the branch's older period too,
     * it must succeed.
     */
    public function test_submit_allows_amount_covered_by_an_older_periods_pemasukan(): void
    {
        $branch = Branch::factory()->create();

        $taOld = TahunAjaran::factory()->for($branch)->create([
            'nama' => '2020/2021', 'tanggal_mulai' => '2020-07-01', 'tanggal_selesai' => '2021-06-30', 'status' => 'Non-Aktif',
        ]);
        $taActive = TahunAjaran::factory()->for($branch)->create([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'status' => 'Aktif',
        ]);

        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create(['branch_id' => $branch->id]);
        $kategori = Kategori::factory()->create(['branch_id' => $branch->id]);
        $jt = JenisTagihan::factory()->create(['branch_id' => $branch->id, 'tahun_ajaran_id' => $taOld->id, 'jumlah' => 500000]);
        $siswa = Siswa::factory()->create([
            'branch_id' => $branch->id,
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
            'jenjang' => 'MI',
        ]);

        // Pemasukan only exists in the OLD, non-active period.
        $tagihanOld = Tagihan::factory()->for($siswa, 'siswa')->for($jt, 'jenis_tagihan')->create([
            'branch_id' => $branch->id,
            'tahun_ajaran_id' => $taOld->id,
        ]);
        Pembayaran::factory()->for($tagihanOld, 'tagihan')->create([
            'branch_id' => $branch->id,
            'jumlah' => 300000,
        ]);

        $requester = User::factory()->admin()->create(['branch_id' => $branch->id]);

        // tanggal_kebutuhan falls inside the ACTIVE period, which has 0 pemasukan of its own.
        $request = PengeluaranRequest::create([
            'uraian' => 'Beli ATK', 'jumlah' => 250000,
            'tanggal_kebutuhan' => '2026-08-01',
            'status' => 'draft', 'requester_id' => $requester->id, 'branch_id' => $branch->id,
        ]);

        $result = app(WorkflowService::class)->submit($request, $requester);

        $this->assertSame('submitted', $result->status);
    }

    public function test_submit_blocked_when_amount_exceeds_branch_wide_saldo(): void
    {
        $branch = Branch::factory()->create();
        $ta = TahunAjaran::factory()->for($branch)->create(['status' => 'Aktif']);
        $requester = User::factory()->admin()->create(['branch_id' => $branch->id]);

        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create(['branch_id' => $branch->id]);
        $kategori = Kategori::factory()->create(['branch_id' => $branch->id]);
        $jt = JenisTagihan::factory()->create(['branch_id' => $branch->id, 'tahun_ajaran_id' => $ta->id, 'jumlah' => 100000]);
        $siswa = Siswa::factory()->create([
            'branch_id' => $branch->id,
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
            'jenjang' => 'MI',
        ]);
        $tagihan = Tagihan::factory()->for($siswa, 'siswa')->for($jt, 'jenis_tagihan')->create(['branch_id' => $branch->id, 'tahun_ajaran_id' => $ta->id]);
        Pembayaran::factory()->for($tagihan, 'tagihan')->create(['branch_id' => $branch->id, 'jumlah' => 100000]);

        $request = PengeluaranRequest::create([
            'uraian' => 'Melebihi saldo', 'jumlah' => 500000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'draft', 'requester_id' => $requester->id, 'branch_id' => $branch->id,
        ]);

        $this->expectException(ValidationException::class);

        app(WorkflowService::class)->submit($request, $requester);
    }
}
