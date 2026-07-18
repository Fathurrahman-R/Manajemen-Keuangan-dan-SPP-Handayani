<?php

namespace Database\Seeders;

use App\Constant\PermissionBinding;
use App\Enum\DefaultRoles;
use App\Enum\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions from enum
        foreach (Permission::cases() as $permission) {
            SpatiePermission::firstOrCreate(['name' => $permission->value]);
        }

        // Create default roles
        $superadmin = Role::firstOrCreate(['name' => DefaultRoles::SUPERADMIN->value]);
        $developer = Role::firstOrCreate(['name' => DefaultRoles::DEVELOPER->value]);
        $kepala_yayasan = Role::firstOrCreate(['name' => DefaultRoles::KEPALA_YAYASAN->value]);
        $admin = Role::firstOrCreate(['name' => DefaultRoles::ADMIN->value]);
        $siswa = Role::firstOrCreate(['name' => DefaultRoles::SISWA->value]);

        $superadmin->syncPermissions([]);

        $developerPermissions = collect(PermissionBinding::DEV_PERMISSIONS)
            ->flatten()
            ->map(fn ($p) => $p->value)
            ->unique()
            ->values()
            ->toArray();
        $developer->syncPermissions($developerPermissions);

        $yayasanPermissions = collect(PermissionBinding::KEPALA_YAYASAN_PERMISSIONS)
            ->flatten()
            ->map(fn ($p) => $p->value)
            ->unique()
            ->values()
            ->toArray();
        $kepala_yayasan->syncPermissions($yayasanPermissions);

        $adminPermissions = collect(PermissionBinding::ADMIN_PERMISSIONS)
            ->flatten()
            ->map(fn ($p) => $p->value)
            ->unique()
            ->values()
            ->toArray();
        $admin->syncPermissions($adminPermissions);

        $siswaPermissions = collect(PermissionBinding::SISWA_PERMISSIONS)
            ->flatten()
            ->map(fn ($p) => $p->value)
            ->unique()
            ->values()
            ->toArray();
        $siswa->syncPermissions($siswaPermissions);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
