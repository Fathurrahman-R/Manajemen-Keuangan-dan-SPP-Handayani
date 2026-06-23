<?php

namespace App\Helpers;

class PermissionHelper
{
    /**
     * Permission mappings for each navigation group.
     * A group is visible if the user has at least one of these permissions.
     */
    protected static array $groupPermissions = [
        'akademik' => [
            'view-siswa',
            'view-kategori',
            'view-kelas',
            'manage-tahun-ajaran',
            'manage-kenaikan-kelas',
        ],
        'keuangan' => [
            'view-jenis-tagihan',
            'view-tagihan',
            'view-pembayaran',
            'view-pengeluaran',
            'create-pengeluaran-request',
            'approve-pengeluaran',
            'disburse-pengeluaran',
            'view-midtrans-transactions',
        ],
        'laporan' => [
            'view-dashboard',
            'view-kas-harian',
            'view-rekap-bulanan',
        ],
        'pengaturan' => [
            'view-roles',
            'view-user',
            'manage-akun-siswa',
            'view-branch',
        ],
    ];

    /**
     * Permission mappings for jenjang visibility.
     * Maps each jenjang to the permission required to view it.
     */
    protected static array $jenjangPermissions = [
        'KB' => 'view-jenjang-kb',
        'TK' => 'view-jenjang-tk',
        'MI' => 'view-jenjang-mi',
    ];

    /**
     * Check if the user has any permission in a navigation group.
     * Used to determine whether an entire group should be visible.
     */
    public static function hasAnyInGroup(string $group): bool
    {
        $permissions = self::getUserPermissions();
        $groupPerms = self::$groupPermissions[$group] ?? [];

        foreach ($groupPerms as $perm) {
            if (in_array($perm, $permissions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user can view a specific jenjang.
     * If no jenjang-specific permissions are defined in the user's permissions,
     * defaults to true (all jenjang visible) for backwards compatibility.
     */
    public static function canViewJenjang(string $jenjang): bool
    {
        $permissions = self::getUserPermissions();

        // If no jenjang-specific permissions exist at all, allow all (backwards compat)
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

    /**
     * Check if the user has a specific permission.
     */
    public static function has(string $permission): bool
    {
        return in_array($permission, self::getUserPermissions());
    }

    /**
     * Get the current user's permissions from session.
     */
    protected static function getUserPermissions(): array
    {
        return session()->get('data.permissions', []);
    }
}
