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
        Permissions::PENGELUARAN_PERMISSIONS
    ];
}
