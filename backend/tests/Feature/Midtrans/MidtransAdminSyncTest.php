<?php

namespace Tests\Feature\Midtrans;

use App\Models\Branch;
use App\Models\JenisTagihan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\MidtransTransaction;
use App\Models\PermissionEndpoint;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use App\Services\Midtrans\MidtransStatusSyncService;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class MidtransAdminSyncTest extends TestCase
{
    protected function tearDown(): void
    {
        // TestCase::setUp() deletes `tagihans` before the next test runs, but
        // doesn't know about `midtrans_transactions` (added after that cleanup
        // list was written) — its FK on kode_tagihan would block that delete.
        \Illuminate\Support\Facades\DB::table('midtrans_transactions')->delete();

        parent::tearDown();
    }

    public function test_unexpected_exception_during_sync_returns_error_code_instead_of_bare_500(): void
    {
        $branch = Branch::factory()->create();
        $admin = User::factory()->admin()->create(['branch_id' => $branch->id]);
        $tahunAjaran = TahunAjaran::factory()->aktif()->create(['branch_id' => $branch->id]);
        $jt = JenisTagihan::factory()->create(['branch_id' => $branch->id, 'tahun_ajaran_id' => $tahunAjaran->id]);
        $kelas = Kelas::factory()->create(['branch_id' => $branch->id]);
        $kategori = Kategori::factory()->create(['branch_id' => $branch->id]);
        $wali = Wali::factory()->create();
        $siswa = Siswa::factory()->create([
            'jenjang' => 'MI',
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
            'branch_id' => $branch->id,
        ]);
        $tagihan = Tagihan::factory()->create([
            'jenis_tagihan_id' => $jt->id,
            'nis' => $siswa->nis,
            'branch_id' => $branch->id,
        ]);

        // resource_key with no permission mapping yet → EndpointPermission
        // middleware allows the request through (see middleware comment:
        // "No permission bound yet → allow").
        PermissionEndpoint::updateOrCreate(
            ['resource_key' => 'midtrans.sync'],
            ['permission_id' => null, 'is_active' => true],
        );

        $trx = MidtransTransaction::create([
            'order_id' => 'HDY-'.$tagihan->kode_tagihan.'-1234567890',
            'kode_tagihan' => $tagihan->kode_tagihan,
            'nis' => $siswa->nis,
            'amount_paid' => 50000,
            'fee_amount' => 4000,
            'gross_amount' => 54000,
            'status' => 'pending',
            'expired_at' => now()->addHours(24),
            'branch_id' => $branch->id,
        ]);

        $service = Mockery::mock(MidtransStatusSyncService::class);
        $service->shouldReceive('syncManual')
            ->once()
            ->andThrow(new \RuntimeException('unexpected DB contention'));
        $this->app->instance(MidtransStatusSyncService::class, $service);

        Sanctum::actingAs($admin, $admin->getAllPermissions()->pluck('name')->toArray());

        $response = $this->postJson("/api/midtrans/admin/transactions/{$trx->order_id}/sync");

        $response->assertStatus(500)
            ->assertJson([
                'error_code' => 'SYNC_FAILED',
                'status' => 'pending',
            ]);

        // Never a bare, undiagnosable error response.
        $this->assertArrayHasKey('error_code', $response->json());
    }

    /**
     * Regression for MID-001: index()/show()/logs() previously only scoped by
     * branch_id when the caller explicitly passed a `branch_id` query param —
     * an admin viewing without that param (the normal case) saw every
     * branch's transactions, and any admin could view any other branch's
     * transaction detail/logs directly by order_id.
     */
    public function test_admin_cannot_see_other_branch_transactions(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $adminA = User::factory()->admin()->create(['branch_id' => $branchA->id]);

        PermissionEndpoint::updateOrCreate(
            ['resource_key' => 'midtrans.admin'],
            ['permission_id' => null, 'is_active' => true],
        );

        $tagihanA = $this->makeTagihanForBranch($branchA);
        $tagihanB = $this->makeTagihanForBranch($branchB);

        $trxA = MidtransTransaction::create([
            'order_id' => 'HDY-'.$tagihanA['tagihan']->kode_tagihan.'-1111111111',
            'kode_tagihan' => $tagihanA['tagihan']->kode_tagihan,
            'nis' => $tagihanA['siswa']->nis,
            'amount_paid' => 50000,
            'fee_amount' => 4000,
            'gross_amount' => 54000,
            'status' => 'pending',
            'expired_at' => now()->addHours(24),
            'branch_id' => $branchA->id,
        ]);

        $trxB = MidtransTransaction::create([
            'order_id' => 'HDY-'.$tagihanB['tagihan']->kode_tagihan.'-2222222222',
            'kode_tagihan' => $tagihanB['tagihan']->kode_tagihan,
            'nis' => $tagihanB['siswa']->nis,
            'amount_paid' => 75000,
            'fee_amount' => 4000,
            'gross_amount' => 79000,
            'status' => 'pending',
            'expired_at' => now()->addHours(24),
            'branch_id' => $branchB->id,
        ]);

        Sanctum::actingAs($adminA, $adminA->getAllPermissions()->pluck('name')->toArray());

        // index() must not leak branch B's transaction even with no filter applied.
        $list = $this->getJson('/api/midtrans/admin/transactions')->assertOk()->json('data');
        $orderIds = collect($list)->pluck('order_id');
        $this->assertTrue($orderIds->contains($trxA->order_id));
        $this->assertFalse($orderIds->contains($trxB->order_id));

        // show() on another branch's order_id must 404, not leak the record.
        $this->getJson("/api/midtrans/admin/transactions/{$trxB->order_id}")->assertNotFound();
    }

    private function makeTagihanForBranch(Branch $branch): array
    {
        $tahunAjaran = TahunAjaran::factory()->aktif()->create(['branch_id' => $branch->id]);
        $jt = JenisTagihan::factory()->create(['branch_id' => $branch->id, 'tahun_ajaran_id' => $tahunAjaran->id]);
        $kelas = Kelas::factory()->create(['branch_id' => $branch->id]);
        $kategori = Kategori::factory()->create(['branch_id' => $branch->id]);
        $wali = Wali::factory()->create();
        $siswa = Siswa::factory()->create([
            'jenjang' => 'MI',
            'wali_id' => $wali->id,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
            'branch_id' => $branch->id,
        ]);
        $tagihan = Tagihan::factory()->create([
            'jenis_tagihan_id' => $jt->id,
            'nis' => $siswa->nis,
            'branch_id' => $branch->id,
        ]);

        return compact('siswa', 'tagihan');
    }
}
