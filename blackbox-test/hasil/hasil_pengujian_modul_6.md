# Laporan Hasil Pengujian Blackbox — Modul 6: Laporan, Dashboard & Notifikasi

> **Tanggal:** 8 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing — Manual)
> **Lingkungan Uji:** `http://127.0.0.1:8000` (Frontend-v2 Filament)
> **Akun:** `admin@handayani.test` / `admin123` (Superadmin)

---

## Hasil Eksekusi Test Case

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Priority | Actual Result | Status | Severity | Bug ID | Evidence |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **DBH-001** | Dashboard — semua widget termuat | Exploratory Testing | Functional | Login sebagai Superadmin | 1. Buka `/dashboard-page` | N/A | Semua widget muncul tanpa error. | High | | | | | |
| **DBH-002** | Dashboard — filter periode dropdown | Equivalence Partitioning | Functional | Ada data lintas periode | 1. Ganti dropdown periode | N/A | Widget refresh sesuai periode. | High | | | | | |
| **DBH-003** | Dashboard — KPI all-time akurat | Equivalence Partitioning | Functional | Ada data >1 periode | 1. Catat 3 stat all-time | N/A | Angka sesuai akumulasi semua periode. | High | | | | | |
| **DBH-004** | Dashboard — KPI periode akurat | Equivalence Partitioning | Functional | Ada data di periode aktif | 1. Catat 6 KPI periode | N/A | Total Tagihan = Terbayar + Tunggakan. | High | | | | | |
| **DBH-005** | Dashboard — Kas Summary tampil | Exploratory Testing | Functional | Ada data pemasukan & pengeluaran | 1. Scroll ke widget | N/A | Pemasukan, pengeluaran, saldo tampil. | High | | | | | |
| **DBH-006** | Dashboard — Chart Pembayaran Bulanan | Exploratory Testing | Functional | Ada data pembayaran | 1. Perhatikan chart bar | N/A | Chart bar dengan data per bulan. | Medium | | | | | |
| **DBH-007** | Dashboard — Chart Kas Bulanan | Exploratory Testing | Functional | Ada data kas | 1. Perhatikan chart | N/A | 2 series: pemasukan & pengeluaran. | Medium | | | | | |
| **DBH-008** | Dashboard — Chart Tunggakan Jenjang | Exploratory Testing | Functional | Ada data tunggakan per jenjang | 1. Perhatikan chart | N/A | Bar per jenjang (KB, TK, MI). | Medium | | | | | |
| **DBH-009** | Dashboard — Chart Status Tagihan | Exploratory Testing | Functional | 1. Perhatikan chart | N/A | Pie/donut/bar proporsi status. | Medium | | | | | |
| **DBH-010** | Dashboard — Tabel Top 10 Tunggakan | Exploratory Testing | Functional | 1. Scroll ke tabel | N/A | 10 siswa tunggakan terbesar. | High | | | | | |
| **DBH-011** | Dashboard — Tabel Jatuh Tempo 7 Hari | Exploratory Testing | Functional | 1. Scroll ke tabel | N/A | Tagihan JT 7 hari ke depan. | High | | | | | |
| **DBH-012** | Dashboard — Tabel Pembayaran Terbaru | Exploratory Testing | Functional | 1. Scroll ke tabel | N/A | 5-10 pembayaran terbaru. | Medium | | | | | |
| **DBH-013** | Dashboard — tombol Refresh | Exploratory Testing | UI/UX | 1. Klik "Refresh" | N/A | Reload data tanpa error. | Low | | | | | |
| **DBH-014** | Dashboard — loading state ganti periode | Exploratory Testing | UI/UX | 1. Ganti periode | N/A | Indikator loading muncul. | Medium | | | | | |
| **DBH-015** | Dashboard — RBAC tanpa `view-dashboard` | Security | Security | 1. Paksa buka URL | N/A | 403 Forbidden. | High | | | | | |
| **DBH-016** | Dashboard — akses oleh siswa | Security | Security | 1. Login siswa, buka `/dashboard-page` | N/A | Redirect/403. | High | | | | | |
| **KSH-001** | Tampilkan Kas Harian — default | Equivalence Partitioning | Functional | 1. Buka Laporan → Kas Harian | N/A | Tabel: Tanggal, Total Masuk, Keluar, Saldo. DESC. | High | | | | | |
| **KSH-002** | Filter — ganti bulan & tahun | Equivalence Partitioning | Functional | 1. Isi Bulan=1, Tahun=2026 | Bulan: 1, Tahun: 2026 | Data Jan 2026. | High | | | | | |
| **KSH-003** | Filter — bulan tidak valid | Boundary Value Analysis | Functional | 1. Isi Bulan=13 | Bulan: 13 | Form tolak/400. | Medium | | | | | |
| **KSH-004** | Sort by kolom | Equivalence Partitioning | UI/UX | 1. Klik header "Tanggal" 2x | N/A | Urutan berubah. | Low | | | | | |
| **KSH-005** | Modal detail tanggal | Equivalence Partitioning | Functional | 1. Klik "Detail" | N/A | Modal: tabel Pemasukan & Pengeluaran. | High | | | | | |
| **KSH-006** | Modal — data pemasukan | Exploratory Testing | Functional | 1. Buka modal | N/A | Tabel: NIS, Nama, Nama Tagihan, Jumlah. | High | | | | | |
| **KSH-007** | Modal — data pengeluaran | Exploratory Testing | Functional | 1. Buka modal | N/A | Tabel: Nama, Pengaju, Penyetuju, Jumlah. | High | | | | | |
| **KSH-008** | Modal — tutup | Exploratory Testing | UI/UX | 1. Klik "Tutup" | N/A | Modal tertutup. | Low | | | | | |
| **KSH-009** | Pagination | Equivalence Partitioning | UI/UX | 1. Klik halaman 2 | N/A | Navigasi OK. | Low | | | | | |
| **KSH-010** | Export PDF | Equivalence Partitioning | Functional | 1. Klik "Export PDF" | Bulan/Tahun: now | File PDF terdownload. | High | | | | | |
| **KSH-011** | Export PDF bulan kosong | Error Guessing | Functional | 1. Export bulan tanpa data | Bulan: 1, Tahun: 2020 | PDF tetap terdownload/404. | Medium | | | | | |
| **KSH-012** | Export Excel (.xlsx) | Equivalence Partitioning | Functional | 1. Export xlsx | Format: xlsx | File terdownload. | High | | | | | |
| **KSH-013** | Export CSV | Equivalence Partitioning | Functional | 1. Export CSV | Format: csv | File terdownload. | High | | | | | |
| **KSH-014** | Export tanpa `export-laporan` | Security | Security | 1. Buka halaman | N/A | Tombol Export tidak tampil. | High | | | | | |
| **KSH-015** | RBAC tanpa `view-kas-harian` | Security | Security | 1. Paksa buka URL | N/A | 403. | High | | | | | |
| **KSH-016** | Empty state | Equivalence Partitioning | UI/UX | 1. Filter bulan tanpa data | N/A | "Tidak Ada Kas Harian". | Medium | | | | | |
| **RKB-001** | Tampilkan Rekap Bulanan — default | Equivalence Partitioning | Functional | 1. Buka Laporan → Rekap Bulanan | N/A | Tabel per bulan: Total Masuk, Keluar, Saldo. | High | | | | | |
| **RKB-002** | Filter — ganti tahun | Equivalence Partitioning | Functional | 1. Isi Tahun=2025 | Tahun: 2025 | Data 2025. | High | | | | | |
| **RKB-003** | Sort by kolom | Equivalence Partitioning | UI/UX | 1. Klik header | N/A | Urutan berubah. | Low | | | | | |
| **RKB-004** | Modal detail bulan | Equivalence Partitioning | Functional | 1. Klik "Detail" | N/A | Modal pemasukan & pengeluaran. | High | | | | | |
| **RKB-005** | Modal — data akurat | Exploratory Testing | Functional | 1. Catat angka baris, bandingkan modal | N/A | Total Masuk modal = Total Masuk baris. | High | | | | | |
| **RKB-006** | Export PDF | Equivalence Partitioning | Functional | 1. Export PDF | Tahun: now | File terdownload. | High | | | | | |
| **RKB-007** | Export Excel | Equivalence Partitioning | Functional | 1. Export xlsx | Format: xlsx, Tahun: now | File terdownload. | High | | | | | |
| **RKB-008** | Export CSV | Equivalence Partitioning | Functional | 1. Export CSV | Format: csv | File terdownload. | High | | | | | |
| **RKB-009** | RBAC tanpa `view-rekap-bulanan` | Security | Security | 1. Paksa buka URL | N/A | 403. | High | | | | | |
| **RKB-010** | Empty state tahun tanpa data | Equivalence Partitioning | UI/UX | 1. Filter tahun tanpa transaksi | Tahun: 2020 | "Tidak Ada Rekap Bulanan". | Medium | | | | | |
| **NTL-001** | Tampilkan log notifikasi | Equivalence Partitioning | Functional | 1. Buka Laporan → Log Notifikasi | N/A | Tabel: Waktu, Tipe, Kode, Email, Status, Alasan. | High | | | | | |
| **NTL-002** | Filter tipe notifikasi | Equivalence Partitioning | Functional | 1. Pilih Tipe "Kwitansi" | Tipe: kwitansi | Hanya log kwitansi. | High | | | | | |
| **NTL-003** | Filter status | Equivalence Partitioning | Functional | 1. Pilih Status "Terkirim" | Status: sent | Hanya sent. | Medium | | | | | |
| **NTL-004** | Search by email | Equivalence Partitioning | Functional | 1. Ketik email | Email: "budi@test.com" | Tabel terfilter. | Medium | | | | | |
| **NTL-005** | Search by kode tagihan | Equivalence Partitioning | Functional | 1. Ketik kode | Kode: "TAG-" | Tabel terfilter. | Medium | | | | | |
| **NTL-006** | Badge tipe benar | Exploratory Testing | UI/UX | 1. Perhatikan badge | N/A | Warna & label sesuai mapping. | Medium | | | | | |
| **NTL-007** | Badge status benar | Exploratory Testing | UI/UX | 1. Perhatikan badge | N/A | Warna & label sesuai mapping. | Medium | | | | | |
| **NTL-008** | Alasan pada failed/skipped | Exploratory Testing | UI/UX | 1. Perhatikan kolom Alasan | N/A | Alasan jelas & readable. | High | | | | | |
| **NTL-009** | Bulk retry notifikasi gagal | State Transition Testing | Functional | 1. Centang log gagal, "Kirim Ulang" | N/A | Notif sukses. | High | | | | | |
| **NTL-010** | Bulk retry tanpa centang | Error Guessing | UI/UX | 1. Klik Kirim Ulang tanpa centang | N/A | Warning "Tidak ada log". | Medium | | | | | |
| **NTL-011** | Pagination | Equivalence Partitioning | UI/UX | 1. Klik halaman 2 | N/A | Pagination OK. | Low | | | | | |
| **NTL-012** | RBAC tanpa `view-notification-logs` | Security | Security | 1. Paksa buka URL | N/A | 403. | High | | | | | |
| **NTL-013** | Sort by kolom | Equivalence Partitioning | UI/UX | 1. Klik header Waktu | N/A | Urutan berubah. | Low | | | | | |
| **NTS-001** | Lihat pengaturan — form termuat | Equivalence Partitioning | Functional | 1. Buka Pengaturan → Notifikasi | N/A | 4 section collapsible + nilai default. | High | | | | | |
| **NTS-002** | Toggle Tagihan Baru — nonaktifkan | Equivalence Partitioning | Functional | 1. Uncheck, Simpan | tagihan_baru_enabled: false | Notif sukses. | High | | | | | |
| **NTS-003** | Toggle Kwitansi — nonaktifkan | Equivalence Partitioning | Functional | 1. Uncheck, Simpan | kwitansi_enabled: false | Notif sukses. | High | | | | | |
| **NTS-004** | Reminder days — input valid | Equivalence Partitioning | Functional | 1. Isi `7, 3, 1`, Simpan | reminder_days: [7,3,1] | Berhasil. | High | | | | | |
| **NTS-005** | Reminder days — kosong | Error Guessing | Functional | 1. Kosongkan, Simpan | [] | Berhasil. | Medium | | | | | |
| **NTS-006** | Overdue interval — valid | Boundary Value Analysis | Functional | 1. Isi 3, Simpan | overdue_interval: 3 | Berhasil. | High | | | | | |
| **NTS-007** | Overdue interval — nilai 0 | Boundary Value Analysis | Functional | 1. Isi 0 | 0 | Form tolak (minValue:1). | High | | | | | |
| **NTS-008** | Overdue interval — >90 | Boundary Value Analysis | Functional | 1. Isi 99 | 99 | Form tolak (maxValue:90). | Medium | | | | | |
| **NTS-009** | Simpan — semua diaktifkan | Equivalence Partitioning | Functional | 1. Check semua, Simpan | Semua enabled | Berhasil. | High | | | | | |
| **NTS-010** | Konfirmasi persistensi | State Transition Testing | Functional | 1. Simpan, refresh | N/A | Nilai tersimpan permanen. | High | | | | | |
| **NTS-011** | RBAC tanpa `view-notification-setting` | Security | Security | 1. Paksa buka URL | N/A | 403. | High | | | | | |
| **NTF-001** | Bell icon — tampil di header | Exploratory Testing | UI/UX | 1. Perhatikan header | N/A | Icon bel (heroicon) muncul. | High | | | | | |
| **NTF-002** | Unread count badge | Equivalence Partitioning | Functional | 1. Perhatikan icon bel | N/A | Badge merah angka unread. | High | | | | | |
| **NTF-003** | Klik bell — dropdown terbuka | Exploratory Testing | UI/UX | 1. Klik icon bel | N/A | Dropdown daftar notifikasi. | High | | | | | |
| **NTF-004** | "Mark All as Read" | Equivalence Partitioning | Functional | 1. Klik bel, "Mark All as Read" | N/A | Badge hilang. | High | | | | | |
| **NTF-005** | Polling otomatis | Exploratory Testing | Functional | 1. Buka dashboard, trigger notif di tab lain | N/A | Notif baru muncul ~5 detik. | Medium | | | | | |
| **NTF-006** | Empty state | Equivalence Partitioning | UI/UX | 1. Klik bell saat 0 notif | N/A | "Tidak ada notifikasi". | Low | | | | | |
| **NTF-007** | Klik notif individual | Exploratory Testing | Functional | 1. Klik notif di dropdown | N/A | Notif terbaca. | Medium | | | | | |
| **PSD-001** | Login siswa — lihat dashboard | Equivalence Partitioning | Functional | 1. Login NIS 000001, buka Beranda | NIS: 000001 | Ringkasan tagihan. | High | | | | | |
| **PSD-002** | Login wali — sibling dropdown | Equivalence Partitioning | Functional | 1. Login wali, buka Beranda | N/A | Dropdown sibling + data. | High | | | | | |
| **PSD-003** | Wali — ganti sibling | Equivalence Partitioning | Functional | 1. Pilih sibling lain | N/A | Data berubah. | High | | | | | |
| **PSD-004** | Loading state | Exploratory Testing | UI/UX | 1. Buka halaman | N/A | Indikator loading. | Medium | | | | | |
| **PSD-005** | RBAC tanpa `view-own-billing` | Security | Security | 1. Paksa buka `/portal/beranda` | N/A | 403 / redirect. | High | | | | | |

---

## Ringkasan Pengujian

- **Total test case:** 78
- **Pass:** 0 | **Fail:** 0 | **Blocked:** 0 | **Untested:** 78
- **Tanggal pengujian:** 8 Juli 2026
- **Penguji:** (Manual — diisi setelah eksekusi)

### Cakupan per Sub-Fitur

| Sub-Fitur | Jumlah TC | Prioritas Tinggi |
|-----------|----------|-----------------|
| Dashboard Admin (DBH) | 16 | 11 |
| Kas Harian (KSH) | 16 | 9 |
| Rekap Bulanan (RKB) | 10 | 6 |
| Notification Log (NTL) | 13 | 8 |
| Notification Settings (NTS) | 11 | 8 |
| In-App Notification (NTF) | 7 | 4 |
| Portal Siswa Dashboard (PSD) | 5 | 4 |
| **Total** | **78** | **50** |

### Daftar Bug Ditemukan

| Bug ID | Terkait TC | Deskripsi Singkat | Severity | Langkah Reproduksi | Evidence |
|---|---|---|---|---|---|
| *(diisi manual)* | | | | | |

### Catatan Tambahan

*(diisi manual setelah pengujian)*

---

*Dokumen hasil pengujian — kolom Actual Result, Status, Severity, Bug ID, Evidence siap diisi manual.*
