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
            ['key' => 'siswa.view',          'group' => 'akademik',   'perm' => 'view-siswa'],
            ['key' => 'siswa.create',        'group' => null,         'perm' => 'create-siswa'],
            ['key' => 'siswa.read',          'group' => null,         'perm' => 'read-siswa'],
            ['key' => 'siswa.update',        'group' => null,         'perm' => 'update-siswa'],
            ['key' => 'siswa.delete',        'group' => null,         'perm' => 'delete-siswa'],

            ['key' => 'ayah.view',           'group' => null,         'perm' => 'view-siswa'],
            ['key' => 'ibu.view',            'group' => null,         'perm' => 'view-siswa'],
            ['key' => 'wali.view',           'group' => null,         'perm' => 'view-siswa'],
            ['key' => 'wali.create',         'group' => null,         'perm' => 'create-siswa'],
            ['key' => 'wali.update',         'group' => null,         'perm' => 'update-siswa'],
            ['key' => 'wali.delete',         'group' => null,         'perm' => 'delete-siswa'],

            ['key' => 'kelas.view',          'group' => 'akademik',   'perm' => 'view-kelas'],
            ['key' => 'kelas.create',        'group' => null,         'perm' => 'create-kelas'],
            ['key' => 'kelas.read',          'group' => null,         'perm' => 'read-kelas'],
            ['key' => 'kelas.update',        'group' => null,         'perm' => 'update-kelas'],
            ['key' => 'kelas.delete',        'group' => null,         'perm' => 'delete-kelas'],

            ['key' => 'kategori.view',       'group' => 'akademik',   'perm' => 'view-kategori'],
            ['key' => 'kategori.create',     'group' => null,         'perm' => 'create-kategori'],
            ['key' => 'kategori.read',       'group' => null,         'perm' => 'read-kategori'],
            ['key' => 'kategori.update',     'group' => null,         'perm' => 'update-kategori'],
            ['key' => 'kategori.delete',     'group' => null,         'perm' => 'delete-kategori'],

            ['key' => 'tahun-ajaran.view',   'group' => 'akademik',   'perm' => 'view-tahun-ajaran'],
            ['key' => 'tahun-ajaran.create', 'group' => null,         'perm' => 'create-tahun-ajaran'],
            ['key' => 'tahun-ajaran.update', 'group' => null,         'perm' => 'update-tahun-ajaran'],
            ['key' => 'tahun-ajaran.delete', 'group' => null,         'perm' => 'delete-tahun-ajaran'],
            ['key' => 'tahun-ajaran.toggle', 'group' => null,         'perm' => 'toggle-tahun-ajaran'],

            ['key' => 'kenaikan-kelas.view', 'group' => 'akademik',   'perm' => 'view-kenaikan-kelas'],
            ['key' => 'kenaikan-kelas.process', 'group' => null,       'perm' => 'process-kenaikan-kelas'],
            ['key' => 'kenaikan-kelas.undo', 'group' => null,         'perm' => 'undo-kenaikan-kelas'],
            ['key' => 'kenaikan-kelas.detail', 'group' => null,   'perm' => 'view-detail-kenaikan'],

            // ═══════════════════════════════════════
            // KEUANGAN
            // ═══════════════════════════════════════
            ['key' => 'jenis-tagihan.view',  'group' => 'keuangan',   'perm' => 'view-jenis-tagihan'],
            ['key' => 'jenis-tagihan.create', 'group' => null,         'perm' => 'create-jenis-tagihan'],
            ['key' => 'jenis-tagihan.update', 'group' => null,         'perm' => 'update-jenis-tagihan'],
            ['key' => 'jenis-tagihan.delete', 'group' => null,         'perm' => 'delete-jenis-tagihan'],

            ['key' => 'tagihan.view',        'group' => 'keuangan',   'perm' => 'view-tagihan'],
            ['key' => 'tagihan.create',      'group' => null,         'perm' => 'create-tagihan'],
            ['key' => 'tagihan.update',      'group' => null,         'perm' => 'update-tagihan'],
            ['key' => 'tagihan.delete',      'group' => null,         'perm' => 'delete-tagihan'],
            ['key' => 'tagihan.export',      'group' => null,         'perm' => 'export-data'],
            ['key' => 'tagihan.siswa',       'group' => null,         'perm' => 'view-own-billing'],

            ['key' => 'pembayaran.view',     'group' => 'keuangan',   'perm' => 'view-pembayaran'],
            ['key' => 'pembayaran.create',   'group' => null,         'perm' => 'create-pembayaran'],
            ['key' => 'pembayaran.delete',   'group' => null,         'perm' => 'delete-pembayaran'],
            ['key' => 'pembayaran.kwitansi', 'group' => null,         'perm' => 'print-kwitansi'],
            ['key' => 'pembayaran.siswa',    'group' => null,         'perm' => 'view-own-billing'],

            ['key' => 'pengeluaran.view',    'group' => 'keuangan',   'perm' => 'view-pengeluaran'],
            ['key' => 'pengeluaran.create',  'group' => null,         'perm' => 'create-pengeluaran'],
            ['key' => 'pengeluaran.update',  'group' => null,         'perm' => 'update-pengeluaran'],
            ['key' => 'pengeluaran.delete',  'group' => null,         'perm' => 'delete-pengeluaran'],
            ['key' => 'pengeluaran.approve', 'group' => null,         'perm' => 'approve-pengeluaran'],
            ['key' => 'pengeluaran.disburse', 'group' => null,         'perm' => 'disburse-pengeluaran'],

            ['key' => 'midtrans.admin',      'group' => 'keuangan',   'perm' => 'view-midtrans-transactions'],
            ['key' => 'midtrans.pay',        'group' => null,         'perm' => 'pay-tagihan-online'],
            ['key' => 'midtrans.sync',       'group' => null,         'perm' => 'sync-midtrans-transactions'],

            // ═══════════════════════════════════════
            // LAPORAN
            // ═══════════════════════════════════════
            ['key' => 'laporan.kas',         'group' => 'laporan',    'perm' => 'view-kas-harian'],
            ['key' => 'laporan.kas-detail',  'group' => null,         'perm' => 'detail-kas-harian'],
            ['key' => 'laporan.rekap',       'group' => 'laporan',    'perm' => 'view-rekap-bulanan'],
            ['key' => 'laporan.rekap-detail', 'group' => null,        'perm' => 'detail-rekap-bulanan'],
            ['key' => 'laporan.export',      'group' => null,         'perm' => 'export-laporan'],

            ['key' => 'import-data',         'group' => 'laporan',    'perm' => 'import-data'],
            ['key' => 'export-data',         'group' => 'laporan',    'perm' => 'export-data'],

            // ═══════════════════════════════════════
            // PENGATURAN
            // ═══════════════════════════════════════
            ['key' => 'users.view',          'group' => 'pengaturan', 'perm' => 'view-user'],
            ['key' => 'users.create',        'group' => null,         'perm' => 'create-user'],
            ['key' => 'users.read',          'group' => null,         'perm' => 'read-user'],
            ['key' => 'users.update',        'group' => null,         'perm' => 'update-user'],
            ['key' => 'users.delete',        'group' => null,         'perm' => 'delete-user'],
            ['key' => 'users.toggle',        'group' => null,         'perm' => 'toggle-user'],

            ['key' => 'role.view',           'group' => 'pengaturan', 'perm' => 'view-roles'],
            ['key' => 'role.create',         'group' => null,         'perm' => 'create-role'],
            ['key' => 'role.update',         'group' => null,         'perm' => 'update-role'],
            ['key' => 'role.delete',         'group' => null,         'perm' => 'delete-role'],

            ['key' => 'akun-siswa.view',     'group' => 'pengaturan', 'perm' => 'view-akun-siswa'],
            ['key' => 'akun-siswa.create',   'group' => null,         'perm' => 'generate-akun-siswa'],
            ['key' => 'akun-siswa.toggle',   'group' => null,         'perm' => 'toggle-akun-siswa'],
            ['key' => 'akun-siswa.reset',    'group' => null,         'perm' => 'reset-akun-siswa-password'],
            ['key' => 'akun-siswa.view-credentials', 'group' => null, 'perm' => 'view-akun-siswa-credentials'],
            ['key' => 'akun-siswa.print-credentials', 'group' => null, 'perm' => 'print-akun-siswa'],

            ['key' => 'pengaturan.view',     'group' => 'pengaturan', 'perm' => 'view-app-setting'],
            ['key' => 'pengaturan.update',   'group' => null,         'perm' => 'update-app-setting'],

            ['key' => 'auto-approve.view',   'group' => 'pengaturan', 'perm' => 'view-auto-approve-setting'],
            ['key' => 'auto-approve.update', 'group' => null,         'perm' => 'update-auto-approve-setting'],

            ['key' => 'branch.view',         'group' => 'pengaturan', 'perm' => 'view-branch'],
            ['key' => 'branch.create',       'group' => null,         'perm' => 'create-branch'],
            ['key' => 'branch.read',         'group' => null,         'perm' => 'read-branch'],
            ['key' => 'branch.update',       'group' => null,         'perm' => 'update-branch'],
            ['key' => 'branch.delete',       'group' => null,         'perm' => 'delete-branch'],
            ['key' => 'ui.branch_switcher.view', 'group' => null, 'perm' => 'view-all-branches'],

            ['key' => 'notification-setting.view', 'group' => 'pengaturan', 'perm' => 'view-notification-setting'],
            ['key' => 'notification-setting.update', 'group' => null,  'perm' => 'update-notification-setting'],

            ['key' => 'notification-logs.view',   'group' => 'pengaturan', 'perm' => 'view-notification-logs'],
            ['key' => 'notification-logs.retry', 'group' => null,      'perm' => 'retry-notification'],

            // RBAC
            ['key' => 'rbac',                'group' => 'pengaturan', 'perm' => 'manage-rbac'],
            ['key' => 'rbac.toggle',                'group' => 'pengaturan', 'perm' => 'toggle-active'],
            ['key' => 'permission.view',     'group' => null,         'perm' => 'view-permissions'],
            ['key' => 'permission.create',   'group' => null,         'perm' => 'create-permission'],
            ['key' => 'permission.update',   'group' => null,         'perm' => 'update-permission'],
            ['key' => 'permission.delete',   'group' => null,         'perm' => 'delete-permission'],

            ['key' => 'endpoint-mapping.view', 'group' => null,       'perm' => 'view-endpoint-mapping'],
            ['key' => 'endpoint-mapping.create', 'group' => null,     'perm' => 'create-endpoint-mapping'],
            ['key' => 'endpoint-mapping.update', 'group' => null,     'perm' => 'update-endpoint-mapping'],
            ['key' => 'endpoint-mapping.delete', 'group' => null,     'perm' => 'delete-endpoint-mapping'],

            ['key' => 'resource-registry.view', 'group' => null,      'perm' => 'view-resource-registry'],
            ['key' => 'resource-registry.create', 'group' => null,    'perm' => 'create-resource-registry'],
            ['key' => 'resource-registry.update', 'group' => null,    'perm' => 'update-resource-registry'],
            ['key' => 'resource-registry.delete', 'group' => null,    'perm' => 'delete-resource-registry'],

            // PORTAL (Khusus UI yang dibinding ke backend)
            ['key' => 'portal.billing',      'group' => null,         'perm' => 'view-own-billing'],
            ['key' => 'portal.tagihan',      'group' => null,         'perm' => 'view-tagihan-siswa'],
            ['key' => 'portal-beranda',      'group' => null,         'perm' => 'view-tagihan-siswa'],
            ['key' => 'portal-access',       'group' => null,         'perm' => 'view-own-billing'],
        ];

        foreach ($resources as $res) {
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

        $this->command->info('Page permissions seeded: '.count($resources).' resources.');
    }
}
