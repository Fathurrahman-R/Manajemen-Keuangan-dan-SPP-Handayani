<?php

namespace App\Enum;

enum Permission: string
{
    // `user`s permission
    case VIEW_USER = 'view-user';
    case CREATE_USER = 'create-user';
    case READ_USER = 'read-user';
    case UPDATE_USER = 'update-user';
    case DELETE_USER = 'delete-user';
    case TOGGLE_USER = 'toggle-user';

    // siswa permissions
    case VIEW_SISWA = 'view-siswa';
    case CREATE_SISWA = 'create-siswa';
    case READ_SISWA = 'read-siswa';
    case UPDATE_SISWA = 'update-siswa';
    case DELETE_SISWA = 'delete-siswa';

    // kelas permissions
    case VIEW_KELAS = 'view-kelas';
    case CREATE_KELAS = 'create-kelas';
    case READ_KELAS = 'read-kelas';
    case UPDATE_KELAS = 'update-kelas';
    case DELETE_KELAS = 'delete-kelas';

    // kategori permissions
    case VIEW_KATEGORI = 'view-kategori';
    case CREATE_KATEGORI = 'create-kategori';
    case READ_KATEGORI = 'read-kategori';
    case UPDATE_KATEGORI = 'update-kategori';
    case DELETE_KATEGORI = 'delete-kategori';

    // pembayaran permissions
    case VIEW_PEMBAYARAN = 'view-pembayaran';
    case CREATE_PEMBAYARAN = 'create-pembayaran';
    case DELETE_PEMBAYARAN = 'delete-pembayaran';
    case PRINT_KWITANSI = 'print-kwitansi';

    // jenis tagihan permissions
    case VIEW_JENIS_TAGIHAN = 'view-jenis-tagihan';
    case CREATE_JENIS_TAGIHAN = 'create-jenis-tagihan';
    //    case READ_JENIS_TAGIHAN = 'read-jenis-tagihan';
    case UPDATE_JENIS_TAGIHAN = 'update-jenis-tagihan';
    case DELETE_JENIS_TAGIHAN = 'delete-jenis-tagihan';

    // tagihan permissions
    case VIEW_TAGIHAN = 'view-tagihan';
    case CREATE_TAGIHAN = 'create-tagihan';
    //    case READ_TAGIHAN = 'read-tagihan';
    case UPDATE_TAGIHAN = 'update-tagihan';
    case DELETE_TAGIHAN = 'delete-tagihan';

    // laporan permission
    case VIEW_KAS_HARIAN = 'view-kas-harian';
    case DETAIL_KAS_HARIAN = 'detail-kas-harian';
    case VIEW_REKAP_BULANAN = 'view-rekap-bulanan';
    case DETAIL_REKAP_BULANAN = 'detail-rekap-bulanan';
    case EXPORT_LAPORAN = 'export-laporan';

    // tahun ajaran permission
    case VIEW_TAHUN_AJARAN = 'view-tahun-ajaran';
    case CREATE_TAHUN_AJARAN = 'create-tahun-ajaran';
    case UPDATE_TAHUN_AJARAN = 'update-tahun-ajaran';
    case DELETE_TAHUN_AJARAN = 'delete-tahun-ajaran';
    case TOGGLE_TAHUN_AJARAN = 'toggle-tahun-ajaran';

    // kenaikan kelas permission
    case VIEW_KENAIKAN_KELAS = 'view-kenaikan-kelas';
    case PROCESS_KENAIKAN_KELAS = 'process-kenaikan-kelas';
    case UNDO_KENAIKAN_KELAS = 'undo-kenaikan-kelas';
    case VIEW_DETAIL_KENAIKAN = 'view-detail-kenaikan';

    // akun siswa permissions
    case VIEW_AKUN_SISWA = 'view-akun-siswa';
    case GENERATE_AKUN_SISWA = 'generate-akun-siswa';
    case RESET_AKUN_SISWA_PASSWORD = 'reset-akun-siswa-password';
    case TOGGLE_AKUN_SISWA = 'toggle-akun-siswa';
    case VIEW_AKUN_SISWA_CREDENTIALS = 'view-akun-siswa-credentials';
    case PRINT_AKUN_SISWA = 'print-akun-siswa';

    // import export permissions
    case IMPORT_DATA = 'import-data';
    case EXPORT_DATA = 'export-data';

    // dashboard permissions
    case VIEW_DASHBOARD = 'view-dashboard';
    case VIEW_OWN_BILLING = 'view-own-billing';

    // pengeluaran permissions
    case VIEW_PENGELUARAN = 'view-pengeluaran';
    case CREATE_PENGELUARAN = 'create-pengeluaran';
    case UPDATE_PENGELUARAN = 'update-pengeluaran';
    case DELETE_PENGELUARAN = 'delete-pengeluaran';
    case APPROVE_PENGELUARAN = 'approve-pengeluaran';
    case DISBURSE_PENGELUARAN = 'disburse-pengeluaran';

    // branch permissions
    case VIEW_BRANCH = 'view-branch';
    case CREATE_BRANCH = 'create-branch';
    case READ_BRANCH = 'read-branch';
    case UPDATE_BRANCH = 'update-branch';
    case DELETE_BRANCH = 'delete-branch';

    // midtrans permissions
    case PAY_TAGIHAN_ONLINE = 'pay-tagihan-online';
    case VIEW_MIDTRANS_TRX = 'view-midtrans-transactions';
    case SYNC_MIDTRANS_TRX = 'sync-midtrans-transactions';
    case VIEW_MIDTRANS_CONFIG = 'view-midtrans-config';
    case UPDATE_MIDTRANS_CONFIG = 'update-midtrans-config';

    // setting permissions
    case VIEW_APP_SETTING = 'view-app-setting';
    case UPDATE_APP_SETTING = 'update-app-setting';

    // Auto Approve setting
    case VIEW_AUTO_APPROVE_SETTING = 'view-auto-approve-setting';
    case UPDATE_AUTO_APPROVE_SETTING = 'update-auto-approve-setting';

    // Notification
    case VIEW_NOTIFICATION_SETTING = 'view-notification-setting';
    case UPDATE_NOTIFICATION_SETTING = 'update-notification-setting';
    case VIEW_NOTIFICATION_LOGS = 'view-notification-logs';
    //    case SEND_NOTIFICATION = 'send-notification';
    case RETRY_NOTIFICATION = 'retry-notification';

    // rbac management permissions
    case MANAGE_RBAC = 'manage-rbac';
    case TOGGLE_ACTIVE = 'toggle-active';
    case BIND_PERMISSION = 'bind-permission';

    case VIEW_ENDPOINT_MAPPING = 'view-endpoint-mapping';
    case CREATE_ENDPOINT_MAPPING = 'create-endpoint-mapping';
    case UPDATE_ENDPOINT_MAPPING = 'update-endpoint-mapping';
    case DELETE_ENDPOINT_MAPPING = 'delete-endpoint-mapping';

    case VIEW_RESOURCE_REGISTRY = 'view-resource-registry';
    case CREATE_RESOURCE_REGISTRY = 'create-resource-registry';
    case UPDATE_RESOURCE_REGISTRY = 'update-resource-registry';
    case DELETE_RESOURCE_REGISTRY = 'delete-resource-registry';

    case VIEW_PERMISSIONS = 'view-permissions';
    case CREATE_PERMISSION = 'create-permission';
    case UPDATE_PERMISSION = 'update-permission';
    case DELETE_PERMISSION = 'delete-permission';
    case ATTACH_PERMISSION = 'attach-permission';

    // roles and permission
    case VIEW_ROLES = 'view-roles';
    case CREATE_ROLE = 'create-role';
    case UPDATE_ROLE = 'update-role';
    case DELETE_ROLE = 'delete-role';
    case ATTACH_ROLE = 'attach-role';
}
