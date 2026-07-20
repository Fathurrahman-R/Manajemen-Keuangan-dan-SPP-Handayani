<?php

namespace Tests\Feature\Rbac;

use App\Models\PagePermission;
use App\Models\PermissionEndpoint;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RbacToggleAndAudienceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 'rbac' resource_key gates the whole /api/rbac/* prefix — no
        // mapping means EndpointPermission aborts every request with 403.
        PermissionEndpoint::updateOrCreate(
            ['resource_key' => 'rbac'],
            ['permission_id' => null, 'is_active' => true],
        );
        foreach (['role.create', 'endpoint-mapping.update', 'resource-registry.update'] as $key) {
            PermissionEndpoint::updateOrCreate(
                ['resource_key' => $key],
                ['permission_id' => null, 'is_active' => true],
            );
        }
    }

    protected function tearDown(): void
    {
        // TestCase::setUp()'s cleanup list predates these tables/rows.
        PermissionEndpoint::where('resource_key', 'test.endpoint.toggle')->delete();
        PagePermission::where('resource_key', 'test.page.toggle')->delete();
        Permission::where('name', 'test-permission-baru')->delete();

        parent::tearDown();
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin, $admin->getAllPermissions()->pluck('name')->toArray());

        return $admin;
    }

    /**
     * Regression for RBAC-003: the is_active ToggleColumn PUTs only
     * `{is_active}`, but `resource_key` was a hard `required` rule — every
     * toggle failed with "Field resource key wajib diisi." even though the
     * record itself already has a resource_key.
     */
    public function test_toggling_endpoint_active_status_with_partial_payload_succeeds(): void
    {
        $this->actingAsAdmin();

        $endpoint = PermissionEndpoint::create([
            'resource_key' => 'test.endpoint.toggle',
            'permission_id' => null,
            'is_active' => true,
        ]);

        $this->putJson("/api/rbac/endpoints/{$endpoint->id}", ['is_active' => false])
            ->assertOk();

        $this->assertDatabaseHas('permission_endpoints', [
            'id' => $endpoint->id,
            'resource_key' => 'test.endpoint.toggle',
            'is_active' => 0,
        ]);
    }

    /**
     * Regression for RBAC-004: same bug as RBAC-003, on the resource/page
     * registry table's toggle column.
     */
    public function test_toggling_page_permission_active_status_with_partial_payload_succeeds(): void
    {
        $this->actingAsAdmin();

        $page = PagePermission::create([
            'resource_key' => 'test.page.toggle',
            'is_active' => true,
        ]);

        $this->putJson("/api/rbac/page-permissions/{$page->id}", ['is_active' => false])
            ->assertOk();

        $this->assertDatabaseHas('page_permissions', [
            'id' => $page->id,
            'resource_key' => 'test.page.toggle',
            'is_active' => 0,
        ]);
    }

    /**
     * Regression for RBAC-001: a permission created with a new, non-'siswa'
     * audience (e.g. "Testing") was silently bucketed into the default
     * "admin" section instead of getting its own audience section.
     */
    public function test_permission_with_new_audience_gets_its_own_section(): void
    {
        $this->actingAsAdmin();

        Permission::create([
            'name' => 'test-permission-baru',
            'guard_name' => 'web',
            'label' => 'Permission Test',
            'group' => 'Test Env',
            'audience' => 'Testing',
        ]);

        $response = $this->getJson('/api/rbac/roles/permissions-tree')->assertOk();
        $data = $response->json('data');

        $this->assertArrayHasKey('audiences', $data);
        $this->assertArrayHasKey('Testing', $data['audiences']);
        $this->assertSame(
            'test-permission-baru',
            $data['audiences']['Testing']['groups']['Test Env'][0]['name'] ?? null,
        );

        // Must NOT have leaked into the default "admin" audience/groups.
        $adminNames = collect($data['audiences']['admin']['groups'] ?? [])
            ->flatten(1)
            ->pluck('name');
        $this->assertFalse($adminNames->contains('test-permission-baru'));
    }
}
