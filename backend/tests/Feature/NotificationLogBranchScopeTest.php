<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\NotificationLog;
use App\Models\PermissionEndpoint;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Regression coverage for a suspected gap (WF-010): does
 * NotificationLogController::index() respect the active-branch-switcher
 * (X-Branch-Id header + ActiveBranchContextMiddleware) the same way other
 * branch-scoped endpoints do? Investigated and confirmed NOT a bug — the
 * middleware mutates $request->user()->branch_id in memory before the
 * controller runs, and Auth::user() returns that same cached instance, so
 * scoping already works correctly. This test locks that behavior in since
 * there was previously zero coverage for the branch-switch mechanism here.
 */
class NotificationLogBranchScopeTest extends TestCase
{
    public function test_notification_logs_follow_the_active_branch_switch_header(): void
    {
        $switchPermission = Permission::firstOrCreate(['name' => 'view-all-branches', 'guard_name' => 'web']);
        PermissionEndpoint::updateOrCreate(['resource_key' => 'api.branch.switch'], ['permission_id' => $switchPermission->id, 'is_active' => true]);
        PermissionEndpoint::updateOrCreate(['resource_key' => 'notification-logs.view'], ['permission_id' => null, 'is_active' => true]);

        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $user = User::factory()->admin()->create(['branch_id' => $branchA->id]);
        $user->givePermissionTo($switchPermission);
        Sanctum::actingAs($user, $user->getAllPermissions()->pluck('name')->toArray());

        NotificationLog::create(['branch_id' => $branchA->id, 'notification_type' => 'tagihan_baru', 'status' => 'sent', 'recipient_email' => 'a@test.com']);
        NotificationLog::create(['branch_id' => $branchB->id, 'notification_type' => 'tagihan_baru', 'status' => 'sent', 'recipient_email' => 'b@test.com']);

        // No switch header — sees own branch (A).
        $this->getJson('/api/notification-logs')
            ->assertOk()
            ->assertJsonPath('data.0.recipient_email', 'a@test.com')
            ->assertJsonCount(1, 'data');

        // Switch header to branch B — sees branch B's logs instead.
        $this->getJson('/api/notification-logs', ['X-Branch-Id' => (string) $branchB->id])
            ->assertOk()
            ->assertJsonPath('data.0.recipient_email', 'b@test.com')
            ->assertJsonCount(1, 'data');
    }
}
