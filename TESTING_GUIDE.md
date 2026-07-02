# Panduan Testing — Frontend Polish Phase 3 & 4

## Persiapan

### 1. Reset & Seed Database Backend

```bash
cd backend
php artisan migrate:fresh --seed
php artisan db:seed --class=FrontendPolishTestSeeder
```

### 2. Jalankan Backend

```bash
cd backend
php artisan serve --port=8000
```

### 3. Build & Jalankan Frontend

```bash
cd frontend-v2
php artisan migrate
npm run build
php artisan serve --port=8080
```

### 4. Akun Testing

| Username | Password | Role | Catatan |
|----------|----------|------|---------|
| `admin123` | `admin123` | superadmin | Akses penuh, semua fitur |
| `siswa_test` | `password123` | siswa | Portal siswa/wali |
| `user_baru` | `password123` | admin | Wajib ganti password saat login |

---

## Checklist Testing

### A. Error Handling — API Down (Simulasi: matikan backend)

Matikan server backend (`Ctrl+C` pada `php artisan serve` backend), lalu buka frontend.

| # | Test | Expected | Status |
|---|------|----------|--------|
| A1 | Buka halaman Data Siswa | Tabel kosong + notifikasi "Server tidak dapat dihubungi" (persistent, warna merah) | ☐ |
| A2 | Buka halaman Data Kelas | Sama seperti A1 | ☐ |
| A3 | Buka halaman Kategori | Sama seperti A1 | ☐ |
| A4 | Buka halaman Data Wali | Sama seperti A1 | ☐ |
| A5 | Buka halaman Jenis Tagihan | Sama seperti A1 | ☐ |
| A6 | Buka halaman User Management | Sama seperti A1 | ☐ |
| A7 | Buka halaman Role Management | Sama seperti A1 | ☐ |
| A8 | Buka halaman Tahun Ajaran | Sama seperti A1 | ☐ |
| A9 | Buka halaman Pengeluaran | Sama seperti A1 | ☐ |
| A10 | Buka halaman Kas Harian | Sama seperti A1 | ☐ |
| A11 | Buka halaman Rekap Bulanan | Sama seperti A1 | ☐ |
| A12 | Buka halaman Branch Management | Sama seperti A1 | ☐ |
| A13 | Buka Dashboard | Widget menampilkan data kosong/zero — TIDAK crash, TIDAK error putih | ☐ |
| A14 | Buka halaman Pengaturan | Muncul pesan "Data Tidak Tersedia" — tidak crash | ☐ |

**Setelah selesai, nyalakan kembali backend.**

---

### B. Dashboard Widgets

Login sebagai `admin123`.

| # | Test | Expected | Status |
|---|------|----------|--------|
| B1 | Buka Dashboard | 6 stat cards tampil (Total Tagihan, Terbayar, Tunggakan, Siswa Aktif, Menunggak, Pelunasan) | ☐ |
| B2 | Lihat chart Pembayaran per Bulan | Bar chart tampil | ☐ |
| B3 | Lihat chart Tunggakan per Jenjang | Doughnut chart tampil | ☐ |
| B4 | Lihat chart Pemasukan vs Pengeluaran | Line chart tampil | ☐ |
| B5 | Lihat chart Status Tagihan | Pie chart tampil | ☐ |
| B6 | Lihat tabel Tagihan Jatuh Tempo | Tabel dengan empty state jika tidak ada data | ☐ |
| B7 | Lihat tabel Pembayaran Terbaru | Tabel dengan data pembayaran terakhir | ☐ |
| B8 | Lihat tabel Top Tunggakan | Tabel dengan data siswa menunggak | ☐ |

---

### C. Dark Mode

| # | Test | Expected | Status |
|---|------|----------|--------|
| C1 | Toggle dark mode dari user menu | Semua halaman berubah ke dark mode | ☐ |
| C2 | Halaman Tagihan (card view) | Background gelap, teks terbaca jelas, badge berwarna sesuai | ☐ |
| C3 | Halaman Pembayaran (card view) | Background gelap, teks terbaca jelas | ☐ |
| C4 | Halaman Siswa Dashboard (portal) | Background gelap, summary cards terbaca | ☐ |
| C5 | Halaman Kenaikan Kelas | Background gelap, kelas list terbaca | ☐ |
| C6 | Halaman Change Password | Form input terlihat jelas, border & placeholder kontras | ☐ |
| C7 | Halaman Settings | Section data sekolah terbaca jelas | ☐ |
| C8 | Semua tabel data (Siswa, Kelas, dll) | Row striped visible in dark mode | ☐ |

---

### D. Notifikasi (Bell Icon)

Login sebagai `admin123`.

| # | Test | Expected | Status |
|---|------|----------|--------|
| D1 | Lihat header panel | Bell icon muncul di kanan atas | ☐ |
| D2 | Lihat badge count | Angka badge = jumlah notifikasi unread (harusnya 4) | ☐ |
| D3 | Klik bell icon | Panel dropdown terbuka, tampil list notifikasi (title + timestamp) | ☐ |
| D4 | Klik satu notifikasi | Notifikasi ditandai "read", badge count berkurang | ☐ |
| D5 | Klik "Mark all as read" | Semua notifikasi ditandai read, badge = 0 | ☐ |
| D6 | Tunggu 30 detik | Data otomatis ter-refresh (polling) | ☐ |

---

### E. Tagihan Card View

Login sebagai `admin123`, buka halaman Tagihan.

| # | Test | Expected | Status |
|---|------|----------|--------|
| E1 | Search siswa | Input search menggunakan Filament style (icon search di kiri) | ☐ |
| E2 | Filter jenjang dropdown | Select dropdown pakai Filament style native | ☐ |
| E3 | Filter status dropdown | Select dropdown pakai Filament style native | ☐ |
| E4 | Lihat badge status | Badge "Belum Dibayar" (merah), "Belum Lunas" (kuning), "Lunas" (hijau) — pakai `<x-filament::badge>` | ☐ |
| E5 | Klik tombol "Bayar" | Modal muncul dengan form pembayaran (schema pattern, bukan form lama) | ☐ |
| E6 | Klik icon trash (hapus) | Icon button Filament native, muncul confirm dialog | ☐ |
| E7 | Lihat pagination | Tombol Prev/Next/Page pakai Filament button style | ☐ |
| E8 | Badge "Jatuh Tempo" | Muncul pada tagihan yang lewat jatuh tempo, warna danger | ☐ |

---

### F. Pembayaran Card View

Login sebagai `admin123`, buka halaman Pembayaran.

| # | Test | Expected | Status |
|---|------|----------|--------|
| F1 | Search siswa | Input search Filament native | ☐ |
| F2 | Filter jenjang | Select Filament native | ☐ |
| F3 | Badge metode pembayaran | "Tunai" = info (biru), "Non-Tunai" = gray | ☐ |
| F4 | Icon download kwitansi | Filament icon-button (arrow-down-tray), tooltip "Download Kwitansi" | ☐ |
| F5 | Icon hapus pembayaran | Filament icon-button (trash), warna danger, tooltip "Hapus Pembayaran" | ☐ |
| F6 | Pagination | Tombol pakai Filament button style | ☐ |

---

### G. Pengeluaran (Workflow)

Login sebagai `admin123`, buka halaman Pengeluaran.

| # | Test | Expected | Status |
|---|------|----------|--------|
| G1 | Tabel pengeluaran request tampil | 6 data tampil (draft, submitted, approved, rejected, disbursed) | ☐ |
| G2 | Filter status | SelectFilter native Filament berfungsi | ☐ |
| G3 | Tombol "Buat Request" | Modal form muncul (TextInput, DatePicker) | ☐ |
| G4 | Action "Submit" pada draft | Tombol submit muncul, confirm dialog | ☐ |
| G5 | Action "Approve" pada submitted | Modal dengan textarea catatan | ☐ |
| G6 | Action "Reject" pada submitted | Modal dengan textarea alasan (required) | ☐ |
| G7 | Action "Cairkan" pada approved | Confirm dialog, notifikasi sukses | ☐ |

---

### H. Settings Page

Login sebagai `admin123`, buka halaman Pengaturan.

| # | Test | Expected | Status |
|---|------|----------|--------|
| H1 | Data sekolah tampil | Nama, alamat, lokasi, email, telepon, kepsek, bendahara tampil | ☐ |
| H2 | Klik "Ubah" | Modal terbuka dengan 3 section: Informasi Sekolah, Kontak, Kepemimpinan | ☐ |
| H3 | Ubah nama sekolah | Save → notifikasi sukses → data ter-refresh tanpa reload | ☐ |
| H4 | Upload logo baru | FileUpload preview muncul, save berhasil | ☐ |

---

### I. Login & Change Password

| # | Test | Expected | Status |
|---|------|----------|--------|
| I1 | Login `user_baru` / `password123` | Redirect otomatis ke /change-password | ☐ |
| I2 | Halaman Change Password | Form Filament native (Section "Ubah Password", 3 TextInput password) | ☐ |
| I3 | Submit password baru (min 8 char) | Validasi Filament, notifikasi sukses, redirect ke login | ☐ |
| I4 | Login admin123, cek redirect | Sudah login → refresh halaman login → redirect ke dashboard (bukan stuck) | ☐ |
| I5 | Link "Lupa Password?" di login | Link visible, navigasi ke halaman reset | ☐ |

---

### J. Portal Siswa

Login sebagai `siswa_test` / `password123`.

| # | Test | Expected | Status |
|---|------|----------|--------|
| J1 | Dashboard siswa | Summary cards tampil (tagihan, pembayaran) | ☐ |
| J2 | Error handling portal | Jika API down → notifikasi Filament (bukan error putih) | ☐ |
| J3 | Halaman Profil | Data user tampil, form email & password berfungsi | ☐ |

---

### K. Table Features (Native Filament)

Login sebagai `admin123`, cek di semua halaman tabel.

| # | Test | Expected | Status |
|---|------|----------|--------|
| K1 | Empty state | Semua tabel kosong menampilkan icon + heading "Tidak Ada Data" | ☐ |
| K2 | Pagination options | Dropdown per-page: 5, 10, 25, 50 tersedia | ☐ |
| K3 | Default pagination | Default = 10 per halaman | ☐ |
| K4 | Column toggle (DataSiswa) | Kolom 5+ (Jenis Kelamin, Tanggal Lahir, Agama, dll) bisa show/hide via toggle | ☐ |
| K5 | SelectFilter (DataSiswa) | Filter "Status" (Aktif/Lulus/Pindah/Keluar) muncul di panel filter | ☐ |
| K6 | SelectFilter (TahunAjaran) | Filter "Status" (Aktif/Non-Aktif) berfungsi | ☐ |
| K7 | SelectFilter (UserManagement) | Filter "Role" berfungsi | ☐ |

---

### L. Dead Code Cleanup Verification

| # | Test | Expected | Status |
|---|------|----------|--------|
| L1 | Build frontend | `npm run build` sukses tanpa error | ☐ |
| L2 | PHP syntax | `php artisan route:clear && config:clear && view:clear` sukses | ☐ |
| L3 | Jalankan tests | `php artisan test` di frontend-v2/ → semua 27 tests pass | ☐ |

---

## Catatan Penting

1. **Untuk testing error handling (Bagian A)**: Matikan hanya server backend, biarkan frontend tetap jalan.
2. **Notifications**: Frontend polls setiap 30 detik. Jika bell icon tidak muncul segera, tunggu hingga polling pertama terjadi.
3. **Dark mode toggle** ada di user menu (klik avatar/nama di pojok kanan atas).
4. **Jika data tidak muncul**: Pastikan backend berjalan dan `.env` frontend mengarah ke URL backend yang benar.
5. **Migration frontend**: `notifications` table perlu dimigrate di frontend-v2 (`php artisan migrate`).
