<?php

namespace App\Constant;

class PermissionBinding
{
    public const DEV_PERMISSIONS = [
        Permissions::DASHBOARD_PERMISSIONS['view'],

        Permissions::RBAC_MANAGEMENT_PERMISSIONS,
        Permissions::PERMISSION_PERMISSIONS,
        Permissions::ROLE_PERMISSIONS,
        Permissions::ENDPOINT_MAPPING_PERMISSIONS,
        Permissions::RESOURCE_REGISTRY_PERMISSIONS,
        Permissions::MIDTRANS_PERMISSIONS['view-config'],
        Permissions::MIDTRANS_PERMISSIONS['update-config'],
    ];

    public const KEPALA_YAYASAN_PERMISSIONS = [
        Permissions::DASHBOARD_PERMISSIONS['view'],

        Permissions::USERS_PERMISSIONS,

        Permissions::BRANCH_PERMISSIONS,
        Permissions::PENGELUARAN_PERMISSIONS['view'],
        Permissions::PENGELUARAN_PERMISSIONS['approve'],
        Permissions::LAPORAN_PERMISSIONS,

        Permissions::SETTING_PERMISSIONS,
        Permissions::NOTIFICATION_PERMISSIONS['view'],
        Permissions::NOTIFICATION_PERMISSIONS['update'],
        Permissions::AUTO_APPROVE_PERMISSIONS,
    ];

    public const ADMIN_PERMISSIONS = [
        Permissions::DASHBOARD_PERMISSIONS['view'],

        Permissions::AKUN_SISWA_PERMISSIONS,

        Permissions::SISWA_PERMISSIONS,
        Permissions::KELAS_PERMISSIONS,
        Permissions::KATEGORI_PERMISSIONS,
        Permissions::TAHUN_AJARAN_PERMISSIONS,
        Permissions::KENAIKAN_KELAS_PERMISSIONS,

        Permissions::JENIS_TAGIHAN_PERMISSIONS,
        Permissions::TAGIHAN_PERMISSIONS,
        Permissions::PEMBAYARAN_PERMISSIONS,
        Permissions::LAPORAN_PERMISSIONS,
        Permissions::PENGELUARAN_PERMISSIONS['view'],
        Permissions::PENGELUARAN_PERMISSIONS['create'],
        Permissions::PENGELUARAN_PERMISSIONS['update'],
        Permissions::PENGELUARAN_PERMISSIONS['delete'],
        Permissions::PENGELUARAN_PERMISSIONS['disburse'],
        Permissions::MIDTRANS_PERMISSIONS['view'],
        Permissions::MIDTRANS_PERMISSIONS['sync'],

        Permissions::IMPORT_EXPORT_PERMISSIONS,
        Permissions::NOTIFICATION_PERMISSIONS['view-logs'],
        Permissions::NOTIFICATION_PERMISSIONS['retry'],
    ];

    public const SISWA_PERMISSIONS = [
        Permissions::DASHBOARD_PERMISSIONS['view-own'],

        Permissions::MIDTRANS_PERMISSIONS['pay'],
        Permissions::PEMBAYARAN_PERMISSIONS['print'],
    ];
}
