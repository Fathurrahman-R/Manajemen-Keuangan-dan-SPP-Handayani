<?php

namespace App\Console\Commands;

use App\Enum\Permission;
use App\Models\PermissionResource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SyncResourcesCommand extends Command
{
    protected $signature = 'permissions:sync-resources';

    protected $description = 'Seed permission_resources table from Permission enum.';

    /**
     * Map Permission enum case to a resource_key in format: module.feature.action
     *
     * Convention:
     *   view-siswa       → akademik.siswa.view
     *   create-siswa     → akademik.siswa.create
     *   view-tagihan     → keuangan.tagihan.view
     *   create-pengeluaran-request → keuangan.pengeluaran.create
     *   view-own-billing → portal.billing.view
     */
    private function toResourceKey(string $permissionName): string
    {
        $parts = explode('-', $permissionName);
        $action = array_shift($parts); // view, create, update, delete, etc.
        $feature = implode('-', $parts); // the rest

        // Determine module
        $module = $this->inferModule($feature, $action);

        return "{$module}.{$feature}.{$action}";
    }

    private function inferModule(string $feature, string $action): string
    {
        $moduleMap = [
            'siswa' => 'akademik',
            'kelas' => 'akademik',
            'kategori' => 'akademik',
            'tahun-ajaran' => 'akademik',
            'kenaikan-kelas' => 'akademik',
            'tagihan' => 'keuangan',
            'pembayaran' => 'keuangan',
            'pengeluaran' => 'keuangan',
            'jenis-tagihan' => 'keuangan',
            'kas' => 'laporan',
            'laporan' => 'laporan',
            'dashboard' => 'laporan',
            'user' => 'pengaturan',
            'roles' => 'pengaturan',
            'permission' => 'pengaturan',
            'branch' => 'pengaturan',
            'akun-siswa' => 'pengaturan',
            'app-setting' => 'pengaturan',
            'notification' => 'pengaturan',
            'midtrans' => 'keuangan',
            'export' => 'laporan',
            'import' => 'laporan',
            'own-billing' => 'portal',
            'tagihan-siswa' => 'portal',
        ];

        return $moduleMap[$feature] ?? 'general';
    }

    private function toLabel(string $permissionName): string
    {
        $parts = explode('-', $permissionName);

        // Map first segment (action) to Indonesian
        $actionMap = [
            'view' => 'Lihat',
            'create' => 'Buat',
            'read' => 'Baca',
            'update' => 'Ubah',
            'delete' => 'Hapus',
            'print' => 'Cetak',
            'process' => 'Proses',
            'undo' => 'Batalkan',
            'generate' => 'Generate',
            'import' => 'Import',
            'export' => 'Export',
            'attach' => 'Lampirkan',
            'detach' => 'Lepas',
            'approve' => 'Setujui',
            'disburse' => 'Cairkan',
            'pay' => 'Bayar',
            'sync' => 'Sinkron',
            'assign' => 'Assign',
        ];

        $action = array_shift($parts);
        $actionLabel = $actionMap[$action] ?? ucfirst($action);
        $featureLabel = implode(' ', array_map('ucfirst', $parts));

        return "{$actionLabel} {$featureLabel}";
    }

    public function handle(): int
    {
        $created = 0;
        $skipped = 0;

        foreach (Permission::cases() as $perm) {
            $name = $perm->value;
            $resourceKey = $this->toResourceKey($name);

            $existing = PermissionResource::where('resource_key', $resourceKey)->first();
            if ($existing) {
                $skipped++;
                continue;
            }

            $spatiePerm = \Spatie\Permission\Models\Permission::where('name', $name)->first();

            PermissionResource::create([
                'permission_id' => $spatiePerm?->id,
                'resource_key' => $resourceKey,
                'label' => $this->toLabel($name),
                'description' => "Auto-synced from permission: {$name}",
                'is_active' => true,
            ]);
            $created++;
            $this->line("  + {$resourceKey} → {$name}");
        }

        $this->info("Resource registry selesai.");
        $this->info("  + {$created} baru");
        $this->info("  = {$skipped} sudah ada (skip)");

        return self::SUCCESS;
    }
}
