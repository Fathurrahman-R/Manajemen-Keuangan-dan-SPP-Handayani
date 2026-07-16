# Laporan Implementasi Abstraksi Resource Key Frontend

Berikut adalah daftar *resource key* yang digunakan untuk proteksi halaman (Pages) dan kontrol visibilitas aksi (Actions/Buttons) di dalam aplikasi `frontend-v2`, berserta lokasi file penggunanya. Laporan ini juga mencakup implementasi terbaru pada halaman dan aksi yang sebelumnya belum terproteksi.

## 1. Resource Key untuk Halaman (Pages)

Halaman-halaman berikut mengevaluasi `resource_key` melalui method `canAccess()` atau pengecekan serupa sebelum merender halaman.

- **`portal-access`**
  - `app/Filament/Portal/Pages/PortalDashboard.php`
  - `app/Filament/Portal/Pages/PortalProfilWaliPage.php`
  - `app/Filament/Portal/Pages/PortalProfilSiswaPage.php`
  - `app/Filament/Portal/Pages/PortalRiwayatPembayaranPage.php`
  - `app/Filament/Portal/Pages/PortalTagihanPage.php`
  - dan halaman portal lainnya.
- **`dashboard`**
  - `app/Filament/Pages/DashboardPage.php`
- **`branch`**
  - `app/Filament/Pages/ManajemenBranch.php`
- **`branch-approval-setting`**
  - `app/Filament/Pages/PengaturanPersetujuanPengeluaran.php`
- **`kategori`**
  - `app/Filament/Pages/ManajemenKategoriSiswa.php`
- **`kelas`**
  - `app/Filament/Pages/ManajemenKelasSiswa.php`
- **`siswa`**
  - `app/Filament/Pages/ManajemenDataSiswa.php`
- **`siswa.view`** *(BARU)*
  - `app/Filament/Pages/DetailSiswa.php`
  - `app/Filament/Pages/DetailWali.php`
- **`kenaikan-kelas`**
  - `app/Filament/Pages/ManajemenKenaikanKelas.php`
- **`kas-harian`**
  - `app/Filament/Pages/LaporanKasHarian.php`
- **`rekap-bulanan`**
  - `app/Filament/Pages/LaporanRekapBulanan.php`
- **`akun-siswa`**
  - `app/Filament/Pages/ManajemenAkunSiswa.php`
- **`notification-logs`**
  - `app/Filament/Pages/NotificationLogs.php`
- **`notification-setting`**
  - `app/Filament/Pages/NotificationSetting.php`
- **`pengeluaran`**
  - `app/Filament/Pages/DaftarPengeluaran.php`
- **`pengeluaran.request`**
  - `app/Filament/Pages/PermintaanPengeluaran.php`
- **`pengeluaran.approve`**
  - `app/Filament/Pages/PersetujuanPengeluaran.php`
- **`pengeluaran.disburse`**
  - `app/Filament/Pages/PencairanPengeluaran.php`
- **`rbac`**
  - `app/Filament/Pages/RbacDashboard.php`
- **`role-management`**
  - `app/Filament/Pages/RoleManagement.php`
- **`app-setting`**
  - `app/Filament/Pages/Settings.php`
- **`tahun-ajaran`**
  - `app/Filament/Pages/ManajemenTahunAjaran.php`
- **`jenis-tagihan`**
  - `app/Filament/Pages/ManajemenJenisTagihan.php`
- **`pembayaran`**
  - `app/Filament/Pages/ManajemenPembayaran.php`
- **`tagihan`**
  - `app/Filament/Pages/ManajemenTagihan.php`
- **`user-management`**
  - `app/Filament/Pages/UserManagement.php`

> **Catatan**: `app/Filament/Pages/Auth/EditProfile.php` tidak diproteksi oleh *resource key* spesifik karena secara desain terbuka untuk seluruh user yang sudah login (Authenticated).

---

## 2. Resource Key untuk Aksi (Actions)

Daftar *resource key* yang disematkan menggunakan method `->visible(fn() => PermissionHelper::hasResource(...))` pada aksi tombol, bulk actions, maupun aksi header di Livewire Table.

- **`siswa.view`** *(BARU)*
  - `app/Livewire/DataSiswa.php` (Aksi: `view`)
  - `app/Livewire/DataWali.php` (Aksi: `view`)
- **`siswa.create`**
  - `app/Livewire/DataSiswa.php` (Aksi: `add_detail`, `add`)
  - `app/Livewire/DataWali.php` (Aksi: `add`)
- **`siswa.update`**
  - `app/Livewire/DataSiswa.php` (Aksi: `update_detail`, `update`)
  - `app/Livewire/DataWali.php` (Aksi: `update`)
- **`siswa.delete`**
  - `app/Livewire/DataSiswa.php` (Aksi: `delete`, `bulkDelete`)
  - `app/Livewire/DataWali.php` (Aksi: `delete`, `bulkDelete`)
- **`laporan.kas-detail`** *(BARU)*
  - `app/Livewire/KasHarian.php` (Aksi: `detail`)
- **`laporan.rekap-detail`** *(BARU)*
  - `app/Livewire/RekapBulanan.php` (Aksi: `detail`)
- **`laporan.export`**
  - `app/Livewire/KasHarian.php` (Aksi: `Export`, `export_excel`)
  - `app/Livewire/RekapBulanan.php` (Aksi: `Export`, `export_excel`)
- **`midtrans.pay`** *(BARU)*
  - `app/Livewire/TagihanSiswa.php` (Aksi: `pay`, `continue`, `resume`, `payBatch`)
- **`akun-siswa.toggle-active`**
  - `app/Filament/Pages/ManajemenAkunSiswa.php`
- **`akun-siswa.reset-password`**
  - `app/Filament/Pages/ManajemenAkunSiswa.php`
- **`akun-siswa.view-credentials`**
  - `app/Filament/Pages/ManajemenAkunSiswa.php`
- **`akun-siswa.print`**
  - `app/Filament/Pages/ManajemenAkunSiswa.php`
- **`akun-siswa.generate`**
  - `app/Filament/Pages/ManajemenAkunSiswa.php`
- **`pembayaran.print`**
  - `app/Filament/Pages/ManajemenPembayaran.php`

## Status Penyelesaian
Implementasi penambahan *resource key* pada halaman dan aksi yang sebelumnya tidak terproteksi (`DetailSiswa.php`, `DetailWali.php`, `KasHarian.php`, `RekapBulanan.php`, dan `TagihanSiswa.php`) telah **berhasil ditambahkan**. Semua fitur ini kini mengevaluasi visibilitas dan aksesnya melalui abstraksi `PermissionHelper::hasResource()`.
