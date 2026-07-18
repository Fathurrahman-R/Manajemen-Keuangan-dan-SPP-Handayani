<?php

namespace Database\Seeders;

use App\Models\PermissionEndpoint;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Seed basic endpoint mapping for RBAC.
 *
 * Maps resource_key → permission for common API endpoints.
 * Covers the most frequently used endpoints so the table is not empty
 * on a fresh install.
 */
class PermissionEndpointSeeder extends Seeder
{
    /**
     * Each entry: [resource_key, permission_name, group, description]
     */
    private const ENDPOINTS = [
        // ── Dashboard ──
        ['dashboard',              'view-dashboard',              'Dashboard', 'Dashboard Admin'],
        ['portal',        'view-own-billing',            'Portal', 'Dashboard siswa/wali'],

        // ── Siswa ──
        ['siswa.view',             'view-siswa',                  'Siswa',     'Melihat daftar siswa'],
        ['siswa.create',           'create-siswa',                'Siswa',     'Menambah data siswa'],
        ['siswa.read',             'read-siswa',                  'Siswa',     'Detail data siswa'],
        ['siswa.update',           'update-siswa',                'Siswa',     'Mengubah data siswa'],
        ['siswa.delete',           'delete-siswa',                'Siswa',     'Menghapus data siswa'],

        // ── Kelas ──
        ['kelas.view',             'view-kelas',                  'Kelas',     'Melihat daftar kelas'],
        ['kelas.create',           'create-kelas',                'Kelas',     'Menambah data kelas'],
        ['kelas.read',             'read-kelas',                  'Kelas',     'Detail data kelas'],
        ['kelas.update',           'update-kelas',                'Kelas',     'Mengubah data kelas'],
        ['kelas.delete',           'delete-kelas',                'Kelas',     'Menghapus data kelas'],

        // ── Kategori ──
        ['kategori.view',          'view-kategori',               'Kategori',  'Melihat daftar kategori'],
        ['kategori.create',        'create-kategori',             'Kategori',  'Menambah data kategori'],
        ['kategori.read',          'read-kategori',               'Kategori',  'Detail data kategori'],
        ['kategori.update',        'update-kategori',             'Kategori',  'Mengubah data kategori'],
        ['kategori.delete',        'delete-kategori',             'Kategori',  'Menghapus data kategori'],

        // ── Tahun Ajaran ──
        ['tahun-ajaran.view',      'view-tahun-ajaran',           'Tahun Ajaran', 'Melihat tahun ajaran'],
        ['tahun-ajaran.create',    'create-tahun-ajaran',         'Tahun Ajaran', 'Menambah tahun ajaran'],
        ['tahun-ajaran.update',    'update-tahun-ajaran',         'Tahun Ajaran', 'Mengubah tahun ajaran'],
        ['tahun-ajaran.delete',    'delete-tahun-ajaran',         'Tahun Ajaran', 'Menghapus tahun ajaran'],
        ['tahun-ajaran.toggle',    'toggle-tahun-ajaran',         'Tahun Ajaran', 'Active/Deactive tahun ajaran'],

        // ── Kenaikan Kelas ──
        ['kenaikan-kelas.view',    'view-kenaikan-kelas',         'Kenaikan Kelas', 'Melihat kenaikan kelas'],
        ['kenaikan-kelas.process',  'process-kenaikan-kelas',      'Kenaikan Kelas', 'Proses kenaikan kelas'],
        ['kenaikan-kelas.undo',  'undo-kenaikan-kelas',         'Kenaikan Kelas', 'Batalkan kenaikan kelas'],
        ['kenaikan-kelas.detail',  'view-detail-kenaikan',         'Kenaikan Kelas', 'Lihat Detail Kenaikan'],

        // ── Jenis Tagihan ──
        ['jenis-tagihan.view',     'view-jenis-tagihan',          'Jenis Tagihan', 'Melihat jenis tagihan'],
        ['jenis-tagihan.create',   'create-jenis-tagihan',        'Jenis Tagihan', 'Menambah jenis tagihan'],
        ['jenis-tagihan.update',   'update-jenis-tagihan',        'Jenis Tagihan', 'Mengubah jenis tagihan'],
        ['jenis-tagihan.delete',   'delete-jenis-tagihan',        'Jenis Tagihan', 'Menghapus jenis tagihan'],

        // ── Tagihan ──
        ['tagihan.view',           'view-tagihan',                'Tagihan',   'Melihat daftar tagihan'],
        ['tagihan.create',         'create-tagihan',              'Tagihan',   'Menambah tagihan'],
        //        ['tagihan.read',           'read-tagihan',                'Tagihan',   'Detail tagihan'],
        ['tagihan.update',         'update-tagihan',              'Tagihan',   'Mengubah tagihan'],
        ['tagihan.delete',         'delete-tagihan',              'Tagihan',   'Menghapus tagihan'],
        ['tagihan.export',         'export-data',              'Tagihan',   'Export data tagihan'],
        ['tagihan.siswa',          'view-own-billing',          'Tagihan',   'Tagihan per siswa'],

        // ── Pembayaran ──
        ['pembayaran.view',        'view-pembayaran',             'Pembayaran', 'Melihat pembayaran'],
        ['pembayaran.kwitansi', 'print-kwitansi', 'Kwitansi', 'Print Kwitansi'],
        ['pembayaran.create',      'create-pembayaran',           'Pembayaran', 'Mencatat pembayaran'],
        ['pembayaran.delete',      'delete-pembayaran',           'Pembayaran', 'Menghapus pembayaran'],
        ['pembayaran.siswa',       'view-own-billing',          'Pembayaran', 'Pembayaran siswa'],
        ['pembayaran.siswa',       'view-own-billing',          'Pembayaran', 'Pembayaran siswa'],

        // ── Pengeluaran ──
        //        ['pengeluaran.view',       'view-pengeluaran',            'Pengeluaran', 'Melihat pengeluaran'],
        //        ['pengeluaran.create',     'create-pengeluaran',          'Pengeluaran', 'Mencatat pengeluaran'],
        //        ['pengeluaran.update',     'update-pengeluaran',          'Pengeluaran', 'Mengubah pengeluaran'],
        //        ['pengeluaran.delete',     'delete-pengeluaran',          'Pengeluaran', 'Menghapus pengeluaran'],

        // ── Pengeluaran Request ──
        ['pengeluaran.view',   'view-pengeluaran',   'Pengeluaran', 'Melihat request pengeluaran'],
        ['pengeluaran.create', 'create-pengeluaran', 'Pengeluaran', 'Membuat request pengeluaran'],
        ['pengeluaran.update', 'update-pengeluaran', 'Pengeluaran', 'Mengubah request pengeluaran'],
        ['pengeluaran.delete', 'delete-pengeluaran', 'Pengeluaran', 'Menghapus request pengeluaran'],
        ['pengeluaran.approve', 'approve-pengeluaran',       'Pengeluaran', 'Menyetujui request'],
        ['pengeluaran.disburse', 'disburse-pengeluaran',       'Pengeluaran', 'Mencairkan request'],

        // ── Laporan ──
        ['laporan.kas',        'view-kas-harian',             'Laporan',   'Melihat kas harian'],
        ['laporan.kas-detail',        'detail-kas-harian',             'Laporan',   'Melihat kas harian'],
        ['laporan.rekap',     'view-rekap-bulanan',          'Laporan',   'Melihat rekap bulanan'],
        ['laporan.rekap-detail',     'detail-rekap-bulanan',          'Laporan',   'Melihat rekap bulanan'],
        ['laporan.export',           'export-laporan',             'Laporan',   'Melihat halaman laporan'],

        // ── Users ──
        ['users.view',             'view-user',                   'Users',     'Melihat daftar user'],
        ['users.read',             'read-user',                   'Users',     'Melihat detail user'],
        ['users.create',           'create-user',                 'Users',     'Menambah user'],
        ['users.update',           'update-user',                 'Users',     'Mengubah user'],
        ['users.delete',           'delete-user',                 'Users',     'Menghapus user'],
        ['users.toggle',           'toggle-user',                 'Users',     'Active/Deactive user'],

        // ── RBAC ──
        ['rbac',                   'manage-rbac',             'RBAC',      'Melihat halaman RBAC'],
        ['permission.view',                   'view-permissions',             'RBAC',      'Melihat daftar permission'],
        ['permission.create',                   'create-permission',             'RBAC',      'Membuat permission baru'],
        ['permission.update',                   'update-permission',             'RBAC',      'Mengubah permission'],
        ['permission.delete',                   'delete-permission',             'RBAC',      'Menghapus permission'],

        ['role.view',                   'view-roles',             'RBAC',      'Melihat daftar role'],
        ['role.create',                   'create-role',             'RBAC',      'Membuat role baru'],
        ['role.update',                   'update-role',             'RBAC',      'Mengubah role'],
        ['role.delete',                   'delete-role',             'RBAC',      'Menghapus role'],

        ['endpoint-mapping.view',                   'view-endpoint-mapping',             'RBAC',      'Melihat daftar endpoint mapping'],
        ['endpoint-mapping.create',                   'create-endpoint-mapping',             'RBAC',      'Membuat endpoint mapping baru'],
        ['endpoint-mapping.update',                   'update-endpoint-mapping',             'RBAC',      'Mengubah endpoint mapping'],
        ['endpoint-mapping.delete',                   'delete-endpoint-mapping',             'RBAC',      'Menghapus endpoint mapping'],

        ['resource-registry.view',                   'view-resource-registry',             'RBAC',      'Melihat daftar resoure registry'],
        ['resource-registry.create',                   'create-resource-registry',             'RBAC',      'Membuat resource registry baru'],
        ['resource-registry.update',                   'update-resource-registry',             'RBAC',      'Mengubah resource registry'],
        ['resource-registry.delete',                   'delete-resource-registry',             'RBAC',      'Menghapus resource registry'],

        // ── Branch ──
        ['branch.view',            'view-branch',                 'Branch',    'Melihat cabang'],
        ['branch.create',          'create-branch',               'Branch',    'Menambah cabang'],
        ['branch.read',          'read-branch',               'Branch',    'Melihat detail cabang'],
        ['branch.update',          'update-branch',               'Branch',    'Mengubah cabang'],
        ['branch.delete',          'delete-branch',               'Branch',    'Menghapus cabang'],
        ['api.branch.switch',      'view-all-branches',           'Branch',    'Mengganti konteks cabang'],

        // ── Midtrans ──
        ['midtrans.admin',         'view-midtrans-transactions',  'Midtrans',  'Admin transaksi Midtrans'],
        ['midtrans.pay',        'pay-tagihan-online',          'Midtrans',  'Pembayaran online via Midtrans'],
        ['midtrans.sync',        'sync-midtrans-transactions',          'Midtrans',  'Sinkronisasi status transaksi Midtrans'],
        //        ['midtrans-config.view',   'view-midtrans-config',        'Midtrans',  'Melihat konfigurasi Midtrans'],
        //        ['midtrans-config.update', 'update-midtrans-config',      'Midtrans',  'Mengubah konfigurasi Midtrans'],

        // ── Pengaturan ──
        ['pengaturan.view',        'view-app-setting',            'Pengaturan', 'Melihat pengaturan'],
        ['pengaturan.update',      'update-app-setting',          'Pengaturan', 'Mengubah pengaturan'],

        ['auto-approve.view',        'view-auto-approve-setting',            'Pengaturan', 'Melihat pengaturan auto approve'],
        ['auto-approve.update',      'update-auto-approve-setting',          'Pengaturan', 'Mengubah pengaturan auto approve'],

        // ── Akun Siswa ──
        ['akun-siswa.view',        'view-akun-siswa',             'Akun Siswa', 'Melihat akun siswa'],
        ['akun-siswa.create',      'generate-akun-siswa',         'Akun Siswa', 'Generate akun siswa'],
        ['akun-siswa.reset',      'reset-akun-siswa-password',           'Akun Siswa', 'Reset password akun siswa'],
        ['akun-siswa.toggle',      'toggle-akun-siswa',           'Akun Siswa', 'Active/Deactive akun siswa'],
        ['akun-siswa.view-credentials',      'view-akun-siswa-credentials',           'Akun Siswa', 'Active/Deactive akun siswa'],
        ['akun-siswa.print-credentials',      'print-akun-siswa',           'Akun Siswa', 'Active/Deactive akun siswa'],

        // ── Import / Export ──
        ['export-data',   'export-data',                 'Import Export', 'Export data'],
        ['import-data',   'import-data',                 'Import Export', 'Import data'],

        // ── Notification ──
        ['notification-setting.view', 'view-notification-setting',   'Notifikasi', 'Melihat pengaturan notifikasi'],
        ['notification-setting.update',   'update-notification-setting',           'Notifikasi', 'Mengupdate pengaturan notifikasi'],
        ['notification-logs.view',  'view-notification-logs',    'Notifikasi', 'Melihat log notifikasi'],
        ['notification-logs.retry', 'retry-notification',        'Notifikasi', 'Retry notifikasi'],

        // ── Parent / Wali ──
        ['ayah.view',              'view-siswa',                  'Siswa',     'Melihat data ayah'],
        ['ibu.view',               'view-siswa',                  'Siswa',     'Melihat data ibu'],
        ['wali.view',              'view-siswa',                  'Wali',      'Melihat data wali'],
        ['wali.create',            'create-siswa',                'Wali',      'Menambah data wali'],
        ['wali.update',            'update-siswa',                'Wali',      'Mengubah data wali'],
        ['wali.delete',            'delete-siswa',                'Wali',      'Menghapus data wali'],
    ];

    public function run(): void
    {
        $created = 0;
        $updated = 0;

        foreach (self::ENDPOINTS as [$resourceKey, $permName, $group, $description]) {
            $permission = Permission::where('name', $permName)->first();

            if (! $permission) {
                $this->command->warn("  ! Permission '{$permName}' not found — skipping endpoint '{$resourceKey}'.");

                continue;
            }

            $exists = PermissionEndpoint::where('resource_key', $resourceKey)->exists();

            PermissionEndpoint::updateOrCreate(
                ['resource_key' => $resourceKey],
                [
                    'permission_id' => $permission->id,
                    'group' => $group,
                    'description' => $description,
                    'is_active' => true,
                ]
            );

            if ($exists) {
                $updated++;
            } else {
                $created++;
            }
        }

        $this->command->info("Endpoint permissions seeded: {$created} created, {$updated} updated.");
    }
}
