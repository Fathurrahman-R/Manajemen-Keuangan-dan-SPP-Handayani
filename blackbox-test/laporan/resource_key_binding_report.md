# Laporan Mapping Resource Key dan Permission Binding

Berikut adalah laporan lengkap hasil penyelarasan antara **Frontend (UI & Navigation)** dan **Backend (API Endpoints)** yang menggunakan *Resource Keys* terpusat melalui `PermissionResourceSeeder` dan `PermissionEndpointSeeder`.

## Daftar Resource Key & Permission Binding

| Resource Key (UI/API) | Permission Binding (Spatie) | Penggunaan di Frontend (File / Aksi) | Mapping ke API Endpoint / Fitur |
| :--- | :--- | :--- | :--- |
| **`dashboard`** | `view-dashboard` | `DashboardPage.php` | Akses Dashboard Admin |
| **`siswa.view`** | `view-siswa` | `ManajemenDataSiswa.php`, `DetailSiswa.php`, Aksi `view` (Siswa/Wali) | Melihat daftar siswa & detail |
| **`siswa.create`** | `create-siswa` | Aksi `add_detail`, `add` (Siswa/Wali) | Menambah data siswa |
| **`siswa.read`** | `read-siswa` | (Internal/Detail View fallback) | Detail data siswa via API |
| **`siswa.update`** | `update-siswa` | Aksi `update_detail`, `update` | Mengubah data siswa |
| **`siswa.delete`** | `delete-siswa` | Aksi `delete`, `bulkDelete` | Menghapus data siswa |
| **`ayah.view`** | `view-siswa` | *UI Variasi Detail Siswa* | Akses data ayah |
| **`ibu.view`** | `view-siswa` | *UI Variasi Detail Siswa* | Akses data ibu |
| **`wali.view`** | `view-siswa` | `DetailWali.php` | Akses data wali |
| **`wali.create`** | `create-siswa` | Aksi `add` (Wali) | Menambah data wali |
| **`wali.update`** | `update-siswa` | Aksi `update` (Wali) | Mengubah data wali |
| **`wali.delete`** | `delete-siswa` | Aksi `delete` (Wali) | Menghapus data wali |
| **`kelas.view`** | `view-kelas` | `ManajemenKelasSiswa.php` | Melihat daftar kelas |
| **`kelas.create`** | `create-kelas` | Aksi create Kelas | Menambah data kelas |
| **`kelas.read`** | `read-kelas` | Detail Kelas | Detail data kelas |
| **`kelas.update`** | `update-kelas` | Aksi update Kelas | Mengubah data kelas |
| **`kelas.delete`** | `delete-kelas` | Aksi delete Kelas | Menghapus data kelas |
| **`kategori.view`** | `view-kategori` | `ManajemenKategoriSiswa.php` | Melihat daftar kategori |
| **`kategori.create`** | `create-kategori` | Aksi create Kategori | Menambah data kategori |
| **`kategori.read`** | `read-kategori` | Detail Kategori | Detail data kategori |
| **`kategori.update`** | `update-kategori` | Aksi update Kategori | Mengubah data kategori |
| **`kategori.delete`** | `delete-kategori` | Aksi delete Kategori | Menghapus data kategori |
| **`tahun-ajaran.view`** | `view-tahun-ajaran` | `ManajemenTahunAjaran.php` | Melihat tahun ajaran |
| **`tahun-ajaran.create`** | `create-tahun-ajaran` | Aksi create Tahun Ajaran | Menambah tahun ajaran |
| **`tahun-ajaran.update`** | `update-tahun-ajaran` | Aksi update Tahun Ajaran | Mengubah tahun ajaran |
| **`tahun-ajaran.delete`** | `delete-tahun-ajaran` | Aksi delete Tahun Ajaran | Menghapus tahun ajaran |
| **`tahun-ajaran.toggle`** | `toggle-tahun-ajaran` | Aksi toggle Active | Active/Deactive tahun ajaran |
| **`kenaikan-kelas.view`** | `view-kenaikan-kelas` | `ManajemenKenaikanKelas.php` | Melihat kenaikan kelas |
| **`kenaikan-kelas.process`** | `process-kenaikan-kelas` | Aksi Proses Kenaikan | Proses kenaikan kelas |
| **`kenaikan-kelas.undo`** | `undo-kenaikan-kelas` | Aksi Undo | Batalkan kenaikan kelas |
| **`kenaikan-kelas.detail`** | `view-detail-kenaikan` | Detail Kenaikan | Lihat Detail Kenaikan |
| **`jenis-tagihan.view`** | `view-jenis-tagihan` | `ManajemenJenisTagihan.php` | Melihat jenis tagihan |
| **`jenis-tagihan.create`** | `create-jenis-tagihan` | Aksi create Jenis Tagihan | Menambah jenis tagihan |
| **`jenis-tagihan.update`** | `update-jenis-tagihan` | Aksi update Jenis Tagihan | Mengubah jenis tagihan |
| **`jenis-tagihan.delete`** | `delete-jenis-tagihan` | Aksi delete Jenis Tagihan | Menghapus jenis tagihan |
| **`tagihan.view`** | `view-tagihan` | `ManajemenTagihan.php` | Melihat daftar tagihan |
| **`tagihan.create`** | `create-tagihan` | Aksi create Tagihan | Menambah tagihan |
| **`tagihan.update`** | `update-tagihan` | Aksi update Tagihan | Mengubah tagihan |
| **`tagihan.delete`** | `delete-tagihan` | Aksi delete Tagihan | Menghapus tagihan |
| **`tagihan.export`** | `export-data` | Aksi export Tagihan | Export data tagihan |
| **`tagihan.siswa`** | `view-own-billing` | Detail tagihan siswa di Portal | Tagihan per siswa |
| **`pembayaran.view`** | `view-pembayaran` | `ManajemenPembayaran.php` | Melihat pembayaran |
| **`pembayaran.create`** | `create-pembayaran` | Aksi create Pembayaran | Mencatat pembayaran |
| **`pembayaran.delete`** | `delete-pembayaran` | Aksi delete Pembayaran | Menghapus pembayaran |
| **`pembayaran.kwitansi`** | `print-kwitansi` | Aksi cetak kwitansi (`pembayaran.print`) | Print Kwitansi |
| **`pembayaran.siswa`** | `view-own-billing` | Detail pembayaran siswa di Portal | Pembayaran siswa |
| **`pengeluaran.view`** | `view-pengeluaran` | `DaftarPengeluaran.php`, `PermintaanPengeluaran.php` | Melihat request pengeluaran |
| **`pengeluaran.create`** | `create-pengeluaran` | Aksi create Pengeluaran | Membuat request pengeluaran |
| **`pengeluaran.update`** | `update-pengeluaran` | Aksi update Pengeluaran | Mengubah request pengeluaran |
| **`pengeluaran.delete`** | `delete-pengeluaran` | Aksi delete Pengeluaran | Menghapus request pengeluaran |
| **`pengeluaran.approve`** | `approve-pengeluaran` | `PersetujuanPengeluaran.php`, Aksi Approve | Menyetujui request |
| **`pengeluaran.disburse`** | `disburse-pengeluaran` | `PencairanPengeluaran.php`, Aksi Disburse | Mencairkan request |
| **`midtrans.admin`** | `view-midtrans-transactions` | Fitur Log Midtrans | Admin transaksi Midtrans |
| **`midtrans.pay`** | `pay-tagihan-online` | `TagihanSiswa.php` (Aksi `pay`, `continue`, dll) | Pembayaran online via Midtrans |
| **`midtrans.sync`** | `sync-midtrans-transactions` | Aksi Sync Midtrans | Sinkronisasi status transaksi |
| **`laporan.kas`** | `view-kas-harian` | `LaporanKasHarian.php` | Melihat kas harian |
| **`laporan.kas-detail`** | `detail-kas-harian` | Aksi `detail` di Kas Harian | Detail kas harian |
| **`laporan.rekap`** | `view-rekap-bulanan` | `LaporanRekapBulanan.php` | Melihat rekap bulanan |
| **`laporan.rekap-detail`** | `detail-rekap-bulanan` | Aksi `detail` di Rekap Bulanan | Detail rekap bulanan |
| **`laporan.export`** | `export-laporan` | Aksi `Export` Laporan | Ekspor Laporan Excel/PDF |
| **`import-data`** | `import-data` | Aksi Import File | Import data sistem |
| **`export-data`** | `export-data` | Aksi Export File Umum | Export data sistem |
| **`import-export.job-status`** | `view-import-export-job` | Fitur background job tracking | Melihat status job |
| **`users.view`** | `view-user` | `UserManagement.php` | Melihat daftar user |
| **`users.create`** | `create-user` | Aksi create User | Menambah user |
| **`users.read`** | `read-user` | Detail User | Melihat detail user |
| **`users.update`** | `update-user` | Aksi update User | Mengubah user |
| **`users.delete`** | `delete-user` | Aksi delete User | Menghapus user |
| **`users.toggle`** | `toggle-user` | Aksi toggle status User | Active/Deactive user |
| **`role.view`** | `view-roles` | `RoleManagement.php` | Melihat daftar role |
| **`role.create`** | `create-role` | Aksi create Role | Membuat role baru |
| **`role.update`** | `update-role` | Aksi update Role | Mengubah role |
| **`role.delete`** | `delete-role` | Aksi delete Role | Menghapus role |
| **`akun-siswa.view`** | `view-akun-siswa` | `ManajemenAkunSiswa.php` | Melihat akun siswa |
| **`akun-siswa.create`** | `generate-akun-siswa` | Aksi generate akun | Generate akun siswa |
| **`akun-siswa.toggle`** | `toggle-akun-siswa` | Aksi toggle status akun | Active/Deactive akun siswa |
| **`akun-siswa.reset`** | `reset-akun-siswa-password` | Aksi reset password | Reset password akun siswa |
| **`akun-siswa.view-credentials`** | `view-akun-siswa-credentials` | Aksi view credentials | Melihat credential akun siswa |
| **`akun-siswa.print-credentials`** | `print-akun-siswa` | Aksi print credentials | Cetak credential akun siswa |
| **`pengaturan.view`** | `view-app-setting` | `Settings.php` (Pengaturan Umum) | Melihat pengaturan sistem |
| **`pengaturan.update`** | `update-app-setting` | Aksi Simpan Pengaturan | Mengubah pengaturan sistem |
| **`auto-approve.view`** | `view-auto-approve-setting` | `PengaturanPersetujuanPengeluaran.php` | Pengaturan auto approve |
| **`auto-approve.update`** | `update-auto-approve-setting` | Aksi Simpan Auto Approve | Mengubah auto approve |
| **`branch.view`** | `view-branch` | `ManajemenBranch.php` | Melihat cabang |
| **`branch.create`** | `create-branch` | Aksi create Branch | Menambah cabang |
| **`branch.read`** | `read-branch` | Detail Branch | Melihat detail cabang |
| **`branch.update`** | `update-branch` | Aksi update Branch | Mengubah cabang |
| **`branch.delete`** | `delete-branch` | Aksi delete Branch | Menghapus cabang |
| **`notification-setting.view`** | `view-notification-setting` | `NotificationSetting.php` | Melihat pengaturan notifikasi |
| **`notification-setting.update`** | `update-notification-setting` | Aksi Simpan Notifikasi | Mengupdate pengaturan notifikasi |
| **`notification-logs.view`** | `view-notification-logs` | `NotificationLogs.php` | Melihat log notifikasi |
| **`notification-logs.retry`** | `retry-notification` | Aksi Retry Notification | Retry notifikasi gagal |
| **`rbac`** | `manage-rbac` | `RbacDashboard.php` | Mengelola Dashboard RBAC |
| **`permission.view`** | `view-permissions` | Sub-menu Permissions | Melihat daftar permission |
| **`permission.create`** | `create-permission` | Aksi create Permission | Membuat permission baru |
| **`permission.update`** | `update-permission` | Aksi update Permission | Mengubah permission |
| **`permission.delete`** | `delete-permission` | Aksi delete Permission | Menghapus permission |
| **`endpoint-mapping.view`** | `view-endpoint-mapping` | Sub-menu Mapping Endpoint | Melihat endpoint mapping |
| **`endpoint-mapping.create`** | `create-endpoint-mapping` | Aksi create Endpoint | Membuat endpoint mapping |
| **`endpoint-mapping.update`** | `update-endpoint-mapping` | Aksi update Endpoint | Mengubah endpoint mapping |
| **`endpoint-mapping.delete`** | `delete-endpoint-mapping` | Aksi delete Endpoint | Menghapus endpoint mapping |
| **`resource-registry.view`** | `view-resource-registry` | Sub-menu Resource Registry | Melihat resoure registry |
| **`resource-registry.create`** | `create-resource-registry` | Aksi create Resource | Membuat resource registry |
| **`resource-registry.update`** | `update-resource-registry` | Aksi update Resource | Mengubah resource registry |
| **`resource-registry.delete`** | `delete-resource-registry` | Aksi delete Resource | Menghapus resource registry |
| **`portal.billing`** | `view-own-billing` | Khusus Portal UI | Billing view UI |
| **`portal.tagihan`** | `view-tagihan-siswa` | Khusus Portal UI | Akses menu Tagihan Siswa UI |
| **`portal-beranda`** | `view-tagihan-siswa` | Khusus Portal UI | Akses Beranda Portal UI |
| **`portal-access`** | `view-own-billing` | Akses Global Panel Portal | Root Access Portal |

## Catatan Perubahan & Implementasi
- Variasi key dari `siswa.view` (yaitu `ayah.view`, `ibu.view`, `wali.view`, dll) sekarang dimasukkan ke dalam `PermissionResourceSeeder` sesuai dengan keselarasan UI yang menggunakan izin turunan dari parent (jawaban rekomendasi A2 & A3).
- Resource key khusus navigasi frontend seperti `portal-access`, `portal-beranda`, dan `dashboard` dimasukkan ke dalam *registry* karena UI mem-fetch konfigurasi ini dari DB melalui endpoint `/rbac/user-resources` (jawaban rekomendasi A3).
- Format nama untuk grup utama (seperti `kategori`, `kelas`, `tahun-ajaran`) diubah menggunakan notasi `.view` (seperti `kategori.view`, `kelas.view`) untuk menyelaraskannya dengan *best practice* naming convention `PermissionEndpointSeeder` (jawaban A1).
- Perizinan yang tidak lagi ada di sistem (seperti `create-pengeluaran-request`) di-mapping kembali ke `create-pengeluaran` yang sesuai dengan `App\Enum\Permission::CREATE_PENGELUARAN`.
