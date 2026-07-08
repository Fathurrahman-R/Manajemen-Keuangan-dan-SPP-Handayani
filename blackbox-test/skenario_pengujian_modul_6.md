# Skenario Pengujian Blackbox — Modul 6: Laporan, Dashboard & Notifikasi

> **Tanggal:** 8 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing)
> **Lingkungan Uji:** `http://127.0.0.1:8000` (Frontend-v2 Filament)
> **Akun:** `admin@handayani.test` / `admin123` (Superadmin) — khusus Portal Siswa Dashboard menggunakan NIS
> **Teknik:** Equivalence Partitioning, Boundary Value Analysis, Error Guessing, Exploratory Testing, State Transition Testing
> **Aspek:** Functional (wajib), UI/UX, Security/RBAC

---

## Daftar Sub-Modul

| No | Fitur | Halaman (Frontend) | Backend Controller | API Endpoints | Prioritas |
|----|-------|-------------------|--------------------|---------------|-----------|
| 1 | **Dashboard Admin (DBH)** | Dashboard (`/dashboard-page`) | `DashboardController` | `/dashboard/summary`, `/dashboard/all-time-summary`, `/dashboard/charts/*`, `/dashboard/top-tunggakan`, `/dashboard/tagihan-jatuh-tempo`, `/dashboard/pembayaran-terbaru`, `/dashboard/kas-summary` | High |
| 2 | **Kas Harian (KSH)** | Laporan → Kas Harian | `KasController` | `/laporan/kas` (GET), `/laporan/kas/detail` (GET), `/laporan/export/kas` (GET) | High |
| 3 | **Rekap Bulanan (RKB)** | Laporan → Rekap Bulanan | `KasController` | `/laporan/rekap` (GET), `/laporan/rekap/detail` (GET), `/laporan/export/rekap` (GET) | High |
| 4 | **Notification Log (NTL)** | Laporan → Log Notifikasi | `NotificationLogController` | `/notification-logs` (GET), `/notification-logs/retry` (POST) | High |
| 5 | **Notification Settings (NTS)** | Pengaturan → Notifikasi | `NotificationSettingController` | `/notification-settings` (GET), `/notification-settings` (PUT) | High |
| 6 | **In-App Notification (NTF)** | Bell icon (header) | `NotificationController` | `/notifications` (GET), `/notifications/unread-count`, `/notifications/{id}/read`, `/notifications/mark-all-read` | Medium |
| 7 | **Portal Siswa Dashboard (PSD)** | Portal → Beranda | `DashboardController` | `/dashboard/siswa` (GET) | High |

---

## 1. Fitur: Dashboard Admin (DBH)

**Halaman:** Dashboard (`/dashboard-page`)
**Fitur:** All-time stats (total tagihan, pemasukan, pengeluaran), KPI periode (total tagihan, terbayar, tunggakan, % pelunasan), Kas Summary, Charts (pembayaran bulanan, kas bulanan, tunggakan per jenjang, status tagihan), Tabel (top 10 tunggakan, jatuh tempo 7 hari, pembayaran terbaru), Filter periode dropdown

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **DBH-001** | Dashboard — semua widget termuat | Exploratory | Functional | Login sbg admin | 1. Buka `/dashboard-page` | N/A | Semua widget muncul tanpa error: KPI all-time (3 stat), KPI periode (6 stat), Kas summary, Charts (4), Tabel (3). | High |
| **DBH-002** | Dashboard — filter periode dropdown | Equivalence Partitioning | Functional | Ada >1 periode (1 aktif, 1 nonaktif) | 1. Buka dashboard<br>2. Ganti dropdown periode ke yang nonaktif<br>3. Tunggu reload widget | N/A | Semua widget refresh: data berubah sesuai periode yang dipilih. | High |
| **DBH-003** | Dashboard — KPI all-time akurat | Equivalence Partitioning | Functional | Ada data tagihan, pemasukan, pengeluaran | 1. Buka dashboard<br>2. Catat angka "Total Tagihan", "Total Pemasukan", "Total Pengeluaran" | N/A | Angka sesuai akumulasi dari semua periode (bukan hanya periode aktif). | High |
| **DBH-004** | Dashboard — KPI periode akurat | Equivalence Partitioning | Functional | Ada data di periode aktif | 1. Catat 6 KPI: Total Tagihan, Terbayar, Tunggakan, Jml Siswa Tagihan, Jml Menunggak, % Pelunasan | N/A | Total Tagihan = Terbayar + Tunggakan (seharusnya). Persentase logis. | High |
| **DBH-005** | Dashboard — Kas Summary tampil | Exploratory | Functional | Ada pemasukan & pengeluaran | 1. Scroll ke Kas Summary | N/A | Widget menampilkan total pemasukan, pengeluaran, dan saldo periode. | High |
| **DBH-006** | Dashboard — Chart Pembayaran Bulanan | Exploratory | Functional | Ada data pembayaran | 1. Perhatikan chart bar | N/A | Chart bar menampilkan data pembayaran per bulan dengan label nama bulan. | Medium |
| **DBH-007** | Dashboard — Chart Kas Bulanan | Exploratory | Functional | Ada data pemasukan & pengeluaran | 1. Perhatikan chart | N/A | Chart menampilkan 2 series: pemasukan dan pengeluaran. | Medium |
| **DBH-008** | Dashboard — Chart Tunggakan Jenjang | Exploratory | Functional | Ada siswa dari berbagai jenjang | 1. Perhatikan chart | N/A | Chart bar menampilkan total tunggakan per jenjang (KB, TK, MI). | Medium |
| **DBH-009** | Dashboard — Chart Status Tagihan | Exploratory | Functional | Ada tagihan berbagai status | 1. Perhatikan chart | N/A | Chart pie/donut/bar menampilkan proporsi status tagihan (Lunas, Belum Dibayar, Belum Lunas). | Medium |
| **DBH-010** | Dashboard — Tabel Top 10 Tunggakan | Exploratory | Functional | Ada data tunggakan | 1. Scroll ke tabel Top 10 Tunggakan | N/A | Menampilkan 10 siswa dengan total tunggakan terbesar. Kolom NIS, Nama, Kelas, Jenjang (badge), Total Tunggakan (Rp). | High |
| **DBH-011** | Dashboard — Tabel Jatuh Tempo 7 Hari | Exploratory | Functional | Ada tagihan mendekati JT | 1. Scroll ke tabel Tagihan Jatuh Tempo | N/A | Menampilkan tagihan yang jatuh tempo dalam 7 hari ke depan. Kolom Siswa, Jenis Tagihan, Jatuh Tempo, Jumlah, Status (badge). | High |
| **DBH-012** | Dashboard — Tabel Pembayaran Terbaru | Exploratory | Functional | Ada pembayaran baru | 1. Scroll ke tabel Pembayaran Terbaru | N/A | Menampilkan 5-10 pembayaran terbaru. Kolom relevan. | Medium |
| **DBH-013** | Dashboard — tombol Refresh | Exploratory | UI/UX | Dashboard termuat | 1. Klik tombol "Refresh" | N/A | Halaman melakukan reload/refresh data tanpa error. | Low |
| **DBH-014** | Dashboard — loading state saat ganti periode | Exploratory | UI/UX | Banyak data | 1. Ganti periode<br>2. Perhatikan UI | N/A | Widget menampilkan indikator loading (opacity/loading bar). | Medium |
| **DBH-015** | Dashboard — RBAC tanpa `view-dashboard` | Security (dasar) | Security | Login sbg admin tanpa permission view-dashboard | 1. Paksa buka `/dashboard-page` | N/A | 403 Forbidden. | High |
| **DBH-016** | Dashboard — akses oleh role siswa | Security (dasar) | Security | Login sbg siswa | 1. Paksa buka `/dashboard-page` | N/A | Redirect ke portal atau 403. | High |

---

## 2. Fitur: Kas Harian (KSH)

**Halaman:** Laporan → Kas Harian
**Fitur:** Tabel kas per tanggal (dalam bulan), filter bulan/tahun, sort, detail modal per tanggal (lihat pemasukan & pengeluaran), export PDF, export Excel/CSV

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **KSH-001** | Tampilkan Kas Harian — default bulan ini | Equivalence Partitioning | Functional | Ada data pembayaran & pengeluaran | 1. Buka Laporan → Kas Harian | N/A | Tabel muncul dengan kolom: Tanggal, Total Masuk, Total Keluar, Saldo. Data diurutkan DESC (terbaru di atas). | High |
| **KSH-002** | Filter Kas Harian — ganti bulan & tahun | Equivalence Partitioning | Functional | Ada data di berbagai bulan | 1. Isi filter Bulan = 1<br>2. Isi filter Tahun = 2026<br>3. Tunggu reload | Bulan: 1, Tahun: 2026 | Tabel menampilkan data bulan Januari 2026. | High |
| **KSH-003** | Filter Kas Harian — bulan tidak valid | Boundary Value Analysis | Functional | - | 1. Isi Bulan = 13 | Bulan: 13 | Form menolak (max 12) atau API return 400. | Medium |
| **KSH-004** | Kas Harian — sort by kolom | Equivalence Partitioning | UI/UX | Ada data | 1. Klik header "Tanggal" 2x (DESC → ASC) | N/A | Urutan berubah. | Low |
| **KSH-005** | Kas Harian — modal detail tanggal | Equivalence Partitioning | Functional | Ada pemasukan & pengeluaran di 1 tanggal | 1. Klik "Detail" di baris tertentu | N/A | Modal terbuka dengan 2 tabel: Pemasukan (NIS, Nama, Nama Tagihan, Jumlah) dan Pengeluaran (Nama, Pengaju, Penyetuju, Jumlah). | High |
| **KSH-006** | Kas Harian — modal detail, data pemasukan | Exploratory | Functional | Ada pemasukan di tanggal tsb | 1. Buka modal detail | N/A | Tabel pemasukan menampilkan data yang sesuai. | High |
| **KSH-007** | Kas Harian — modal detail, data pengeluaran | Exploratory | Functional | Ada pengeluaran di tanggal tsb | 1. Buka modal detail | N/A | Tabel pengeluaran menampilkan data sesuai (Nama Pengeluaran, Pengaju, Penyetuju). | High |
| **KSH-008** | Kas Harian — modal detail tutup | Exploratory | UI/UX | Modal terbuka | 1. Klik "Tutup" | N/A | Modal tertutup. | Low |
| **KSH-009** | Kas Harian — pagination | Equivalence Partitioning | UI/UX | Banyak data >10 baris | 1. Klik halaman 2 | N/A | Navigasi halaman berfungsi. | Low |
| **KSH-010** | Kas Harian — export PDF | Equivalence Partitioning | Functional | Ada data | 1. Klik "Export PDF"<br>2. Pilih bulan, tahun<br>3. Export | Bulan: now()->month, Tahun: now()->year | File `Kas harian-{bulan}-{tahun}.pdf` terdownload. | High |
| **KSH-011** | Kas Harian — export PDF bulan kosong | Error Guessing | Functional | Tidak ada data di bulan tsb | 1. Pilih bulan tanpa data<br>2. Export | Bulan: 1, Tahun: 2020 | PDF tetap terdownload (mungkin dengan header + "Tidak ada data") ATAU error 404. | Medium |
| **KSH-012** | Kas Harian — export Excel (.xlsx) | Equivalence Partitioning | Functional | Ada data | 1. Klik "Export Excel/CSV"<br>2. Pilih xlsx, isi bulan, tahun<br>3. Export | Format: xlsx | File terdownload. | High |
| **KSH-013** | Kas Harian — export CSV | Equivalence Partitioning | Functional | Ada data | 1. Export CSV | Format: csv | File CSV terdownload. | High |
| **KSH-014** | Kas Harian — export tanpa permission `export-laporan` | Security (dasar) | Security | Admin tanpa permission export-laporan | 1. Buka halaman | N/A | Tombol export Excel/CSV tidak muncul (visible only if permission ada). | High |
| **KSH-015** | Kas Harian — RBAC akses halaman tanpa `view-kas-harian` | Security (dasar) | Security | Admin tanpa permission | 1. Paksa buka URL | N/A | 403 Forbidden. | High |
| **KSH-016** | Kas Harian — empty state | Equivalence Partitioning | UI/UX | Tidak ada data di bulan tsb | 1. Filter bulan tanpa data | N/A | Tabel empty state: "Tidak Ada Kas Harian" + deskripsi + icon. | Medium |

---

## 3. Fitur: Rekap Bulanan (RKB)

**Halaman:** Laporan → Rekap Bulanan
**Fitur:** Tabel rekap per bulan (dalam tahun), filter tahun, sort, modal detail per bulan, export PDF, export Excel/CSV

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **RKB-001** | Tampilkan Rekap Bulanan — default tahun ini | Equivalence Partitioning | Functional | Ada data di berbagai bulan | 1. Buka Laporan → Rekap Bulanan | N/A | Tabel menampilkan data per bulan: Tanggal (nama bulan), Total Masuk, Total Keluar, Saldo. | High |
| **RKB-002** | Filter Rekap — ganti tahun | Equivalence Partitioning | Functional | Ada data di tahun berbeda | 1. Isi filter Tahun = 2025 | Tahun: 2025 | Tabel menampilkan data tahun 2025. | High |
| **RKB-003** | Rekap — sort by kolom | Equivalence Partitioning | UI/UX | Ada data | 1. Klik header "Total Masuk" | N/A | Urutan berubah. | Low |
| **RKB-004** | Rekap — modal detail bulan | Equivalence Partitioning | Functional | Ada transaksi di bulan tsb | 1. Klik "Detail" di baris bulan tertentu | N/A | Modal terbuka menampilkan pemasukan & pengeluaran di bulan tsb. | High |
| **RKB-005** | Rekap — modal detail data akurat | Exploratory | Functional | Ada pemasukan | 1. Catat angka di baris bulan<br>2. Buka modal | N/A | Jumlah pemasukan di modal = Total Masuk di baris. | High |
| **RKB-006** | Rekap — export PDF | Equivalence Partitioning | Functional | Ada data | 1. Klik "Export PDF"<br>2. Isi Tahun<br>3. Export | Tahun: now()->year | File `Rekap Bulanan-{tahun}.pdf` terdownload. | High |
| **RKB-007** | Rekap — export Excel | Equivalence Partitioning | Functional | Ada data | 1. Klik "Export Excel/CSV"<br>2. Pilih xlsx, isi tahun<br>3. Export | Format: xlsx, Tahun: now()->year | File terdownload. | High |
| **RKB-008** | Rekap — export CSV | Equivalence Partitioning | Functional | Ada data | 1. Export CSV | Format: csv | File CSV terdownload. | High |
| **RKB-009** | Rekap — RBAC tanpa `view-rekap-bulanan` | Security (dasar) | Security | Admin tanpa permission | 1. Paksa buka URL | N/A | 403 Forbidden. | High |
| **RKB-010** | Rekap — empty state tahun tanpa data | Equivalence Partitioning | UI/UX | Tahun tanpa data | 1. Filter tahun tanpa transaksi | Tahun: 2020 | Empty state: "Tidak Ada Rekap Bulanan". | Medium |

---

## 4. Fitur: Notification Log (NTL)

**Halaman:** Laporan → Log Notifikasi
**Fitur:** Tabel log notifikasi email, filter tipe & status, search (client-side), badge warna per tipe/status, bulk retry (kirim ulang notifikasi gagal/dilewati)

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **NTL-001** | Tampilkan log notifikasi | Equivalence Partitioning | Functional | Ada notifikasi pernah terkirim | 1. Buka Laporan → Log Notifikasi | N/A | Tabel muncul dengan kolom: Waktu, Tipe (badge), Kode Tagihan, Email Tujuan, Status (badge), Alasan. | High |
| **NTL-002** | Log — filter tipe notifikasi | Equivalence Partitioning | Functional | Ada berbagai tipe notif | 1. Pilih filter Tipe "Kwitansi" | Tipe: kwitansi | Hanya log tipe kwitansi yang tampil. | High |
| **NTL-003** | Log — filter status notifikasi | Equivalence Partitioning | Functional | Ada berbagai status | 1. Pilih filter Status "Terkirim" | Status: sent | Hanya log status sent tampil. | Medium |
| **NTL-004** | Log — search by email | Equivalence Partitioning | Functional | Ada log dengan email tertentu | 1. Ketik email di search | Email: "budi@test.com" | Tabel terfilter (client-side). | Medium |
| **NTL-005** | Log — search by kode tagihan | Equivalence Partitioning | Functional | Ada log dengan kode tagihan | 1. Ketik kode tagihan | Kode: "TAG-" | Tabel terfilter (client-side). | Medium |
| **NTL-006** | Log — badge tipe benar | Exploratory | UI/UX | Ada berbagai tipe | 1. Perhatikan badge tipe | N/A | Warna badge: tagihan_baru (primary), kwitansi (success), reminder (warning), overdue (danger). Label Indonesia. | Medium |
| **NTL-007** | Log — badge status benar | Exploratory | UI/UX | Ada berbagai status | 1. Perhatikan badge status | N/A | Warna badge: sent (success), failed (danger), skipped (warning). Label Indonesia. | Medium |
| **NTL-008** | Log — alasan pada status failed/skipped | Exploratory | UI/UX | Ada log gagal | 1. Perhatikan kolom Alasan | N/A | Alasan jelas: "Email belum diatur", "Email tidak valid", "Berhenti langganan", "Notifikasi dinonaktifkan", dll. | High |
| **NTL-009** | Log — bulk retry notifikasi gagal | State Transition | Functional | Ada ≥1 log gagal/dilewati | 1. Centang log gagal<br>2. Klik "Kirim Ulang Terpilih"<br>3. Konfirmasi | N/A | Notif "{count} notifikasi berhasil dikirim ulang". | High |
| **NTL-010** | Log — bulk retry tanpa centang | Error Guessing | UI/UX | Tidak ada centang | 1. Klik "Kirim Ulang Terpilih" tanpa centang | N/A | Notifikasi warning "Tidak ada log yang dipilih". | Medium |
| **NTL-011** | Log — pagination | Equivalence Partitioning | UI/UX | Banyak data | 1. Klik halaman 2 | N/A | Pagination berfungsi. | Low |
| **NTL-012** | Log — RBAC tanpa `view-notification-logs` | Security (dasar) | Security | Admin tanpa permission | 1. Paksa buka URL | N/A | 403 Forbidden. | High |
| **NTL-013** | Log — sort by kolom | Equivalence Partitioning | UI/UX | Ada data | 1. Klik header Waktu | N/A | Urutan berubah. | Low |

---

## 5. Fitur: Notification Settings (NTS)

**Halaman:** Pengaturan → Notifikasi
**Fitur:** Form toggle enable/disable untuk 4 tipe notifikasi (Tagihan Baru, Kwitansi, Reminder, Overdue), set reminder days (tags), set overdue interval days, save

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **NTS-001** | Lihat pengaturan notifikasi — form termuat | Equivalence Partitioning | Functional | Login sbg admin dengan permission | 1. Buka Pengaturan → Notifikasi | N/A | Form muncul dengan 4 section collapsible: Tagihan Baru, Kwitansi, Pengingat Jatuh Tempo, Notifikasi Keterlambatan. Nilai default terisi. | High |
| **NTS-002** | Ubah toggle Tagihan Baru — nonaktifkan | Equivalence Partitioning | Functional | Form termuat | 1. Uncheck "Aktifkan notifikasi tagihan baru"<br>2. Simpan | tagihan_baru_enabled: false | Notifikasi "Pengaturan notifikasi berhasil disimpan." | High |
| **NTS-003** | Ubah toggle Kwitansi — nonaktifkan | Equivalence Partitioning | Functional | - | 1. Uncheck kwitansi<br>2. Simpan | kwitansi_enabled: false | Berhasil disimpan. | High |
| **NTS-004** | Ubah reminder days — input valid | Equivalence Partitioning | Functional | - | 1. Isi "Kirim pengingat H-": `7, 3, 1` (tags)<br>2. Simpan | reminder_days_before: [7, 3, 1] | Berhasil disimpan. Notifikasi reminder akan dikirim H-7, H-3, H-1. | High |
| **NTS-005** | Ubah reminder days — input kosong | Error Guessing | Functional | - | 1. Kosongkan tags<br>2. Simpan | reminder_days_before: [] | Berhasil disimpan (tidak ada reminder). | Medium |
| **NTS-006** | Ubah overdue interval — nilai valid | Boundary Value Analysis | Functional | - | 1. Isi interval = 3 | overdue_interval_days: 3 | Berhasil disimpan. Kirim notif tiap 3 hari setelah JT. | High |
| **NTS-007** | Ubah overdue interval — nilai 0 | Boundary Value Analysis | Functional | - | 1. Isi interval = 0 | overdue_interval_days: 0 | Form menolak (minValue: 1). | High |
| **NTS-008** | Ubah overdue interval — nilai >90 | Boundary Value Analysis | Functional | - | 1. Isi interval = 99 | overdue_interval_days: 99 | Form menolak (maxValue: 90). | Medium |
| **NTS-009** | Simpan — semua diaktifkan | Equivalence Partitioning | Functional | - | 1. Check semua toggle<br>2. Isi default values<br>3. Simpan | Semua enabled | Berhasil. | High |
| **NTS-010** | Simpan — konfirmasi tersimpan permanen | State Transition | Functional | Setelah simpan | 1. Refresh halaman | N/A | Nilai yang disimpan sebelumnya masih terisi (persisten di database). | High |
| **NTS-011** | RBAC akses halaman tanpa `view-notification-setting` | Security (dasar) | Security | Admin tanpa permission | 1. Paksa buka URL | N/A | 403 Forbidden. | High |

---

## 6. Fitur: In-App Notification (NTF)

**Halaman:** Icon bel di header Filament panel (muncul di semua halaman)
**Fitur:** Notifikasi in-app (bell icon), unread count badge, dropdown daftar notifikasi, mark as read, mark all as read

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **NTF-001** | Bell icon — tampil di header | Exploratory | UI/UX | Login sbg admin | 1. Perhatikan header panel | N/A | Icon bel (heroicon-o-bell) muncul di pojok kanan atas header. | High |
| **NTF-002** | Notifikasi — unread count badge | Equivalence Partitioning | Functional | Ada notifikasi belum dibaca | 1. Perhatikan icon bel | N/A | Badge merah dengan angka notifikasi belum dibaca tampil. | High |
| **NTF-003** | Notifikasi — klik bell, dropdown terbuka | Exploratory | UI/UX | Ada notifikasi | 1. Klik icon bel | N/A | Dropdown menampilkan daftar notifikasi (title, message, waktu). | High |
| **NTF-004** | Notifikasi — klik "Mark All as Read" | Equivalence Partitioning | Functional | Ada notif >1 belum dibaca | 1. Klik bell<br>2. Klik "Mark All as Read" | N/A | Badge unread hilang. Semua notif terbaca. | High |
| **NTF-005** | Notifikasi — polling otomatis | Exploratory | Functional | - | 1. Buka dashboard<br>2. Buka tab baru, lakukan aksi yg trigger notif (misal bayar tagihan)<br>3. Kembali ke tab dashboard | N/A | Setelah ~5 detik, notifikasi baru muncul di bell tanpa refresh manual. | Medium |
| **NTF-006** | Notifikasi — empty state | Equivalence Partitioning | UI/UX | Belum ada notifikasi | 1. Klik bell saat 0 notif | N/A | Dropdown menampilkan "Tidak ada notifikasi" atau sejenis. | Low |
| **NTF-007** | Notifikasi — klik notif individual | Exploratory | Functional | Ada notifikasi | 1. Buka dropdown<br>2. Klik salah satu notif | N/A | Notif ditandai baca. Jika ada link action, navigasi ke halaman terkait. | Medium |

---

## 7. Fitur: Portal Siswa Dashboard (PSD)

**Halaman:** Portal → Beranda (`/portal/beranda`)
**Fitur:** Dashboard siswa/wali: lihat ringkasan tagihan, pilih sibling (untuk wali), data dari API `/dashboard/siswa`

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **PSD-001** | Login siswa — lihat dashboard | Equivalence Partitioning | Functional | Login sbg siswa aktif, ada tagihan | 1. Login dgn NIS: `000001`<br>2. Buka `/portal/beranda` | NIS: 000001 | Dashboard menampilkan ringkasan tagihan siswa tsb. | High |
| **PSD-002** | Login wali — lihat dashboard + sibling dropdown | Equivalence Partitioning | Functional | Login sbg wali dgn >1 anak | 1. Login sbg wali<br>2. Buka beranda | N/A | Dashboard muncul + dropdown untuk memilih anak (sibling). | High |
| **PSD-003** | Wali — ganti sibling | Equivalence Partitioning | Functional | Login wali, dropdown ada | 1. Pilih sibling berbeda dari dropdown | N/A | Data dashboard berubah sesuai anak yang dipilih. | High |
| **PSD-004** | Dashboard siswa — loading state | Exploratory | UI/UX | Koneksi normal | 1. Buka halaman | N/A | Indikator loading muncul, lalu konten. | Medium |
| **PSD-005** | Dashboard siswa — RBAC tanpa `view-own-billing` | Security (dasar) | Security | Siswa tanpa permission | 1. Paksa buka `/portal/beranda` | N/A | 403 atau redirect. | High |

---

## Ringkasan

| Sub-Fitur | Jumlah TC | Teknik Utama | Prioritas Tinggi |
|-----------|----------|-------------|-----------------|
| Dashboard Admin (DBH) | 16 | EP, EG, Exp | 11 |
| Kas Harian (KSH) | 16 | EP, BVA, EG, Exp | 9 |
| Rekap Bulanan (RKB) | 10 | EP, EG, Exp | 6 |
| Notification Log (NTL) | 13 | EP, EG, ST, Exp | 8 |
| Notification Settings (NTS) | 11 | EP, BVA, EG, ST | 8 |
| In-App Notification (NTF) | 7 | EP, Exp | 4 |
| Portal Siswa Dashboard (PSD) | 5 | EP, EG | 4 |
| **Total** | **78** | - | **50** |

**Keterangan Teknik:**
- **EP** = Equivalence Partitioning
- **BVA** = Boundary Value Analysis
- **ST** = State Transition Testing
- **EG** = Error Guessing
- **Exp** = Exploratory Testing

**Aspek yang Dicakup:**
- Functional: 100% test case
- UI/UX: ~15% (chart rendering, empty state, badge, modal, loading, dropdown)
- Security (RBAC): ~15% (tombol/permission checks)
- Business Logic: ~15% (akurasi saldo, KPI, filter data)

---

*Dokumen siap untuk direview.*
