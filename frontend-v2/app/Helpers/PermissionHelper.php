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

            if ($data === null) {
                \Illuminate\Support\Facades\Log::error('RBAC API failed for /rbac/user-resources');
                self::$userResources = [];
            } else {
                self::$userResources = $data;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('RBAC API threw exception: '.$e->getMessage());
            self::$userResources = [];
        }

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
            self::$userGroups = $data ?? [];
        } catch (\Exception $e) {
            self::$userGroups = [];
        }

        return self::$userGroups;
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
