<?php

namespace App\Enum;

enum Permission: string
{
    //users permission
    case VIEW_USER = 'view-user';
    case CREATE_USER = 'create-user';
    case READ_USER = 'read-user';
    case UPDATE_USER = 'update-user';
    case DELETE_USER = 'delete-user';

    //siswa permissions
    case VIEW_SISWA = 'view-siswa';
    case CREATE_SISWA = 'create-siswa';
    case READ_SISWA = 'read-siswa';
    case UPDATE_SISWA = 'update-siswa';
    case DELETE_SISWA = 'delete-siswa';

    //kelas permissions
    case VIEW_KELAS = 'view-kelas';
    case CREATE_KELAS = 'create-kelas';
    case READ_KELAS = 'read-kelas';
    case UPDATE_KELAS = 'update-kelas';
    case DELETE_KELAS = 'delete-kelas';

    //kategori permissions
    case VIEW_KATEGORI = 'view-kategori';
    case CREATE_KATEGORI = 'create-kategori';
    case READ_KATEGORI = 'read-kategori';
    case UPDATE_KATEGORI = 'update-kategori';
    case DELETE_KATEGORI = 'delete-kategori';

    //pengeluaran permissions
    case VIEW_PENGELUARAN = 'view-pengeluaran';
    case CREATE_PENGELUARAN = 'create-pengeluaran';
    case READ_PENGELUARAN = 'read-pengeluaran';
    case UPDATE_PENGELUARAN = 'update-pengeluaran';
    case DELETE_PENGELUARAN = 'delete-pengeluaran';

    //pembayaran permissions
    case VIEW_PEMBAYARAN = 'view-pembayaran';
    case DELETE_PEMBAYARAN = 'delete-pembayaran';
    case PRINT_KWITANSI = 'print-kwitansi';

    //jenis tagihan permissions
    case VIEW_JENIS_TAGIHAN = 'view-jenis-tagihan';
    case CREATE_JENIS_TAGIHAN = 'create-jenis-tagihan';
    case READ_JENIS_TAGIHAN = 'read-jenis-tagihan';
    case UPDATE_JENIS_TAGIHAN = 'update-jenis-tagihan';
    case DELETE_JENIS_TAGIHAN = 'delete-jenis-tagihan';

    //tagihan permissions
    case VIEW_TAGIHAN = 'view-tagihan';
    case CREATE_TAGIHAN = 'create-tagihan';
    case READ_TAGIHAN = 'read-tagihan';
    case UPDATE_TAGIHAN = 'update-tagihan';
    case DELETE_TAGIHAN = 'delete-tagihan';

    //laporan permission
    case VIEW_KAS_HARIAN = 'view-kas-harian';
    case VIEW_REKAP_BULANAN = 'view-rekap-bulanan';
    case EXPORT_LAPORAN = 'export-laporan';

    //roles and permission
    case VIEW_ROLES = 'view-roles';
    case CREATE_ROLE = 'create-role';
    case UPDATE_ROLE = 'update-role';
    case DELETE_ROLE = 'delete-role';
    case ATTACH_ROLE = 'attach-role';
    case DETACH_ROLE = 'detach-role';
    case VIEW_PERMISSIONS = 'view-permissions';
    case ATTACH_PERMISSIONS = 'attach-permissions';
    case DETACH_PERMISSIONS = 'detach-permissions';

    //tahun ajaran permission
    case MANAGE_TAHUN_AJARAN = 'manage-tahun-ajaran';

    //kenaikan kelas permission
    case MANAGE_KENAIKAN_KELAS = 'manage-kenaikan-kelas';

    //akun siswa permissions
    case VIEW_TAGIHAN_SISWA = 'view-tagihan-siswa';
    case MANAGE_AKUN_SISWA = 'manage-akun-siswa';
}
