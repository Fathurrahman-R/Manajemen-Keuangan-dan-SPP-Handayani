<?php

namespace Database\Seeders;

use App\Enum\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Seed label, group, and audience columns for all permissions
 * defined in App\Enum\Permission.
 *
 * Run after `permissions:sync` so that all enum cases exist in the DB.
 */
class PermissionMetadataSeeder extends Seeder
{
    /**
     * Map each Permission case → [label, group, audience].
     *
     * audience = null   → visible in "Admin / Karyawan" section
     * audience = 'siswa' → visible in "Siswa / Wali" section
     */
    private const METADATA = [
        // ── Dashboard ──
        'view-dashboard' => ['label' => 'Lihat Dashboard',                'group' => 'Dashboard',             'audience' => null],
        'view-own-billing' => ['label' => 'Lihat Tagihan Sendiri',          'group' => 'Tagihan & Pembayaran',  'audience' => 'siswa'],

        // ── Siswa ──
        'view-siswa' => ['label' => 'Lihat Siswa',                    'group' => 'Siswa',                 'audience' => null],
        'create-siswa' => ['label' => 'Tambah Siswa',                   'group' => 'Siswa',                 'audience' => null],
        'read-siswa' => ['label' => 'Detail Siswa',                   'group' => 'Siswa',                 'audience' => null],
        'update-siswa' => ['label' => 'Ubah Siswa',                     'group' => 'Siswa',                 'audience' => null],
        'delete-siswa' => ['label' => 'Hapus Siswa',                    'group' => 'Siswa',                 'audience' => null],

        // ── Kelas ──
        'view-kelas' => ['label' => 'Lihat Kelas',                    'group' => 'Kelas',                 'audience' => null],
        'create-kelas' => ['label' => 'Tambah Kelas',                   'group' => 'Kelas',                 'audience' => null],
        'read-kelas' => ['label' => 'Detail Kelas',                   'group' => 'Kelas',                 'audience' => null],
        'update-kelas' => ['label' => 'Ubah Kelas',                     'group' => 'Kelas',                 'audience' => null],
        'delete-kelas' => ['label' => 'Hapus Kelas',                    'group' => 'Kelas',                 'audience' => null],

        // ── Kategori ──
        'view-kategori' => ['label' => 'Lihat Kategori',                 'group' => 'Kategori',              'audience' => null],
        'create-kategori' => ['label' => 'Tambah Kategori',                'group' => 'Kategori',              'audience' => null],
        'read-kategori' => ['label' => 'Detail Kategori',                'group' => 'Kategori',              'audience' => null],
        'update-kategori' => ['label' => 'Ubah Kategori',                  'group' => 'Kategori',              'audience' => null],
        'delete-kategori' => ['label' => 'Hapus Kategori',                 'group' => 'Kategori',              'audience' => null],

        // ── Tahun Ajaran ──
        'view-tahun-ajaran' => ['label' => 'Lihat Tahun Ajaran',             'group' => 'Tahun Ajaran',          'audience' => null],
        'create-tahun-ajaran' => ['label' => 'Tambah Tahun Ajaran',            'group' => 'Tahun Ajaran',          'audience' => null],
        'update-tahun-ajaran' => ['label' => 'Ubah Tahun Ajaran',              'group' => 'Tahun Ajaran',          'audience' => null],
        'delete-tahun-ajaran' => ['label' => 'Hapus Tahun Ajaran',             'group' => 'Tahun Ajaran',          'audience' => null],
        'toggle-tahun-ajaran' => ['label' => 'Aktif/Nonaktifkan Tahun Ajaran', 'group' => 'Tahun Ajaran',          'audience' => null],

        // ── Kenaikan Kelas ──
        'view-kenaikan-kelas' => ['label' => 'Lihat Kenaikan Kelas',           'group' => 'Kenaikan Kelas',        'audience' => null],
        'process-kenaikan-kelas' => ['label' => 'Proses Kenaikan Kelas',          'group' => 'Kenaikan Kelas',        'audience' => null],
        'undo-kenaikan-kelas' => ['label' => 'Batalkan Kenaikan Kelas',        'group' => 'Kenaikan Kelas',        'audience' => null],
        'view-detail-kenaikan' => ['label' => 'Detail Kenaikan Kelas',          'group' => 'Kenaikan Kelas',        'audience' => null],

        // ── Jenis Tagihan ──
        'view-jenis-tagihan' => ['label' => 'Lihat Jenis Tagihan',            'group' => 'Jenis Tagihan',         'audience' => null],
        'create-jenis-tagihan' => ['label' => 'Tambah Jenis Tagihan',           'group' => 'Jenis Tagihan',         'audience' => null],
        'update-jenis-tagihan' => ['label' => 'Ubah Jenis Tagihan',             'group' => 'Jenis Tagihan',         'audience' => null],
        'delete-jenis-tagihan' => ['label' => 'Hapus Jenis Tagihan',            'group' => 'Jenis Tagihan',         'audience' => null],

        // ── Tagihan ──
        'view-tagihan' => ['label' => 'Lihat Tagihan',                  'group' => 'Tagihan',               'audience' => null],
        'create-tagihan' => ['label' => 'Tambah Tagihan',                 'group' => 'Tagihan',               'audience' => null],
        'update-tagihan' => ['label' => 'Ubah Tagihan',                   'group' => 'Tagihan',               'audience' => null],
        'delete-tagihan' => ['label' => 'Hapus Tagihan',                  'group' => 'Tagihan',               'audience' => null],

        // ── Pembayaran ──
        'view-pembayaran' => ['label' => 'Lihat Pembayaran',               'group' => 'Pembayaran',            'audience' => null],
        'create-pembayaran' => ['label' => 'Tambah Pembayaran',              'group' => 'Pembayaran',            'audience' => null],
        'delete-pembayaran' => ['label' => 'Hapus Pembayaran',               'group' => 'Pembayaran',            'audience' => null],
        'print-kwitansi' => ['label' => 'Cetak Kwitansi',                 'group' => 'Pembayaran',            'audience' => null],

        // ── Pengeluaran ──
        'view-pengeluaran' => ['label' => 'Lihat Pengeluaran',              'group' => 'Pengeluaran',           'audience' => null],
        'create-pengeluaran' => ['label' => 'Tambah Pengeluaran',             'group' => 'Pengeluaran',           'audience' => null],
        'update-pengeluaran' => ['label' => 'Ubah Pengeluaran',               'group' => 'Pengeluaran',           'audience' => null],
        'delete-pengeluaran' => ['label' => 'Hapus Pengeluaran',              'group' => 'Pengeluaran',           'audience' => null],
        'approve-pengeluaran' => ['label' => 'Setujui Pengeluaran',            'group' => 'Pengeluaran',     'audience' => null],
        'disburse-pengeluaran' => ['label' => 'Cairkan Pengeluaran',            'group' => 'Pengeluaran',     'audience' => null],

        // ── Laporan ──
        'view-kas-harian' => ['label' => 'Lihat Kas Harian',               'group' => 'Laporan',               'audience' => null],
        'detail-kas-harian' => ['label' => 'Detail Kas Harian',              'group' => 'Laporan',               'audience' => null],
        'view-rekap-bulanan' => ['label' => 'Lihat Rekap Bulanan',            'group' => 'Laporan',               'audience' => null],
        'detail-rekap-bulanan' => ['label' => 'Detail Rekap Bulanan',           'group' => 'Laporan',               'audience' => null],
        'export-laporan' => ['label' => 'Ekspor Laporan',                 'group' => 'Laporan',               'audience' => null],
        'import-data' => ['label' => 'Impor Data',                     'group' => 'Import / Ekspor',       'audience' => null],
        'export-data' => ['label' => 'Ekspor Data',                    'group' => 'Import / Ekspor',       'audience' => null],
        'view-import-export-job' => ['label' => 'Lihat Pekerjaan Import/Ekspor',  'group' => 'Import / Ekspor',       'audience' => null],

        // ── User Management ──
        'view-user' => ['label' => 'Lihat User',                     'group' => 'Users',                 'audience' => null],
        'create-user' => ['label' => 'Tambah User',                    'group' => 'Users',                 'audience' => null],
        'read-user' => ['label' => 'Detail User',                    'group' => 'Users',                 'audience' => null],
        'update-user' => ['label' => 'Ubah User',                      'group' => 'Users',                 'audience' => null],
        'delete-user' => ['label' => 'Hapus User',                     'group' => 'Users',                 'audience' => null],
        'toggle-user' => ['label' => 'Aktif/Nonaktifkan User',         'group' => 'Users',                 'audience' => null],

        // ── Roles & Permissions ──
        'view-roles' => ['label' => 'Lihat Role',                     'group' => 'Roles & Permissions',   'audience' => null],
        'create-role' => ['label' => 'Tambah Role',                    'group' => 'Roles & Permissions',   'audience' => null],
        'update-role' => ['label' => 'Ubah Role',                      'group' => 'Roles & Permissions',   'audience' => null],
        'delete-role' => ['label' => 'Hapus Role',                     'group' => 'Roles & Permissions',   'audience' => null],
        'attach-role' => ['label' => 'Tetapkan Role ke User',          'group' => 'Roles & Permissions',   'audience' => null],
        'view-permissions' => ['label' => 'Lihat Permission',               'group' => 'Roles & Permissions',   'audience' => null],
        'create-permission' => ['label' => 'Tambah Permission',              'group' => 'Roles & Permissions',   'audience' => null],
        'update-permission' => ['label' => 'Ubah Permission',                'group' => 'Roles & Permissions',   'audience' => null],
        'delete-permission' => ['label' => 'Hapus Permission',               'group' => 'Roles & Permissions',   'audience' => null],
        'attach-permission' => ['label' => 'Tetapkan Permission',            'group' => 'Roles & Permissions',   'audience' => null],

        // ── RBAC Management ──
        'manage-rbac' => ['label' => 'Kelola RBAC',                    'group' => 'RBAC Management',       'audience' => null],
        'toggle-active' => ['label' => 'Aktif/Nonaktifkan Status',       'group' => 'RBAC Management',       'audience' => null],
        'bind-permission' => ['label' => 'Bind Permission',                'group' => 'RBAC Management',       'audience' => null],
        'view-endpoint-mapping' => ['label' => 'Lihat Endpoint Mapping',         'group' => 'RBAC Management',       'audience' => null],
        'create-endpoint-mapping' => ['label' => 'Tambah Endpoint Mapping',        'group' => 'RBAC Management',       'audience' => null],
        'update-endpoint-mapping' => ['label' => 'Ubah Endpoint Mapping',          'group' => 'RBAC Management',       'audience' => null],
        'delete-endpoint-mapping' => ['label' => 'Hapus Endpoint Mapping',         'group' => 'RBAC Management',       'audience' => null],
        'view-resource-registry' => ['label' => 'Lihat Resource Registry',        'group' => 'RBAC Management',       'audience' => null],
        'create-resource-registry' => ['label' => 'Tambah Resource Registry',       'group' => 'RBAC Management',       'audience' => null],
        'update-resource-registry' => ['label' => 'Ubah Resource Registry',         'group' => 'RBAC Management',       'audience' => null],
        'delete-resource-registry' => ['label' => 'Hapus Resource Registry',        'group' => 'RBAC Management',       'audience' => null],

        // ── Akun Siswa ──
        'view-akun-siswa' => ['label' => 'Lihat Akun Siswa',               'group' => 'Akun Siswa',            'audience' => null],
        'generate-akun-siswa' => ['label' => 'Generate Akun Siswa',            'group' => 'Akun Siswa',            'audience' => null],
        'toggle-akun-siswa' => ['label' => 'Aktif/Nonaktifkan Akun',         'group' => 'Akun Siswa',            'audience' => null],
        'reset-akun-siswa-password' => ['label' => 'Reset Password Akun Siswa',     'group' => 'Akun Siswa',            'audience' => null],
        'view-akun-siswa-credentials' => ['label' => 'Lihat Credential Akun Siswa',    'group' => 'Akun Siswa',            'audience' => null],
        'print-akun-siswa' => ['label' => 'Cetak Akun Siswa',               'group' => 'Akun Siswa',            'audience' => null],

        // ── Pengaturan (App Setting, Branch, Notif) ──
        'view-app-setting' => ['label' => 'Lihat Pengaturan Aplikasi',      'group' => 'Pengaturan',            'audience' => null],
        'update-app-setting' => ['label' => 'Ubah Pengaturan Aplikasi',       'group' => 'Pengaturan',            'audience' => null],

        'view-auto-approve-setting' => ['label' => 'Lihat Pengaturan Auto Approve',  'group' => 'Pengaturan',            'audience' => null],
        'update-auto-approve-setting' => ['label' => 'Ubah Pengaturan Auto Approve',   'group' => 'Pengaturan',            'audience' => null],

        'view-branch' => ['label' => 'Lihat Cabang',                   'group' => 'Cabang',                'audience' => null],
        'create-branch' => ['label' => 'Tambah Cabang',                  'group' => 'Cabang',                'audience' => null],
        'read-branch' => ['label' => 'Detail Cabang',                  'group' => 'Cabang',                'audience' => null],
        'update-branch' => ['label' => 'Ubah Cabang',                    'group' => 'Cabang',                'audience' => null],
        'delete-branch' => ['label' => 'Hapus Cabang',                   'group' => 'Cabang',                'audience' => null],

        // ── Midtrans ──
        'view-midtrans-transactions' => ['label' => 'Lihat Transaksi Midtrans',       'group' => 'Midtrans',              'audience' => null],
        'sync-midtrans-transactions' => ['label' => 'Sinkron Transaksi Midtrans',     'group' => 'Midtrans',              'audience' => null],
        'view-midtrans-config' => ['label' => 'Lihat Konfigurasi Midtrans',     'group' => 'Midtrans',              'audience' => null],
        'update-midtrans-config' => ['label' => 'Ubah Konfigurasi Midtrans',      'group' => 'Midtrans',              'audience' => null],
        'pay-tagihan-online' => ['label' => 'Bayar Tagihan Online',           'group' => 'Midtrans',              'audience' => 'siswa'],

        // ── Notifikasi ──
        'view-notification-setting' => ['label' => 'Lihat Pengaturan Notifikasi',    'group' => 'Notifikasi',            'audience' => null],
        'update-notification-setting' => ['label' => 'Ubah Pengaturan Notifikasi',     'group' => 'Notifikasi',            'audience' => null],
        'view-notification-logs' => ['label' => 'Lihat Log Notifikasi',           'group' => 'Notifikasi',            'audience' => null],
        'retry-notification' => ['label' => 'Ulang Notifikasi',               'group' => 'Notifikasi',            'audience' => null],
    ];

    public function run(): void
    {
        $updated = 0;
        $skipped = 0;

        foreach (self::METADATA as $name => $meta) {
            $perm = SpatiePermission::where('name', $name)->first();

            if (! $perm) {
                $this->command->warn("  ! Permission '{$name}' not found in DB — skipping.");
                $skipped++;

                continue;
            }

            $changes = [];
            foreach ($meta as $field => $value) {
                if ($perm->getAttribute($field) !== $value) {
                    $changes[$field] = $value;
                }
            }

            if (! empty($changes)) {
                $perm->update($changes);
                $updated++;
            }
        }

        $this->command->info("Permission metadata seeded: {$updated} updated, {$skipped} skipped (not found).");
    }
}
