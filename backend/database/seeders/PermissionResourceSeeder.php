<?php

namespace Database\Seeders;

use App\Models\PagePermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionResourceSeeder extends Seeder
{
    /**
     * Seed page_permissions with ALL resource pointers (feature + action level).
     *
     * This is the SINGLE merged table for both Resource Registry and Page Security.
     *
     * Convention:
     *   {feature}             → feature-level (navigation, page access)
     *   {feature}.{action}    → action-level (buttons, UI actions)
     *
     * Permission binding is done via permission_name, changeable via UI.
     * Code references resource_key ONLY — no hardcoded permission names.
     */
    public function run(): void
    {
        $resources = [
            // ═══════════════════════════════════════
            // DASHBOARD
            // ═══════════════════════════════════════
            ['key' => 'dashboard',           'group' => 'dashboard',  'perm' => 'view-dashboard'],

            // ═══════════════════════════════════════
            // AKADEMIK
            // ═══════════════════════════════════════
            ['key' => 'siswa',               'group' => 'akademik',   'perm' => 'view-siswa'],
            ['key' => 'siswa.create',        'group' => null,         'perm' => 'create-siswa'],
            ['key' => 'siswa.read',          'group' => null,         'perm' => 'read-siswa'],
            ['key' => 'siswa.update',        'group' => null,         'perm' => 'update-siswa'],
            ['key' => 'siswa.delete',        'group' => null,         'perm' => 'delete-siswa'],

            ['key' => 'kelas',               'group' => 'akademik',   'perm' => 'view-kelas'],
            ['key' => 'kelas.create',        'group' => null,         'perm' => 'create-kelas'],
            ['key' => 'kelas.read',          'group' => null,         'perm' => 'read-kelas'],
            ['key' => 'kelas.update',        'group' => null,         'perm' => 'update-kelas'],
            ['key' => 'kelas.delete',        'group' => null,         'perm' => 'delete-kelas'],

            ['key' => 'kategori',            'group' => 'akademik',   'perm' => 'view-kategori'],
            ['key' => 'kategori.create',     'group' => null,         'perm' => 'create-kategori'],
            ['key' => 'kategori.read',       'group' => null,         'perm' => 'read-kategori'],
            ['key' => 'kategori.update',     'group' => null,         'perm' => 'update-kategori'],
            ['key' => 'kategori.delete',     'group' => null,         'perm' => 'delete-kategori'],

            ['key' => 'tahun-ajaran',        'group' => 'akademik',   'perm' => 'view-tahun-ajaran'],
            ['key' => 'tahun-ajaran.create', 'group' => null,         'perm' => 'create-tahun-ajaran'],
            ['key' => 'tahun-ajaran.update', 'group' => null,         'perm' => 'update-tahun-ajaran'],
            ['key' => 'tahun-ajaran.delete', 'group' => null,         'perm' => 'delete-tahun-ajaran'],

            ['key' => 'kenaikan-kelas',      'group' => 'akademik',   'perm' => 'view-kenaikan-kelas'],
            ['key' => 'kenaikan-kelas.process','group' => null,       'perm' => 'process-kenaikan-kelas'],
            ['key' => 'kenaikan-kelas.undo', 'group' => null,         'perm' => 'undo-kenaikan-kelas'],

            // ═══════════════════════════════════════
            // KEUANGAN
            // ═══════════════════════════════════════
            ['key' => 'jenis-tagihan',       'group' => 'keuangan',   'perm' => 'view-jenis-tagihan'],
            ['key' => 'jenis-tagihan.create','group' => null,         'perm' => 'create-jenis-tagihan'],
            ['key' => 'jenis-tagihan.read',  'group' => null,         'perm' => 'read-jenis-tagihan'],
            ['key' => 'jenis-tagihan.update','group' => null,         'perm' => 'update-jenis-tagihan'],
            ['key' => 'jenis-tagihan.delete','group' => null,         'perm' => 'delete-jenis-tagihan'],

            ['key' => 'tagihan',             'group' => 'keuangan',   'perm' => 'view-tagihan'],
            ['key' => 'tagihan.create',      'group' => null,         'perm' => 'create-tagihan'],
            ['key' => 'tagihan.read',        'group' => null,         'perm' => 'read-tagihan'],
            ['key' => 'tagihan.update',      'group' => null,         'perm' => 'update-tagihan'],
            ['key' => 'tagihan.delete',      'group' => null,         'perm' => 'delete-tagihan'],

            ['key' => 'pembayaran',          'group' => 'keuangan',   'perm' => 'view-pembayaran'],
            ['key' => 'pembayaran.create',   'group' => null,         'perm' => 'create-pembayaran'],
            ['key' => 'pembayaran.delete',   'group' => null,         'perm' => 'delete-pembayaran'],
            ['key' => 'pembayaran.print',    'group' => null,         'perm' => 'print-kwitansi'],

            ['key' => 'pengeluaran',         'group' => 'keuangan',   'perm' => 'view-pengeluaran'],
            ['key' => 'pengeluaran.create',  'group' => null,         'perm' => 'create-pengeluaran'],
            ['key' => 'pengeluaran.read',    'group' => null,         'perm' => 'read-pengeluaran'],
            ['key' => 'pengeluaran.update',  'group' => null,         'perm' => 'update-pengeluaran'],
            ['key' => 'pengeluaran.delete',  'group' => null,         'perm' => 'delete-pengeluaran'],
            ['key' => 'pengeluaran.request', 'group' => null,         'perm' => 'create-pengeluaran-request'],
            ['key' => 'pengeluaran.approve', 'group' => null,         'perm' => 'approve-pengeluaran'],
            ['key' => 'pengeluaran.disburse','group' => null,         'perm' => 'disburse-pengeluaran'],

            ['key' => 'midtrans',            'group' => 'keuangan',   'perm' => 'view-midtrans-transactions'],
            ['key' => 'midtrans.pay',        'group' => null,         'perm' => 'pay-tagihan-online'],
            ['key' => 'midtrans.sync',       'group' => null,         'perm' => 'sync-midtrans-transactions'],

            ['key' => 'midtrans-config',     'group' => 'keuangan',   'perm' => 'view-midtrans-config'],
            ['key' => 'midtrans-config.update','group' => null,       'perm' => 'update-midtrans-config'],

            // ═══════════════════════════════════════
            // LAPORAN
            // ═══════════════════════════════════════
            ['key' => 'kas-harian',          'group' => 'laporan',    'perm' => 'view-kas-harian'],
            ['key' => 'rekap-bulanan',       'group' => 'laporan',    'perm' => 'view-rekap-bulanan'],
            ['key' => 'laporan.export',      'group' => null,         'perm' => 'export-laporan'],

            ['key' => 'import-export',       'group' => 'laporan',    'perm' => 'import-data'],
            ['key' => 'import-export.import','group' => null,         'perm' => 'import-data'],
            ['key' => 'import-export.export','group' => null,         'perm' => 'export-data'],

            // ═══════════════════════════════════════
            // PENGATURAN
            // ═══════════════════════════════════════
            ['key' => 'user-management',     'group' => 'pengaturan', 'perm' => 'view-user'],
            ['key' => 'user-management.create','group' => null,       'perm' => 'create-user'],
            ['key' => 'user-management.read','group' => null,         'perm' => 'read-user'],
            ['key' => 'user-management.update','group' => null,       'perm' => 'update-user'],
            ['key' => 'user-management.delete','group' => null,       'perm' => 'delete-user'],

            ['key' => 'role-management',     'group' => 'pengaturan', 'perm' => 'view-roles'],
            ['key' => 'role.create',         'group' => null,         'perm' => 'create-role'],
            ['key' => 'role.update',         'group' => null,         'perm' => 'update-role'],
            ['key' => 'role.delete',         'group' => null,         'perm' => 'delete-role'],
            ['key' => 'role.attach',         'group' => null,         'perm' => 'attach-role'],
            ['key' => 'role.detach',         'group' => null,         'perm' => 'detach-role'],

            ['key' => 'akun-siswa',          'group' => 'pengaturan', 'perm' => 'view-akun-siswa'],
            ['key' => 'akun-siswa.generate', 'group' => null,         'perm' => 'generate-akun-siswa'],

            ['key' => 'app-setting',         'group' => 'pengaturan', 'perm' => 'view-app-setting'],
            ['key' => 'app-setting.update',  'group' => null,         'perm' => 'update-app-setting'],

            ['key' => 'branch',              'group' => 'pengaturan', 'perm' => 'view-branch'],
            ['key' => 'branch.create',       'group' => null,         'perm' => 'create-branch'],
            ['key' => 'branch.read',         'group' => null,         'perm' => 'read-branch'],
            ['key' => 'branch.update',       'group' => null,         'perm' => 'update-branch'],
            ['key' => 'branch.delete',       'group' => null,         'perm' => 'delete-branch'],

            ['key' => 'notification-setting','group' => 'pengaturan', 'perm' => 'view-notification-setting'],
            ['key' => 'notification-setting.update','group' => null,  'perm' => 'update-notification-setting'],

            ['key' => 'notification-logs',   'group' => 'pengaturan', 'perm' => 'view-notification-logs'],

            ['key' => 'branch-approval-setting','group' => 'pengaturan', 'perm' => 'view-app-setting'],

            // RBAC
            ['key' => 'rbac',                'group' => 'pengaturan', 'perm' => 'view-permissions'],
            ['key' => 'rbac.view',           'group' => null,         'perm' => 'view-permission'],
            ['key' => 'rbac.create',         'group' => null,         'perm' => 'create-permission'],
            ['key' => 'rbac.edit',           'group' => null,         'perm' => 'edit-permission'],
            ['key' => 'rbac.delete',         'group' => null,         'perm' => 'delete-permission'],
            ['key' => 'rbac.assign',         'group' => null,         'perm' => 'assign-permission'],
            ['key' => 'rbac.attach',         'group' => null,         'perm' => 'attach-permissions'],
            ['key' => 'rbac.detach',         'group' => null,         'perm' => 'detach-permissions'],

            // PORTAL
            ['key' => 'portal.billing',      'group' => null,         'perm' => 'view-own-billing'],
            ['key' => 'portal.tagihan',      'group' => null,         'perm' => 'view-tagihan-siswa'],
        ];

        foreach ($resources as $res) {
            $permission = Permission::where('name', $res['perm'])->first();

            PagePermission::updateOrCreate(
                ['resource_key' => $res['key']],
                [
                    'permission_name' => $res['perm'],
                    'guard_name' => 'web',
                    'group' => $res['group'],
                    'description' => "Bound to: {$res['perm']}",
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Page permissions seeded: ' . count($resources) . ' resources.');
    }
}
