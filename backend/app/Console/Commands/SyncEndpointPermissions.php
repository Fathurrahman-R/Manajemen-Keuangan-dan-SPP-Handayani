<?php

namespace App\Console\Commands;

use App\Models\PermissionEndpoint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncEndpointPermissions extends Command
{
    protected $signature = 'permissions:sync-endpoints
        {--clear : Hapus semua data endpoint sebelum insert ulang}
        {--prune : Hapus mapping yang tidak ada di route config}';

    protected $description = 'Sinkronkan permission_endpoints dengan mapping resource_key → permission_name.';

    public function getMapping(): array
    {
        return [
            // Users
            'users.view' => 'view-user',
            'users.create' => 'create-user',
            'users.update' => 'update-user',
            'users.delete' => 'delete-user',
            // RBAC
            'rbac' => 'view-permissions',
            // Dashboard
            'dashboard.view' => 'view-dashboard',
            'dashboard.siswa' => 'view-own-billing',
            // Siswa
            'siswa.view' => 'view-siswa',
            'siswa.create' => 'create-siswa',
            'siswa.update' => 'update-siswa',
            'siswa.delete' => 'delete-siswa',
            // Kelas
            'kelas.view' => 'view-kelas',
            'kelas.create' => 'create-kelas',
            'kelas.update' => 'update-kelas',
            'kelas.delete' => 'delete-kelas',
            // Kategori
            'kategori.view' => 'view-kategori',
            'kategori.create' => 'create-kategori',
            'kategori.update' => 'update-kategori',
            'kategori.delete' => 'delete-kategori',
            // Wali
            'wali.view' => 'view-siswa',
            'wali.create' => 'create-siswa',
            'wali.update' => 'update-siswa',
            'wali.delete' => 'delete-siswa',
            // Parent search
            'ayah.view' => 'view-siswa',
            'ibu.view' => 'view-siswa',
            // Tagihan
            'tagihan.view' => 'view-tagihan',
            'tagihan.create' => 'create-tagihan',
            'tagihan.update' => 'update-tagihan',
            'tagihan.delete' => 'delete-tagihan',
            'tagihan.siswa' => 'view-tagihan-siswa',
            // Pembayaran
            'pembayaran.view' => 'view-pembayaran',
            'pembayaran.create' => 'create-pembayaran',
            'pembayaran.delete' => 'delete-pembayaran',
            'pembayaran.siswa' => 'view-own-billing',
            // Pengeluaran
            'pengeluaran.view' => 'view-pengeluaran',
            'pengeluaran.create' => 'create-pengeluaran',
            'pengeluaran.update' => 'update-pengeluaran',
            'pengeluaran.delete' => 'delete-pengeluaran',
            // Jenis Tagihan
            'jenis-tagihan.view' => 'view-jenis-tagihan',
            'jenis-tagihan.create' => 'create-jenis-tagihan',
            'jenis-tagihan.update' => 'update-jenis-tagihan',
            'jenis-tagihan.delete' => 'delete-jenis-tagihan',
            // Pengaturan
            'pengaturan.view' => 'view-app-setting',
            'pengaturan.update' => 'update-app-setting',
            // Notification Logs
            'notification-logs.view' => 'view-notification-logs',
            'notification-logs.create' => 'send-notification',
            // Notifications
            'notifications.view' => 'view-notification-setting',
            'notifications.update' => 'update-notification-setting',
            // Laporan
            'laporan.view' => 'view-kas-harian',
            // Tahun Ajaran
            'tahun-ajaran.view' => 'view-tahun-ajaran',
            'tahun-ajaran.create' => 'create-tahun-ajaran',
            'tahun-ajaran.update' => 'update-tahun-ajaran',
            'tahun-ajaran.delete' => 'delete-tahun-ajaran',
            // Kenaikan Kelas
            'kenaikan-kelas.view' => 'view-kenaikan-kelas',
            'kenaikan-kelas.create' => 'process-kenaikan-kelas',
            'kenaikan-kelas.update' => 'undo-kenaikan-kelas',
            // Akun Siswa
            'akun-siswa.view' => 'view-akun-siswa',
            'akun-siswa.create' => 'generate-akun-siswa',
            'akun-siswa.update' => 'reset-akun-siswa-password',
            // Pengeluaran Request
            'pengeluaran-request.view' => 'view-pengeluaran-request',
            'pengeluaran-request.create' => 'create-pengeluaran-request',
            'pengeluaran-request.update' => 'create-pengeluaran-request',
            'pengeluaran-request.delete' => 'delete-pengeluaran',
            'pengeluaran-request.approve' => 'approve-pengeluaran',
            // Import / Export
            'import-export.export' => 'export-data',
            'import-export.import' => 'import-data',
            'import-export.job-status' => 'view-import-export-job',
            // Midtrans
            'midtrans.portal' => 'pay-tagihan-online',
            'midtrans.admin' => 'view-midtrans-transactions',
            // New resource keys
            'portal-beranda' => 'view-tagihan-siswa',
            'portal-access' => 'view-own-billing',
            'roles.delete' => 'delete-role',
            'laporan-harian' => 'view-kas-harian',
            'tagihan.export' => 'export-laporan',
        ];
    }

    public function handle(): int
    {
        $clear = $this->option('clear');
        $prune = $this->option('prune');
        $mapping = $this->getMapping();

        // Get permission IDs
        $permissions = DB::table('permissions')->pluck('id', 'name');

        if ($clear) {
            PermissionEndpoint::query()->delete();
            $this->info('Cleared all existing endpoint data.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $warnings = [];

        foreach ($mapping as $resourceKey => $permName) {
            $permId = $permissions[$permName] ?? null;

            if (! $permId) {
                $warnings[] = "Permission '{$permName}' not found for resource_key '{$resourceKey}'";
                $skipped++;

                continue;
            }

            PermissionEndpoint::updateOrCreate(
                ['resource_key' => $resourceKey],
                [
                    'permission_id' => $permId,
                    'description' => "Auto-synced mapping for {$resourceKey}",
                    'is_active' => true,
                ]
            );

            $this->line("  ✓ {$resourceKey} → {$permName}");
            $created++;
        }

        // Optional: prune stale mappings that no longer exist in code
        if ($prune) {
            $existingKeys = array_keys($mapping);
            $deleted = PermissionEndpoint::whereNotIn('resource_key', $existingKeys)->delete();
            if ($deleted) {
                $this->warn("Pruned {$deleted} stale endpoint mappings.");
            } else {
                $this->info('No stale mappings to prune.');
            }
        }

        $this->info("Done. Created/updated: {$created}, skipped: {$skipped}");

        if (! empty($warnings)) {
            $this->warn('Warnings:');
            foreach ($warnings as $w) {
                $this->warn("  - {$w}");
            }
        }

        return self::SUCCESS;
    }
}
