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
        $admin = Role::firstOrCreate(['name' => DefaultRoles::ADMIN->value]);
        Role::firstOrCreate(['name' => DefaultRoles::USER->value]);
        $siswa = Role::firstOrCreate(['name' => DefaultRoles::SISWA->value]);

        $superadmin->syncPermissions([]);

        $adminPermissions = collect(PermissionBinding::ADMIN_PERMISSIONS)
            ->flatten()
            ->map(fn($p) => $p->value)
            ->filter(fn($p) => $p !== Permission::UPDATE_MIDTRANS_CONFIG->value)
            ->values()
            ->toArray();
        $admin->syncPermissions($adminPermissions);

        $siswa->syncPermissions([
            Permission::VIEW_TAGIHAN_SISWA->value,
            Permission::VIEW_OWN_BILLING->value,
            Permission::PAY_TAGIHAN_ONLINE->value,
            Permission::PRINT_KWITANSI->value,
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Backfill group, audience, and label ──

        // Group assignment: permission name → group label
        $groupMap = [
            // Users
            'view-user' => 'Users', 'create-user' => 'Users', 'read-user' => 'Users',
            'update-user' => 'Users', 'delete-user' => 'Users',
            // Siswa
            'view-siswa' => 'Siswa', 'create-siswa' => 'Siswa', 'read-siswa' => 'Siswa',
            'update-siswa' => 'Siswa', 'delete-siswa' => 'Siswa',
            // Kelas
            'view-kelas' => 'Kelas', 'create-kelas' => 'Kelas', 'read-kelas' => 'Kelas',
            'update-kelas' => 'Kelas', 'delete-kelas' => 'Kelas',
            // Kategori
            'view-kategori' => 'Kategori', 'create-kategori' => 'Kategori', 'read-kategori' => 'Kategori',
            'update-kategori' => 'Kategori', 'delete-kategori' => 'Kategori',
            // Pembayaran
            'view-pembayaran' => 'Pembayaran', 'create-pembayaran' => 'Pembayaran',
            'delete-pembayaran' => 'Pembayaran', 'print-kwitansi' => 'Pembayaran',
            // Jenis Tagihan
            'view-jenis-tagihan' => 'Jenis Tagihan', 'create-jenis-tagihan' => 'Jenis Tagihan',
            'read-jenis-tagihan' => 'Jenis Tagihan', 'update-jenis-tagihan' => 'Jenis Tagihan',
            'delete-jenis-tagihan' => 'Jenis Tagihan',
            // Tagihan
            'view-tagihan' => 'Tagihan', 'create-tagihan' => 'Tagihan', 'read-tagihan' => 'Tagihan',
            'update-tagihan' => 'Tagihan', 'delete-tagihan' => 'Tagihan',
            // Pengeluaran
            'view-pengeluaran' => 'Pengeluaran', 'create-pengeluaran' => 'Pengeluaran',
            'read-pengeluaran' => 'Pengeluaran', 'update-pengeluaran' => 'Pengeluaran',
            'delete-pengeluaran' => 'Pengeluaran',
            // Approval Workflow
            'create-pengeluaran-request' => 'Approval Workflow', 'approve-pengeluaran' => 'Approval Workflow',
            'disburse-pengeluaran' => 'Approval Workflow',
            // Laporan
            'view-kas-harian' => 'Laporan', 'view-rekap-bulanan' => 'Laporan', 'export-laporan' => 'Laporan',
            // Tahun Ajaran
            'view-tahun-ajaran' => 'Tahun Ajaran', 'create-tahun-ajaran' => 'Tahun Ajaran',
            'update-tahun-ajaran' => 'Tahun Ajaran', 'delete-tahun-ajaran' => 'Tahun Ajaran',
            // Kenaikan Kelas
            'view-kenaikan-kelas' => 'Kenaikan Kelas', 'process-kenaikan-kelas' => 'Kenaikan Kelas',
            'undo-kenaikan-kelas' => 'Kenaikan Kelas',
            // Akun Siswa
            'view-akun-siswa' => 'Akun Siswa', 'generate-akun-siswa' => 'Akun Siswa',
            // Import Export
            'import-data' => 'Import Export', 'export-data' => 'Import Export',
            // Dashboard
            'view-dashboard' => 'Dashboard',
            // Branch
            'view-branch' => 'Branch', 'create-branch' => 'Branch', 'read-branch' => 'Branch',
            'update-branch' => 'Branch', 'delete-branch' => 'Branch',
            // Midtrans
            'pay-tagihan-online' => 'Midtrans (Admin)', 'view-midtrans-transactions' => 'Midtrans (Admin)',
            'sync-midtrans-transactions' => 'Midtrans (Admin)', 'view-midtrans-config' => 'Midtrans (Admin)',
            'update-midtrans-config' => 'Midtrans (Admin)',
            // Pengaturan
            'view-app-setting' => 'Pengaturan', 'update-app-setting' => 'Pengaturan',
            'view-notification-setting' => 'Pengaturan', 'update-notification-setting' => 'Pengaturan',
            'view-notification-logs' => 'Pengaturan',
            // Roles & Permissions
            'view-roles' => 'Roles & Permissions', 'create-role' => 'Roles & Permissions',
            'update-role' => 'Roles & Permissions', 'delete-role' => 'Roles & Permissions',
            'attach-role' => 'Roles & Permissions', 'detach-role' => 'Roles & Permissions',
            'view-permissions' => 'Roles & Permissions', 'attach-permissions' => 'Roles & Permissions',
            'detach-permissions' => 'Roles & Permissions', 'view-permission' => 'Roles & Permissions',
            'create-permission' => 'Roles & Permissions', 'edit-permission' => 'Roles & Permissions',
            'delete-permission' => 'Roles & Permissions', 'assign-permission' => 'Roles & Permissions',
        ];

        // Label mapping (dari permissionLabelDictionary + humanizePermission di RoleController)
        $labelMap = [
            'view-user' => 'Lihat User',
            'create-user' => 'Tambah User',
            'read-user' => 'Detail User',
            'update-user' => 'Ubah User',
            'delete-user' => 'Hapus User',
            'view-siswa' => 'Lihat Siswa',
            'create-siswa' => 'Tambah Siswa',
            'read-siswa' => 'Detail Siswa',
            'update-siswa' => 'Ubah Siswa',
            'delete-siswa' => 'Hapus Siswa',
            'view-kelas' => 'Lihat Kelas',
            'create-kelas' => 'Tambah Kelas',
            'read-kelas' => 'Detail Kelas',
            'update-kelas' => 'Ubah Kelas',
            'delete-kelas' => 'Hapus Kelas',
            'view-kategori' => 'Lihat Kategori',
            'create-kategori' => 'Tambah Kategori',
            'read-kategori' => 'Detail Kategori',
            'update-kategori' => 'Ubah Kategori',
            'delete-kategori' => 'Hapus Kategori',
            'view-pembayaran' => 'Lihat Pembayaran',
            'create-pembayaran' => 'Tambah Pembayaran',
            'delete-pembayaran' => 'Hapus Pembayaran',
            'print-kwitansi' => 'Cetak Kwitansi',
            'view-jenis-tagihan' => 'Lihat Jenis Tagihan',
            'create-jenis-tagihan' => 'Tambah Jenis Tagihan',
            'read-jenis-tagihan' => 'Detail Jenis Tagihan',
            'update-jenis-tagihan' => 'Ubah Jenis Tagihan',
            'delete-jenis-tagihan' => 'Hapus Jenis Tagihan',
            'view-tagihan' => 'Lihat Tagihan',
            'create-tagihan' => 'Tambah Tagihan',
            'read-tagihan' => 'Detail Tagihan',
            'update-tagihan' => 'Ubah Tagihan',
            'delete-tagihan' => 'Hapus Tagihan',
            'view-pengeluaran' => 'Lihat Pengeluaran',
            'create-pengeluaran' => 'Tambah Pengeluaran',
            'read-pengeluaran' => 'Detail Pengeluaran',
            'update-pengeluaran' => 'Ubah Pengeluaran',
            'delete-pengeluaran' => 'Hapus Pengeluaran',
            'create-pengeluaran-request' => 'Ajukan Pengeluaran',
            'approve-pengeluaran' => 'Setujui Pengeluaran',
            'disburse-pengeluaran' => 'Cairkan Pengeluaran',
            'view-kas-harian' => 'Lihat Kas Harian',
            'view-rekap-bulanan' => 'Lihat Rekap Bulanan',
            'export-laporan' => 'Ekspor Laporan',
            'view-tahun-ajaran' => 'Lihat Tahun Ajaran',
            'create-tahun-ajaran' => 'Tambah Tahun Ajaran',
            'update-tahun-ajaran' => 'Ubah Tahun Ajaran',
            'delete-tahun-ajaran' => 'Hapus Tahun Ajaran',
            'view-kenaikan-kelas' => 'Lihat Kenaikan Kelas',
            'process-kenaikan-kelas' => 'Proses Kenaikan Kelas',
            'undo-kenaikan-kelas' => 'Undo Kenaikan Kelas',
            'view-akun-siswa' => 'Lihat Akun Siswa',
            'generate-akun-siswa' => 'Generate Akun Siswa',
            'import-data' => 'Impor Data',
            'export-data' => 'Ekspor Data',
            'view-dashboard' => 'Lihat Dashboard',
            'view-own-billing' => 'Lihat Tagihan Sendiri',
            'view-branch' => 'Lihat Cabang',
            'create-branch' => 'Tambah Cabang',
            'read-branch' => 'Detail Cabang',
            'update-branch' => 'Ubah Cabang',
            'delete-branch' => 'Hapus Cabang',
            'pay-tagihan-online' => 'Bayar Tagihan Online',
            'view-midtrans-transactions' => 'Lihat Transaksi Midtrans',
            'sync-midtrans-transactions' => 'Sinkron Transaksi Midtrans',
            'view-midtrans-config' => 'Lihat Konfigurasi Midtrans',
            'update-midtrans-config' => 'Ubah Konfigurasi Midtrans',
            'view-app-setting' => 'Lihat Pengaturan Aplikasi',
            'update-app-setting' => 'Ubah Pengaturan Aplikasi',
            'view-notification-setting' => 'Lihat Pengaturan Notifikasi',
            'update-notification-setting' => 'Ubah Pengaturan Notifikasi',
            'view-notification-logs' => 'Riwayat Notifikasi',
            'view-roles' => 'Lihat Role',
            'create-role' => 'Tambah Role',
            'update-role' => 'Ubah Role',
            'delete-role' => 'Hapus Role',
            'attach-role' => 'Tetapkan Role ke User',
            'detach-role' => 'Lepaskan Role dari User',
            'view-permissions' => 'Lihat Daftar Permission',
            'attach-permissions' => 'Tetapkan Permission ke Role',
            'detach-permissions' => 'Lepaskan Permission dari Role',
            'view-permission' => 'Lihat Permission',
            'create-permission' => 'Buat Permission',
            'edit-permission' => 'Edit Permission',
            'delete-permission' => 'Hapus Permission',
            'assign-permission' => 'Assign Permission',
            'view-tagihan-siswa' => 'Lihat Halaman Tagihan Siswa',
        ];

        // Audience: admin = null (default), siswa = 'siswa'
        $siswaPerms = ['view-own-billing', 'view-tagihan-siswa', 'pay-tagihan-online', 'print-kwitansi'];

        foreach (SpatiePermission::all() as $perm) {
            $updates = [];

            if (in_array($perm->name, $siswaPerms)) {
                $updates['audience'] = 'siswa';
            }

            if (isset($groupMap[$perm->name]) && $perm->group === null) {
                $updates['group'] = $groupMap[$perm->name];
            }

            if (isset($labelMap[$perm->name]) && $perm->label === null) {
                $updates['label'] = $labelMap[$perm->name];
            }

            if ($updates !== []) {
                $perm->update($updates);
            }
        }

        // ── Seed page_permissions ──
        $pages = [
            ['route_pattern' => 'dashboard-page', 'permission_name' => 'view-dashboard'],
            ['route_pattern' => 'siswa*', 'permission_name' => 'view-siswa'],
            ['route_pattern' => 'kelas*', 'permission_name' => 'view-kelas'],
            ['route_pattern' => 'kategori*', 'permission_name' => 'view-kategori'],
            ['route_pattern' => 'kenaikan-kelas*', 'permission_name' => 'view-kenaikan-kelas'],
            ['route_pattern' => 'jenis-tagihan*', 'permission_name' => 'view-jenis-tagihan'],
            ['route_pattern' => 'pembayaran*', 'permission_name' => 'view-pembayaran'],
            ['route_pattern' => 'tagihan*', 'permission_name' => 'view-tagihan'],
            ['route_pattern' => 'pengeluaran*', 'permission_name' => 'view-pengeluaran'],
            ['route_pattern' => 'pengeluaran-request*', 'permission_name' => 'view-pengeluaran'],
            ['route_pattern' => 'transaksi-midtrans*', 'permission_name' => 'view-midtrans-transactions'],
            ['route_pattern' => 'laporan*', 'permission_name' => 'view-kas-harian'],
            ['route_pattern' => 'setting*', 'permission_name' => 'view-app-setting'],
            ['route_pattern' => 'notification-settings*', 'permission_name' => 'view-notification-setting'],
            ['route_pattern' => 'notification-log*', 'permission_name' => 'view-notification-logs'],
            ['route_pattern' => 'branch-approval-settings*', 'permission_name' => 'view-app-setting'],
            ['route_pattern' => 'users*', 'permission_name' => 'view-user'],
            ['route_pattern' => 'akun-siswa*', 'permission_name' => 'view-akun-siswa'],
            ['route_pattern' => 'rbac*', 'permission_name' => 'view-permissions'],
            ['route_pattern' => 'tahun-ajaran*', 'permission_name' => 'view-tahun-ajaran'],
            ['route_pattern' => 'portal/*', 'permission_name' => 'view-tagihan-siswa'],
        ];

        foreach ($pages as $page) {
            \App\Models\PagePermission::firstOrCreate(
                ['route_pattern' => $page['route_pattern'], 'permission_name' => $page['permission_name']],
                ['guard_name' => 'web', 'is_active' => true],
            );
        }
    }
}
