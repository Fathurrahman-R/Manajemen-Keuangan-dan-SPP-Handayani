<?php

namespace App\Constant;

use App\Enum\Permission;

class PermissionBinding
{
    public const ADMIN_PERMISSIONS = [
        Permissions::SISWA_PERMISSIONS,
        Permissions::KELAS_PERMISSIONS,
        Permissions::KATEGORI_PERMISSIONS,
        Permissions::PEMBAYARAN_PERMISSIONS,
        Permissions::PENGELUARAN_PERMISSIONS,
        Permissions::JENIS_TAGIHAN_PERMISSIONS,
        Permissions::TAGIHAN_PERMISSIONS,
        Permissions::TAHUN_AJARAN_PERMISSIONS,
        Permissions::KENAIKAN_KELAS_PERMISSIONS,
        Permissions::AKUN_SISWA_PERMISSIONS,
        Permissions::IMPORT_EXPORT_PERMISSIONS,
        Permissions::DASHBOARD_PERMISSIONS,
        Permissions::APPROVAL_WORKFLOW_PERMISSIONS,
        Permissions::BRANCH_PERMISSIONS,
        Permissions::MIDTRANS_PERMISSIONS,
        Permissions::SETTING_PERMISSIONS,
        // RBAC management permissions
        [
            Permission::VIEW_PERMISSION,
            Permission::CREATE_PERMISSION,
            Permission::EDIT_PERMISSION,
            Permission::DELETE_PERMISSION,
            Permission::ASSIGN_PERMISSION,
        ],
    ];
}
