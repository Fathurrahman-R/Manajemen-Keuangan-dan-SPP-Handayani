<?php

namespace Tests\Feature;

use App\Enum\DefaultRoles;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createSuperadmin(string $token = 'sa-token'): User
    {
        /** @var User $user */
        $user = User::factory()->superadmin()->create([
            'username' => 'superadmin',
            'token'    => $token,
        ]);
        return $user;
    }

    private function createAdmin(string $token = 'admin-token'): User
    {
        /** @var User $user */
        $user = User::factory()->admin()->create([
            'username' => 'admin-user',
            'token'    => $token,
        ]);
        return $user;
    }

    private function createRole(string $name = 'kasir'): Role
    {
        return Role::create(['name' => $name, 'guard_name' => 'web']);
    }

    // ── GET /roles ────────────────────────────────────────────────────────────

    public function testIndexRoleBerhasil(): void
    {
        $this->createSuperadmin();
        $this->createRole();

        $this->getJson('api/roles', ['Authorization' => 'sa-token'])
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function testIndexRoleTanpaAuth(): void
    {
        $this->getJson('api/roles')
            ->assertStatus(401);
    }

    public function testIndexRoleForbiddenBukanSuperadmin(): void
    {
        $this->createAdmin();

        $this->getJson('api/roles', ['Authorization' => 'admin-token'])
            ->assertStatus(403);
    }

    // ── POST /roles ───────────────────────────────────────────────────────────

    public function testStoreRoleBerhasil(): void
    {
        $this->createSuperadmin();

        $this->postJson('api/roles', [
            'name'        => 'kasir',
            'permissions' => [],
        ], ['Authorization' => 'sa-token'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'kasir');
    }

    public function testStoreRoleDuplikat(): void
    {
        $this->createSuperadmin();
        $this->createRole('kasir');

        $this->postJson('api/roles', [
            'name'        => 'kasir',
            'permissions' => [],
        ], ['Authorization' => 'sa-token'])
            ->assertStatus(400)
            ->assertJsonPath('errors.message.0', 'Role dengan nama tersebut sudah ada.');
    }

    public function testStoreRoleValidasiFailed(): void
    {
        $this->createSuperadmin();

        // name wajib diisi
        $this->postJson('api/roles', [], ['Authorization' => 'sa-token'])
            ->assertStatus(422);
    }

    // ── GET /roles/{id} ───────────────────────────────────────────────────────

    public function testShowRoleBerhasil(): void
    {
        $this->createSuperadmin();
        $role = $this->createRole();

        $this->getJson("api/roles/{$role->id}", ['Authorization' => 'sa-token'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'kasir');
    }

    public function testShowRoleTidakDitemukan(): void
    {
        $this->createSuperadmin();

        $this->getJson('api/roles/9999', ['Authorization' => 'sa-token'])
            ->assertStatus(400);
    }

    // ── PUT /roles/{id} ───────────────────────────────────────────────────────

    public function testUpdateRoleBerhasil(): void
    {
        $this->createSuperadmin();
        $role = $this->createRole();

        $this->putJson("api/roles/{$role->id}", [
            'name'        => 'kasir-updated',
            'permissions' => [],
        ], ['Authorization' => 'sa-token'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'kasir-updated');
    }

    public function testUpdateRoleTidakDitemukan(): void
    {
        $this->createSuperadmin();

        $this->putJson('api/roles/9999', [
            'name'        => 'kasir',
            'permissions' => [],
        ], ['Authorization' => 'sa-token'])
            ->assertStatus(400);
    }

    // ── DELETE /roles/{id} ────────────────────────────────────────────────────

    public function testDestroyRoleBerhasil(): void
    {
        $this->createSuperadmin();
        $role = $this->createRole();

        $this->deleteJson("api/roles/{$role->id}", [], ['Authorization' => 'sa-token'])
            ->assertStatus(200)
            ->assertJsonPath('data', true);
    }

    public function testDestroyRoleTidakDitemukan(): void
    {
        $this->createSuperadmin();

        $this->deleteJson('api/roles/9999', [], ['Authorization' => 'sa-token'])
            ->assertStatus(400);
    }

    // ── POST /roles/attach ────────────────────────────────────────────────────

    public function testAttachRoleBerhasil(): void
    {
        $this->createSuperadmin();
        $this->createRole('kasir');
        /** @var User $target */
        $target = User::factory()->create(['username' => 'target', 'token' => 'target-tok']);

        $this->postJson('api/roles/attach', [
            'user_id' => $target->id,
            'role'    => 'kasir',
        ], ['Authorization' => 'sa-token'])
            ->assertStatus(200)
            ->assertJsonPath('message', 'Role berhasil dikaitkan.');
    }

    public function testAttachRoleUserTidakDitemukan(): void
    {
        $this->createSuperadmin();
        $this->createRole('kasir');

        $this->postJson('api/roles/attach', [
            'user_id' => 9999,
            'role'    => 'kasir',
        ], ['Authorization' => 'sa-token'])
            ->assertStatus(422); // form request validation: exists:users,id
    }

    public function testAttachRoleTidakDitemukan(): void
    {
        $this->createSuperadmin();
        /** @var User $target */
        $target = User::factory()->create(['username' => 'target', 'token' => 'target-tok']);

        $this->postJson('api/roles/attach', [
            'user_id' => $target->id,
            'role'    => 'tidak-ada',
        ], ['Authorization' => 'sa-token'])
            ->assertStatus(400);
    }

    // ── POST /roles/detach ────────────────────────────────────────────────────

    public function testDetachRoleBerhasil(): void
    {
        $this->createSuperadmin();
        /** @var User $target */
        $target = User::factory()->admin()->create(['username' => 'target', 'token' => 'target-tok']);

        $this->postJson('api/roles/detach', [
            'user_id' => $target->id,
            'role'    => DefaultRoles::ADMIN->value,
        ], ['Authorization' => 'sa-token'])
            ->assertStatus(200)
            ->assertJsonPath('message', 'Role berhasil dilepaskan.');
    }

    public function testDetachRoleTidakDitemukan(): void
    {
        $this->createSuperadmin();
        /** @var User $target */
        $target = User::factory()->create(['username' => 'target', 'token' => 'target-tok']);

        $this->postJson('api/roles/detach', [
            'user_id' => $target->id,
            'role'    => 'tidak-ada',
        ], ['Authorization' => 'sa-token'])
            ->assertStatus(400);
    }
}
