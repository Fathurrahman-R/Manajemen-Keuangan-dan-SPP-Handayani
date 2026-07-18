<?php

namespace Tests\Feature;

use App\Models\PermissionEndpoint;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Dynamic RBAC — Modul 9.
 *
 * Covers: DynamicPermissionMiddleware, RbacController CRUD,
 * API regression, edge cases & security.
 *
 * @group rbac
 * @group module-9
 */
class RbacTest extends TestCase
{
    use WithFaker;

    protected \App\Models\Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Hapus user dari test sebelumnya — UserFactory pakai username default 'admin'
        \Illuminate\Support\Facades\DB::delete('delete from users');
        \Illuminate\Support\Facades\DB::delete('delete from personal_access_tokens');

        // parent::setUp() sudah hapus roles + users via DB::delete, seed ulang
        $this->seedMinimalRbac();

        $this->branch = \App\Models\Branch::firstOrCreate(
            ['location' => 'Test Branch'],
            ['location' => 'Test Branch']
        );
    }

    // ── RBAC Minimal Seeder ──

    protected function seedMinimalRbac(): void
    {
        // Hapus leftover dari sync-endpoints command yang mungkin pakai format path berbeda
        \Illuminate\Support\Facades\DB::delete('delete from permission_endpoints');
        \Illuminate\Support\Facades\DB::delete('delete from permission_resources');

        // Seed minimal resource untuk test list
        \App\Models\PermissionResource::create([
            'resource_key' => 'users',
            'label' => 'Manajemen Pengguna',
            'is_active' => true,
        ]);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        // Seed endpoint mapping untuk endpoint yang di-test
        $this->ensureMapping('GET', '/api/users', 'view-user');
        $this->ensureMapping('GET', '/api/kategori', 'view-kategori');
        $this->ensureMapping('GET', '/api/tagihan', 'view-tagihan');
        $this->ensureMapping('POST', '/api/tagihan', 'create-tagihan');
        $this->ensureMapping('DELETE', '/api/tagihan/{kode_tagihan}', 'delete-tagihan');
        $this->ensureMapping('GET', '/api/siswa/{jenjang}/{id}', 'read-siswa');
        $this->ensureMapping('GET', '/api/kelas/TK', 'view-kelas');

        // RBAC API mapping
        $this->ensureMapping('GET', '/api/rbac/permissions', 'view-permission');
        $this->ensureMapping('POST', '/api/rbac/permissions', 'create-permission');
        $this->ensureMapping('PUT', '/api/rbac/permissions/{permission}', 'edit-permission');
        $this->ensureMapping('DELETE', '/api/rbac/permissions/{permission}', 'delete-permission');
        $this->ensureMapping('GET', '/api/rbac/endpoints', 'view-permission');
        $this->ensureMapping('POST', '/api/rbac/endpoints', 'edit-permission');
        $this->ensureMapping('PUT', '/api/rbac/endpoints/{endpoint}', 'edit-permission');
        $this->ensureMapping('DELETE', '/api/rbac/endpoints/{endpoint}', 'delete-permission');
        $this->ensureMapping('GET', '/api/rbac/resources', 'view-permission');
        $this->ensureMapping('POST', '/api/rbac/resources', 'edit-permission');
        $this->ensureMapping('PUT', '/api/rbac/resources/{resource}', 'edit-permission');
        $this->ensureMapping('DELETE', '/api/rbac/resources/{resource}', 'delete-permission');
        $this->ensureMapping('GET', '/api/rbac/roles', 'assign-permission');
        $this->ensureMapping('GET', '/api/rbac/roles/{role}/permissions', 'assign-permission');
        $this->ensureMapping('PUT', '/api/rbac/roles/{role}/permissions', 'assign-permission');
        $this->ensureMapping('GET', '/api/rbac/user-resources', null); // public

        Cache::forget('dynamic_permissions_endpoints');
    }

    protected function ensureMapping(string $method, string $path, ?string $permName): void
    {
        $permId = $permName
            ? Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web'])->id
            : null;

        PermissionEndpoint::firstOrCreate(
            ['method' => $method, 'path_pattern' => ltrim($path, '/')],
            ['permission_id' => $permId, 'is_active' => true]
        );
    }

    // ── User Helpers ──

    protected function authAs(string $roleName, array $permissionNames = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        $perms = collect($permissionNames)
            ->map(fn ($n) => Permission::firstOrCreate(['name' => $n, 'guard_name' => 'web']));
        $role->syncPermissions($perms);

        /** @var User $user */
        $user = User::factory()->create([
            'username' => 'user-'.uniqid(),
            'branch_id' => $this->branch->id,
        ]);
        $user->assignRole($role);

        Sanctum::actingAs($user);

        return $user;
    }

    protected function assertNot403($response): void
    {
        $this->assertNotEquals(403, $response->status(),
            'Expected non-403 but got 403 Forbidden.');
    }

    // ══════════════════════════════════════════════
    // A. DynamicPermissionMiddleware — Access Control
    // ══════════════════════════════════════════════

    /** @test */
    public function rbac_001_superadmin_bypass_all_permissions()
    {
        $this->authAs('superadmin');

        $this->get('/api/users')
            ->assertStatus(200);
    }

    /** @test */
    public function rbac_002_admin_with_permission_can_access()
    {
        $this->authAs('admin', ['view-user']);

        $this->get('/api/users')
            ->assertStatus(200);
    }

    /** @test */
    public function rbac_003_user_without_permission_gets_403()
    {
        $this->authAs('admin');

        $this->get('/api/users')
            ->assertStatus(403);
    }

    /** @test */
    public function rbac_004_unmapped_endpoint_allows_by_default()
    {
        $this->authAs('admin');

        // Endpoint /api/health-check — tidak terdaftar → default permit
        $response = $this->get('/api/health-check');
        $this->assertNotEquals(403, $response->status());
    }

    /** @test */
    public function rbac_005_update_endpoint_mapping_takes_effect_immediately()
    {
        Cache::forget('dynamic_permissions_endpoints');

        $endpoint = PermissionEndpoint::where('method', 'GET')
            ->where('path_pattern', 'api/kategori')
            ->first();

        if (! $endpoint) {
            $this->markTestSkipped('Mapping /api/kategori tidak ditemukan.');
        }

        $originalPermId = $endpoint->permission_id;
        $this->authAs('admin');

        // User tanpa view-kategori → 403
        $this->get('/api/kategori')
            ->assertStatus(403);

        // Hapus proteksi
        $endpoint->update(['permission_id' => null]);
        Cache::forget('dynamic_permissions_endpoints');

        // User yang sama sekarang bisa
        $this->assertNot403(
            $this->get('/api/kategori')
        );

        // Kembalikan
        $endpoint->update(['permission_id' => $originalPermId]);
        Cache::forget('dynamic_permissions_endpoints');
    }

    /** @test */
    public function rbac_006_path_pattern_with_dynamic_params()
    {
        $this->authAs('admin', ['read-siswa']);

        // Pattern api/siswa/{jenjang}/{id}
        $this->assertNot403(
            $this->get('/api/siswa/TK/5')
        );
    }

    /** @test */
    public function rbac_007_method_specific_permission()
    {
        $this->authAs('admin', ['view-tagihan']);

        // GET → punya view-tagihan
        $this->assertNot403(
            $this->get('/api/tagihan')
        );

        // POST → tidak punya create-tagihan → 403
        $this->post('/api/tagihan', ['dummy' => true])
            ->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    // B. Permission CRUD
    // ══════════════════════════════════════════════

    /** @test */
    public function rbac_008_list_permissions()
    {
        $this->authAs('admin', ['view-permission']);

        $this->get('/api/rbac/permissions')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'guard_name']]]);
    }

    /** @test */
    public function rbac_009_create_permission()
    {
        $this->authAs('admin', ['create-permission']);

        $this->post('/api/rbac/permissions', ['name' => 'view-test-'.time()])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Permission created.');
    }

    /** @test */
    public function rbac_010_duplicate_permission_name_rejected()
    {
        $this->authAs('admin', ['create-permission']);

        $name = 'view-dup-'.time();
        $this->postJson('/api/rbac/permissions', ['name' => $name])
            ->assertStatus(201);

        $this->postJson('/api/rbac/permissions', ['name' => $name])
            ->assertStatus(422);
    }

    /** @test */
    public function rbac_011_update_permission()
    {
        $this->authAs('admin', ['edit-permission']);

        $perm = Permission::create(['name' => 'view-upd-'.time(), 'guard_name' => 'web']);
        $newName = $perm->name.'-v2';

        $this->putJson('/api/rbac/permissions/'.$perm->id, ['name' => $newName])
            ->assertStatus(200);

        $this->assertDatabaseHas('permissions', ['name' => $newName]);
    }

    /** @test */
    public function rbac_012_delete_permission()
    {
        $this->authAs('admin', ['delete-permission']);

        $perm = Permission::create(['name' => 'view-del-'.time(), 'guard_name' => 'web']);

        $this->delete('/api/rbac/permissions/'.$perm->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('permissions', ['id' => $perm->id]);
    }

    /** @test */
    public function rbac_013_user_without_create_permission_cannot_create()
    {
        $this->authAs('admin', ['view-permission']); // view saja

        $this->postJson('/api/rbac/permissions', ['name' => 'should-fail-'.time()])
            ->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    // C. Endpoint Mapping
    // ══════════════════════════════════════════════

    /** @test */
    public function rbac_014_list_endpoints()
    {
        $this->authAs('admin', ['view-permission']);

        $this->get('/api/rbac/endpoints')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'method', 'path_pattern']]]);
    }

    /** @test */
    public function rbac_015_create_endpoint_mapping()
    {
        $this->authAs('admin', ['edit-permission']);

        $perm = Permission::firstOrCreate(['name' => 'view-siswa', 'guard_name' => 'web']);

        $this->post('/api/rbac/endpoints', [
            'method' => 'GET',
            'path_pattern' => 'api/siswa/test-'.time(),
            'permission_id' => $perm->id,
            'group' => 'siswa',
            'is_active' => true,
        ])->assertStatus(201);
    }

    /** @test */
    public function rbac_016_update_endpoint()
    {
        $this->authAs('admin', ['edit-permission']);

        $endpoint = PermissionEndpoint::where('method', 'GET')
            ->where('path_pattern', 'api/kategori')
            ->first();

        if (! $endpoint) {
            $this->markTestSkipped('Endpoint mapping tidak ditemukan.');
        }

        $this->putJson('/api/rbac/endpoints/'.$endpoint->id, [
            'method' => $endpoint->method,
            'path_pattern' => $endpoint->path_pattern,
            'permission_id' => $endpoint->permission_id,
            'is_active' => $endpoint->is_active,
        ])->assertStatus(200);
    }

    /** @test */
    public function rbac_017_disable_endpoint()
    {
        $this->authAs('admin', ['edit-permission']);

        $endpoint = PermissionEndpoint::first();
        if (! $endpoint) {
            $this->markTestSkipped('Tidak ada endpoint.');
        }

        $this->putJson('/api/rbac/endpoints/'.$endpoint->id, [
            'method' => $endpoint->method,
            'path_pattern' => $endpoint->path_pattern,
            'permission_id' => $endpoint->permission_id,
            'is_active' => false,
        ])->assertStatus(200);

        $this->assertDatabaseHas('permission_endpoints', ['id' => $endpoint->id, 'is_active' => 0]);

        $endpoint->update(['is_active' => true]);
    }

    // ══════════════════════════════════════════════
    // D. Resource Registry
    // ══════════════════════════════════════════════

    /** @test */
    public function rbac_019_list_resources()
    {
        $this->authAs('admin', ['view-permission']);

        $this->get('/api/rbac/resources')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'resource_key', 'label']]]);
    }

    /** @test */
    public function rbac_020_create_resource()
    {
        $this->authAs('admin', ['edit-permission']);

        $this->post('/api/rbac/resources', [
            'resource_key' => 'test.module.create-'.time(),
            'label' => 'Test Resource',
            'is_active' => true,
        ])->assertStatus(201);
    }

    // ══════════════════════════════════════════════
    // E. Role Assignment
    // ══════════════════════════════════════════════

    /** @test */
    public function rbac_024_list_roles_with_permissions()
    {
        $this->authAs('admin', ['assign-permission']);

        $this->get('/api/rbac/roles')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'permissions']]]);
    }

    /** @test */
    public function rbac_025_assign_permission_to_role()
    {
        $this->authAs('admin', ['assign-permission']);

        $role = Role::create(['name' => 'test-role-'.time(), 'guard_name' => 'web']);
        $perm = Permission::create(['name' => 'view-assign-'.time(), 'guard_name' => 'web']);

        $this->putJson('/api/rbac/roles/'.$role->id.'/permissions', [
            'permissions' => [$perm->name],
        ])->assertStatus(200);

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo($perm->name));
    }

    /** @test */
    public function rbac_026_unassign_permission_from_role()
    {
        $role = Role::create(['name' => 'test-unassign-'.time(), 'guard_name' => 'web']);
        $perm = Permission::create(['name' => 'view-unassign-'.time(), 'guard_name' => 'web']);
        $role->givePermissionTo($perm);

        $this->authAs('admin', ['assign-permission']);

        $this->putJson('/api/rbac/roles/'.$role->id.'/permissions', [
            'permissions' => [],
        ])->assertStatus(200);

        $role->refresh();
        $this->assertFalse($role->hasPermissionTo($perm->name));
    }

    // ══════════════════════════════════════════════
    // G. API Regression
    // ══════════════════════════════════════════════

    /** @test */
    public function rbac_031_regression_users_endpoint_protected()
    {
        // Superadmin (bypass)
        $this->authAs('superadmin');
        $this->get('/api/users')->assertStatus(200);

        // Admin dengan view-user
        $this->authAs('admin', ['view-user']);
        $this->get('/api/users')->assertStatus(200);

        // Admin tanpa view-user → 403
        $this->authAs('admin');
        $this->get('/api/users')->assertStatus(403);
    }

    /** @test */
    public function rbac_033_regression_delete_endpoint_protected()
    {
        // Admin dengan delete-tagihan
        $this->authAs('admin', ['delete-tagihan']);
        $r = $this->delete('/api/tagihan/nonexistent-xyz');
        $this->assertNot403($r);

        // Admin tanpa delete-tagihan → 403
        $this->authAs('admin');
        $this->delete('/api/tagihan/nonexistent-xyz')
            ->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    // H. Edge Cases & Security
    // ══════════════════════════════════════════════

    /** @test */
    public function rbac_035_invalid_token_returns_401()
    {
        $this->withHeaders([
            'Authorization' => 'Bearer invalid_token_xxx',
            'Accept' => 'application/json',
        ])->get('/api/users')
            ->assertStatus(401);
    }

    /** @test */
    public function rbac_036_cache_cleared_after_endpoint_update()
    {
        Cache::forget('dynamic_permissions_endpoints');

        $this->authAs('admin', ['edit-permission']);

        $endpoint = PermissionEndpoint::first();
        if (! $endpoint) {
            $this->markTestSkipped('Tidak ada endpoint.');
        }

        $this->putJson('/api/rbac/endpoints/'.$endpoint->id, [
            'method' => $endpoint->method,
            'path_pattern' => $endpoint->path_pattern,
            'permission_id' => $endpoint->permission_id,
            'is_active' => $endpoint->is_active,
        ])->assertStatus(200);

        $this->assertFalse(Cache::has('dynamic_permissions_endpoints'));
    }

    /** @test */
    public function rbac_037_unauthorized_access_to_rbac_api()
    {
        $this->authAs('admin'); // tanpa permission RBAC

        $this->get('/api/rbac/permissions')->assertStatus(403);
        $this->post('/api/rbac/permissions', ['name' => 'test'])->assertStatus(403);
        $this->get('/api/rbac/endpoints')->assertStatus(403);
        $this->get('/api/rbac/resources')->assertStatus(403);
        $this->get('/api/rbac/roles')->assertStatus(403);
    }

    /** @test */
    public function rbac_040_superadmin_without_explicit_permissions()
    {
        $this->authAs('superadmin');

        $this->get('/api/users')->assertStatus(200);
        $this->get('/api/kelas/TK')->assertStatus(200);
    }
}
