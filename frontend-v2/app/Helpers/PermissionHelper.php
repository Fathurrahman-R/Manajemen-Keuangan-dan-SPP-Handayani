<?php

namespace App\Helpers;

use App\Services\ApiService;

class PermissionHelper
{
    // ── Cached data ──
    protected static ?array $userResources = null;

    protected static ?array $userGroups = null;

    // Icon mapping untuk setiap grup navigasi
    protected static array $groupIcons = [
        'dashboard' => 'heroicon-o-home',
        'akademik' => 'heroicon-o-academic-cap',
        'keuangan' => 'heroicon-o-banknotes',
        'laporan' => 'heroicon-o-chart-bar',
        'pengaturan' => 'heroicon-o-cog-6-tooth',
    ];

    protected static array $groupLabels = [
        'dashboard' => 'Dashboard',
        'akademik' => 'Akademik',
        'keuangan' => 'Keuangan',
        'laporan' => 'Laporan',
        'pengaturan' => 'Pengaturan',
    ];

    /**
     * Check if the user has any permission in a navigation group.
     * Groups are determined dynamically from the Resource Registry (via API).
     * Superadmin bypass: always returns true.
     */
    public static function hasAnyInGroup(string $group): bool
    {
        if (self::isSuperadmin()) {
            return true;
        }

        $groups = self::getUserGroups();

        foreach ($groups as $g) {
            if ($g['group'] === $group && ! empty($g['resources'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has access to a registered resource (resource_key).
     * Superadmin bypass: always returns true.
     *
     * Jika resource registry belum terisi (misal API error), fallback
     * ke pengecekan permission name langsung dari session.
     */
    public static function hasResource(string $resourceKey): bool
    {
        if (self::isSuperadmin()) {
            return true;
        }

        $resources = self::getUserResources();

        // Fallback: jika resource registry kosong, cek langsung dari session
        if (empty($resources)) {
            return in_array($resourceKey, session()->get('data.permissions', []));
        }

        return in_array($resourceKey, $resources);
    }

    /**
     * Get list of resource keys accessible to the current user.
     */
    public static function getUserResources(): array
    {
        if (self::$userResources !== null) {
            return self::$userResources;
        }

        try {
            $data = ApiService::cachedGet('/rbac/user-resources', [], (int) config('handayani.cache.rbac_ttl', 60));
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            self::abortBackendUnreachable('/rbac/user-resources', $e->getMessage());
        }

        // Daftar kosong berarti "user memang tidak punya resource"; null berarti
        // permintaannya gagal. Dulu keduanya sama-sama jadi array kosong, jadi
        // backend yang mati tampil sebagai "403 Forbidden" — seolah-olah user
        // yang tidak berhak, bukan servernya yang tidak bisa dihubungi.
        if ($data === null) {
            self::abortBackendUnreachable('/rbac/user-resources', 'endpoint membalas status galat');
        }

        self::$userResources = $data;

        return self::$userResources;
    }

    /**
     * Get grouped resources accessible to the current user.
     * Data di-cache per request (statis) + Redis (lintas request, lihat ApiService::cachedGet()).
     */
    public static function getUserGroups(): array
    {
        if (self::$userGroups !== null) {
            return self::$userGroups;
        }

        try {
            $data = ApiService::cachedGet('/rbac/user-groups', [], (int) config('handayani.cache.rbac_ttl', 60));
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            self::abortBackendUnreachable('/rbac/user-groups', $e->getMessage());
        }

        if ($data === null) {
            self::abortBackendUnreachable('/rbac/user-groups', 'endpoint membalas status galat');
        }

        self::$userGroups = $data;

        return self::$userGroups;
    }

    /**
     * Hentikan request dengan 503 saat API RBAC tidak bisa dijangkau.
     *
     * Tanpa data ini tidak ada satu pun keputusan otorisasi yang bisa diambil,
     * dan menebak "tidak punya akses" menghasilkan diagnosis yang salah bagi
     * siapa pun yang menemuinya.
     */
    protected static function abortBackendUnreachable(string $endpoint, string $alasan): never
    {
        // Backend yang menolak token (401) sudah membersihkan sesi lewat listener
        // di AppServiceProvider. Hilangnya token adalah penanda "sesi berakhir",
        // bukan "server mati" — arahkan ke login, jangan tampilkan 503.
        if (! session()->has('data.token')) {
            // Bukan redirect()->guest(): Livewire menukar binding `redirect`
            // dengan Redirector miliknya sendiri yang bukan objek Response,
            // dan middleware CSRF menolaknya dengan 500.
            session()->put('url.intended', url()->current());

            abort(new \Illuminate\Http\RedirectResponse('/login'));
        }

        \Illuminate\Support\Facades\Log::error('RBAC API tidak dapat dihubungi', [
            'endpoint' => $endpoint,
            'alasan' => $alasan,
        ]);

        abort(503, 'Server aplikasi sedang tidak dapat dihubungi. Coba beberapa saat lagi atau hubungi administrator.');
    }

    /**
     * Get icon for a navigation group.
     */
    public static function getGroupIcon(string $group): string
    {
        return self::$groupIcons[$group] ?? 'heroicon-o-folder';
    }

    /**
     * Get label for a navigation group.
     */
    public static function getGroupLabel(string $group): string
    {
        return self::$groupLabels[$group] ?? ucfirst($group);
    }

    /**
     * Check if current user is superadmin.
     */
    protected static function isSuperadmin(): bool
    {
        return in_array('superadmin', session()->get('data.roles', []));
    }
}
