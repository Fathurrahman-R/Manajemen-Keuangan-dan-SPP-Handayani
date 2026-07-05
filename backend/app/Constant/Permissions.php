<?php

namespace App\Constant;

use App\Enum\Permission;

class Permissions
{
    public const USERS_PERMISSIONS = [
        'view' => Permission::VIEW_USER,
        'create' => Permission::CREATE_USER,
        'read' => Permission::READ_USER,
        'update' => Permission::UPDATE_USER,
        'delete' => Permission::DELETE_USER,
    ];
    public const SISWA_PERMISSIONS = [
        'view' => Permission::VIEW_SISWA,
        'create' => Permission::CREATE_SISWA,
        'read' => Permission::READ_SISWA,
        'update' => Permission::UPDATE_SISWA,
        'delete' => Permission::DELETE_SISWA,
    ];
    public const KELAS_PERMISSIONS = [
        'view' => Permission::VIEW_KELAS,
        'create' => Permission::CREATE_KELAS,
        'read' => Permission::READ_KELAS,
        'update' => Permission::UPDATE_KELAS,
        'delete' => Permission::DELETE_KELAS,
    ];
    public const KATEGORI_PERMISSIONS = [
        'view' => Permission::VIEW_KATEGORI,
        'create' => Permission::CREATE_KATEGORI,
        'read' => Permission::READ_KATEGORI,
        'update' => Permission::UPDATE_KATEGORI,
        'delete' => Permission::DELETE_KATEGORI,
    ];
    public const PENGELUARAN_PERMISSIONS = [
        'view' => Permission::VIEW_PENGELUARAN,
        'create' => Permission::CREATE_PENGELUARAN,
        'read' => Permission::READ_PENGELUARAN,
        'update' => Permission::UPDATE_PENGELUARAN,
        'delete' => Permission::DELETE_PENGELUARAN,
    ];
    public const PEMBAYARAN_PERMISSIONS = [
        'view' => Permission::VIEW_PEMBAYARAN,
        'create' => Permission::CREATE_PEMBAYARAN,
        'delete' => Permission::DELETE_PEMBAYARAN,
        'print' => Permission::PRINT_KWITANSI
    ];
    public const JENIS_TAGIHAN_PERMISSIONS = [
        'view' => Permission::VIEW_JENIS_TAGIHAN,
        'create' => Permission::CREATE_JENIS_TAGIHAN,
        'read' => Permission::READ_JENIS_TAGIHAN,
        'update' => Permission::UPDATE_JENIS_TAGIHAN,
        'delete' => Permission::DELETE_JENIS_TAGIHAN,
    ];
    public const TAGIHAN_PERMISSIONS = [
        'view' => Permission::VIEW_TAGIHAN,
        'create' => Permission::CREATE_TAGIHAN,
        'read' => Permission::READ_TAGIHAN,
        'update' => Permission::UPDATE_TAGIHAN,
        'delete' => Permission::DELETE_TAGIHAN,
    ];
    public const LAPORAN_PERMISSIONS = [
        'view' => [
            Permission::VIEW_KAS_HARIAN,
            Permission::VIEW_REKAP_BULANAN,
        ],
        'export' => Permission::EXPORT_LAPORAN,
    ];

    public const TAHUN_AJARAN_PERMISSIONS = [
        'view' => Permission::VIEW_TAHUN_AJARAN,
        'create' => Permission::CREATE_TAHUN_AJARAN,
        'update' => Permission::UPDATE_TAHUN_AJARAN,
        'delete' => Permission::DELETE_TAHUN_AJARAN,
    ];

    public const KENAIKAN_KELAS_PERMISSIONS = [
        'view' => Permission::VIEW_KENAIKAN_KELAS,
        'process' => Permission::PROCESS_KENAIKAN_KELAS,
        'undo' => Permission::UNDO_KENAIKAN_KELAS,
    ];

    public const AKUN_SISWA_PERMISSIONS = [
        'view' => Permission::VIEW_AKUN_SISWA,
        'generate' => Permission::GENERATE_AKUN_SISWA,
    ];

    public const IMPORT_EXPORT_PERMISSIONS = [
        'import' => Permission::IMPORT_DATA,
        'export' => Permission::EXPORT_DATA,
    ];

    public const DASHBOARD_PERMISSIONS = [
        'view' => Permission::VIEW_DASHBOARD,
        'view-own' => Permission::VIEW_OWN_BILLING,
    ];

    public const APPROVAL_WORKFLOW_PERMISSIONS = [
        'create' => Permission::CREATE_PENGELUARAN_REQUEST,
        'approve' => Permission::APPROVE_PENGELUARAN,
        'disburse' => Permission::DISBURSE_PENGELUARAN,
    ];

    public const BRANCH_PERMISSIONS = [
        'view' => Permission::VIEW_BRANCH,
        'create' => Permission::CREATE_BRANCH,
        'read' => Permission::READ_BRANCH,
        'update' => Permission::UPDATE_BRANCH,
        'delete' => Permission::DELETE_BRANCH,
    ];

    public const MIDTRANS_PERMISSIONS = [
        'pay-online' => Permission::PAY_TAGIHAN_ONLINE,
        'view-transactions' => Permission::VIEW_MIDTRANS_TRX,
        'sync-transactions' => Permission::SYNC_MIDTRANS_TRX,
        'view-config' => Permission::VIEW_MIDTRANS_CONFIG,
        'update-config' => Permission::UPDATE_MIDTRANS_CONFIG,
    ];

    public const SETTING_PERMISSIONS = [
        'view-app' => Permission::VIEW_APP_SETTING,
        'update-app' => Permission::UPDATE_APP_SETTING,
        'view-notification' => Permission::VIEW_NOTIFICATION_SETTING,
        'update-notification' => Permission::UPDATE_NOTIFICATION_SETTING,
        'view-notification-logs' => Permission::VIEW_NOTIFICATION_LOGS,
    ];
}
