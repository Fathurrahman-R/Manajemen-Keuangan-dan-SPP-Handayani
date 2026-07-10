<?php

namespace App\Console\Commands;

use App\Constant\PermissionBinding;
use App\Enum\DefaultRoles;
use App\Enum\Permission;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sync the permission table with the App\Enum\Permission enum.
 *
 * Adds any permission cases that don't yet exist in the database, optionally
 * removes stale permissions that are no longer in the enum, and re-attaches
 * the canonical superadmin/admin/siswa permission sets.
 */
class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync
        {--prune : Hapus permission di database yang sudah tidak ada di enum}';

    protected $description = 'Sinkronkan tabel permissions dengan App\\Enum\\Permission dan refresh role superadmin/admin/siswa.';

    public function handle(): int
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $enumNames = collect(Permission::cases())->map(fn($p) => $p->value);

        $created = 0;
        foreach ($enumNames as $name) {
            $perm = SpatiePermission::firstOrCreate(['name' => $name]);
            if ($perm->wasRecentlyCreated) {
                $created++;
                $this->line("  + {$name}");
            }
        }
        $this->info("Permissions baru ditambahkan: {$created}");

        if ($this->option('prune')) {
            $stale = SpatiePermission::whereNotIn('name', $enumNames->all())->pluck('name');
            if ($stale->isNotEmpty()) {
                SpatiePermission::whereIn('name', $stale->all())->delete();
                foreach ($stale as $name) {
                    $this->line("  - {$name}");
                }
                $this->warn("Permissions stale dihapus: {$stale->count()}");
            } else {
                $this->info('Tidak ada permission stale.');
            }
        }

        // Refresh role assignments to keep them aligned with code.
        // Role superadmin: tidak perlu sync permission — Gate::before bypass handle semuanya.
        Role::firstOrCreate(['name' => DefaultRoles::SUPERADMIN->value]);
        $this->info('Role superadmin: bypass via Gate::before (tanpa explicit permission).');

        $admin = Role::firstOrCreate(['name' => DefaultRoles::ADMIN->value]);
        $adminPermissions = collect(PermissionBinding::ADMIN_PERMISSIONS)
            ->flatten()
            ->map(fn($p) => $p->value)
            ->filter(fn($p) => $p !== Permission::UPDATE_MIDTRANS_CONFIG->value)
            ->values()
            ->all();
        $admin->syncPermissions($adminPermissions);
        $this->info('Role admin: ' . count($adminPermissions) . ' permission.');

        $siswa = Role::firstOrCreate(['name' => DefaultRoles::SISWA->value]);
        $siswaPermissions = [
            Permission::VIEW_TAGIHAN_SISWA->value,
            Permission::VIEW_OWN_BILLING->value,
            Permission::PAY_TAGIHAN_ONLINE->value,
            Permission::PRINT_KWITANSI->value,
        ];
        $siswa->syncPermissions($siswaPermissions);
        $this->info('Role siswa: ' . count($siswaPermissions) . ' permission.');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('Permission cache di-clear.');

        return self::SUCCESS;
    }
}
