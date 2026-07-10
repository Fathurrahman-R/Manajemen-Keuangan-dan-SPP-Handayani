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
        'dashboard'   => 'heroicon-o-home',
        'akademik'    => 'heroicon-o-academic-cap',
        'keuangan'    => 'heroicon-o-banknotes',
        'laporan'     => 'heroicon-o-chart-bar',
        'pengaturan'  => 'heroicon-o-cog-6-tooth',
    ];

    protected static array $groupLabels = [
        'dashboard'   => 'Dashboard',
        'akademik'    => 'Akademik',
        'keuangan'    => 'Keuangan',
        'laporan'     => 'Laporan',
        'pengaturan'  => 'Pengaturan',
    ];

    /**
     * Check if the current user has the given permission.
     * Superadmin bypass: always returns true.
     */
    public static function has(string $permission): bool
    {
        if (self::isSuperadmin()) {
            return true;
        }

        return in_array($permission, self::getUserPermissions());
    }

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
            if ($g['group'] === $group && !empty($g['resources'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has access to a registered resource (resource_key).
     * Superadmin bypass: always returns true.
     * Falls back to permission check if resources not yet loaded.
     */
    public static function hasResource(string $resourceKey): bool
    {
        if (self::isSuperadmin()) {
            return true;
        }

        $resources = self::getUserResources();

        // Fallback: if resource registry is empty, check via permission name
        if (empty($resources)) {
            return self::has($resourceKey);
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

        if (self::isSuperadmin()) {
            self::$userResources = [];
            return self::$userResources;
        }

        try {
            $r = ApiService::client()->get('/rbac/user-resources');
            if ($r->successful()) {
                self::$userResources = $r->json()['data'] ?? [];
            } else {
                self::$userResources = [];
            }
        } catch (\Exception $e) {
            self::$userResources = [];
        }

        return self::$userResources;
    }

    /**
     * Get grouped resources accessible to the current user.
     * Data di-cache per request.
     */
    public static function getUserGroups(): array
    {
        if (self::$userGroups !== null) {
            return self::$userGroups;
        }

        try {
            $r = ApiService::client()->get('/rbac/user-groups');
            if ($r->successful()) {
                self::$userGroups = $r->json()['data'] ?? [];
            } else {
                self::$userGroups = [];
            }
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

    /**
     * Get the current user's permissions from session.
     */
    protected static function getUserPermissions(): array
    {
        return session()->get('data.permissions', []);
    }

    /**
     * Permission mappings for jenjang visibility.
     */
    protected static array $jenjangPermissions = [
        'KB' => 'view-jenjang-kb',
        'TK' => 'view-jenjang-tk',
        'MI' => 'view-jenjang-mi',
    ];

    /**
     * Check if the user can view a specific jenjang.
     */
    public static function canViewJenjang(string $jenjang): bool
    {
        if (self::isSuperadmin()) {
            return true;
        }

        $permissions = self::getUserPermissions();

        $hasAnyJenjangPerm = false;
        foreach (self::$jenjangPermissions as $perm) {
            if (in_array($perm, $permissions)) {
                $hasAnyJenjangPerm = true;
                break;
            }
        }

        if (!$hasAnyJenjangPerm) {
            return true;
        }

        $requiredPerm = self::$jenjangPermissions[$jenjang] ?? null;

        return $requiredPerm && in_array($requiredPerm, $permissions);
    }

    /**
     * Get the list of jenjang values visible to the current user.
     */
    public static function visibleJenjang(): array
    {
        return array_values(array_filter(
            array_keys(self::$jenjangPermissions),
            fn(string $jenjang) => self::canViewJenjang($jenjang)
        ));
    }
}
