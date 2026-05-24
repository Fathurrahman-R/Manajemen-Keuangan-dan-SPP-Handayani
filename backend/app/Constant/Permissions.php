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
        'delete' => Permission::DELETE_PEMBAYARAN,
        'print' => Permission::PRINT_KWITANSI
    ];
    public const LAPORAN_PERMISSIONS = [
        'view' => [
            Permission::VIEW_KAS_HARIAN,
            Permission::VIEW_REKAP_BULANAN,
        ],
        'export' => Permission::EXPORT_LAPORAN,
    ];
}
