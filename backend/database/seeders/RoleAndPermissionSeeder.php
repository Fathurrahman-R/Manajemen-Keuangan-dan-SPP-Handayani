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
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cache before seeding
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions from enum
        foreach (Permission::cases() as $permission) {
            SpatiePermission::firstOrCreate(['name' => $permission->value]);
        }

        // Create default roles
        $superadmin = Role::firstOrCreate(['name' => DefaultRoles::SUPERADMIN->value]);
        $admin = Role::firstOrCreate(['name' => DefaultRoles::ADMIN->value]);
        Role::firstOrCreate(['name' => DefaultRoles::USER->value]);
        $siswa = Role::firstOrCreate(['name' => DefaultRoles::SISWA->value]);

        // Assign all permissions to superadmin
        $superadmin->syncPermissions(
            collect(Permission::cases())->map(fn($p) => $p->value)->toArray()
        );

        // Assign admin permissions from PermissionBinding
        // Exclude manage-midtrans-config from admin — only superadmin should have it (Req 3.4)
        $adminPermissions = collect(PermissionBinding::ADMIN_PERMISSIONS)
            ->flatten()
            ->map(fn($p) => $p->value)
            ->filter(fn($p) => $p !== Permission::MANAGE_MIDTRANS_CONFIG->value)
            ->values()
            ->toArray();
        $admin->syncPermissions($adminPermissions);

        // Assign siswa permissions — view-tagihan-siswa, view-own-billing, and pay-tagihan-online
        $siswa->syncPermissions([
            Permission::VIEW_TAGIHAN_SISWA->value,
            Permission::VIEW_OWN_BILLING->value,
            Permission::PAY_TAGIHAN_ONLINE->value,
        ]);

        // Clear cache after seeding
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
