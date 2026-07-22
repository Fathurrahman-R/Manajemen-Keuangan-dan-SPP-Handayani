<?php

namespace Tests\Feature\Workflow;

use App\Models\Branch;
use App\Models\PengeluaranRequest;
use App\Models\PermissionEndpoint;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Regression coverage for TC-WF-005: approve harus ditolak kalau status
 * request bukan 'submitted'. UI tidak menampilkan tombol approve untuk
 * request draft, tapi endpoint API harus tetap menolaknya sendiri kalau
 * dipanggil langsung (mis. lewat curl/Postman) — jangan cuma andalkan UI.
 */
class PengeluaranApprovalTransitionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $permission = Permission::firstOrCreate(['name' => 'approve-pengeluaran', 'guard_name' => 'web']);
        PermissionEndpoint::updateOrCreate(
            ['resource_key' => 'pengeluaran.approve'],
            ['permission_id' => $permission->id, 'is_active' => true],
        );
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\DB::table('pengeluaran_requests')->delete();

        parent::tearDown();
    }

    private function actingAsApprover(): User
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->admin()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo('approve-pengeluaran');
        Sanctum::actingAs($user, $user->getAllPermissions()->pluck('name')->toArray());

        return $user;
    }

    public function test_approve_endpoint_rejects_a_request_that_is_still_draft(): void
    {
        $approver = $this->actingAsApprover();
        $requester = User::factory()->admin()->create(['branch_id' => $approver->branch_id, 'username' => 'requester-'.uniqid()]);

        $request = PengeluaranRequest::create([
            'uraian' => 'Beli ATK',
            'jumlah' => 100000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'draft',
            'requester_id' => $requester->id,
            'branch_id' => $approver->branch_id,
        ]);

        $this->postJson("/api/pengeluaran-request/{$request->id}/approve")
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('pengeluaran_requests', ['id' => $request->id, 'status' => 'draft']);
    }

    public function test_approve_endpoint_rejects_a_request_that_is_already_approved(): void
    {
        $approver = $this->actingAsApprover();
        $requester = User::factory()->admin()->create(['branch_id' => $approver->branch_id, 'username' => 'requester-'.uniqid()]);

        $request = PengeluaranRequest::create([
            'uraian' => 'Beli ATK',
            'jumlah' => 100000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'approved',
            'requester_id' => $requester->id,
            'branch_id' => $approver->branch_id,
        ]);

        $this->postJson("/api/pengeluaran-request/{$request->id}/approve")
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('pengeluaran_requests', ['id' => $request->id, 'status' => 'approved']);
    }
}
