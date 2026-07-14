<?php

namespace App\Console\Commands;

use App\Constant\Permissions;
use App\Enum\Permission as PermissionEnum;
use App\Http\Controllers\RbacController;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class BackfillPermissionGroupsCommand extends Command
{
    protected $signature = 'permissions:backfill-groups';

    protected $description = 'Isi kolom group untuk semua permission yang sudah ada di DB';

    public function handle(): int
    {
        // Mapping permission name → group label (mirip RoleController@permissions)
        $map = [];

        // From constant groups
        $groupDefs = [
            'Users' => Permissions::USERS_PERMISSIONS,
            'Siswa' => Permissions::SISWA_PERMISSIONS,
            'Kelas' => Permissions::KELAS_PERMISSIONS,
            'Kategori' => Permissions::KATEGORI_PERMISSIONS,
            'Pembayaran' => Permissions::PEMBAYARAN_PERMISSIONS,
            'Jenis Tagihan' => Permissions::JENIS_TAGIHAN_PERMISSIONS,
            'Tagihan' => Permissions::TAGIHAN_PERMISSIONS,
            'Pengeluaran' => Permissions::PENGELUARAN_PERMISSIONS,
            'Approval Workflow' => Permissions::APPROVAL_WORKFLOW_PERMISSIONS,
            'Laporan' => Permissions::LAPORAN_PERMISSIONS,
            'Tahun Ajaran' => Permissions::TAHUN_AJARAN_PERMISSIONS,
            'Kenaikan Kelas' => Permissions::KENAIKAN_KELAS_PERMISSIONS,
            'Akun Siswa' => Permissions::AKUN_SISWA_PERMISSIONS,
            'Import Export' => Permissions::IMPORT_EXPORT_PERMISSIONS,
            'Dashboard' => Permissions::DASHBOARD_PERMISSIONS,
            'Branch' => Permissions::BRANCH_PERMISSIONS,
            'Midtrans (Admin)' => Permissions::MIDTRANS_PERMISSIONS,
            'Pengaturan' => Permissions::SETTING_PERMISSIONS,
        ];

        foreach ($groupDefs as $label => $group) {
            $this->flattenAndMap($group, $label, $map);
        }

        // Roles & Permissions group (all role/permission enum cases)
        $rbacPerms = [
            PermissionEnum::VIEW_ROLES,
            PermissionEnum::CREATE_ROLE,
            PermissionEnum::UPDATE_ROLE,
            PermissionEnum::DELETE_ROLE,
            PermissionEnum::ATTACH_ROLE,
            PermissionEnum::DETACH_ROLE,
            PermissionEnum::VIEW_PERMISSIONS,
            PermissionEnum::ATTACH_PERMISSIONS,
            PermissionEnum::DETACH_PERMISSIONS,
            PermissionEnum::VIEW_PERMISSION,
            PermissionEnum::CREATE_PERMISSION,
            PermissionEnum::EDIT_PERMISSION,
            PermissionEnum::DELETE_PERMISSION,
            PermissionEnum::ASSIGN_PERMISSION,
        ];
        foreach ($rbacPerms as $perm) {
            $map[$perm->value] = 'Roles & Permissions';
        }

        // Also map the missing dalam $siswa group di RoleController
        $siswaPerms = [
            PermissionEnum::VIEW_OWN_BILLING,
            PermissionEnum::VIEW_TAGIHAN_SISWA,
            PermissionEnum::PAY_TAGIHAN_ONLINE,
        ];
        foreach ($siswaPerms as $perm) {
            if (! isset($map[$perm->value])) {
                $map[$perm->value] = 'Siswa & Wali';
            }
        }

        $updated = 0;
        foreach ($map as $name => $group) {
            $count = Permission::where('name', $name)->whereNull('group')->update(['group' => $group]);
            if ($count > 0) {
                $this->line("  {$name} → {$group}");
                $updated += $count;
            }
        }

        $this->info("Selesai. {$updated} permission di-backfill group-nya.");

        // ── Now backfill audience ──
        $audienceCount = 0;

        // 1. Permissions in admin groups → audience = null (already null, skip).

        // 2. Hardcoded siswa permissions.
        $siswaPerms = [
            PermissionEnum::VIEW_OWN_BILLING->value,
            PermissionEnum::VIEW_TAGIHAN_SISWA->value,
            PermissionEnum::PAY_TAGIHAN_ONLINE->value,
        ];
        foreach ($siswaPerms as $name) {
            $count = Permission::where('name', $name)->whereNull('audience')->update(['audience' => 'siswa']);
            if ($count > 0) {
                $this->line("  {$name} → siswa");
                $audienceCount += $count;
            }
        }

        // 3. Permissions with group from backfill but no audience → leave null (admin section).
        //    Nothing to do.

        $this->info("Selesai. {$audienceCount} permission di-set audience-nya.\n");

        // ── Now backfill label ──
        $rbacCtrl = new RbacController;
        $refl = new \ReflectionMethod($rbacCtrl, 'humanizePermission');
        $refl->setAccessible(true);

        $allPerms = Permission::whereNull('label')->get();
        $labelCount = 0;
        foreach ($allPerms as $perm) {
            $label = $refl->invoke($rbacCtrl, $perm->name);
            $perm->update(['label' => $label]);
            $this->line("  {$perm->name} → {$label}");
            $labelCount++;
        }

        $this->info("Selesai. {$labelCount} permission di-set label-nya.");

        return self::SUCCESS;
    }

    private function flattenAndMap(array $group, string $label, array &$map): void
    {
        foreach ($group as $value) {
            if ($value instanceof PermissionEnum) {
                $map[$value->value] = $label;
            } elseif (is_array($value)) {
                $this->flattenAndMap($value, $label, $map);
            }
        }
    }
}
