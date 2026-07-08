# Laporan Hasil Pengujian Blackbox — Modul 5: Manajemen Keuangan

> **Tanggal:** 8 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing — Manual)
> **Lingkungan Uji:** `http://127.0.0.1:8000` (Frontend-v2 Filament)
> **Akun:** `admin@handayani.test` / `admin123` (Superadmin)

---

## Hasil Eksekusi Test Case

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Priority | Actual Result | Status | Severity | Bug ID | Evidence |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **JTG-001** | Tambah jenis tagihan — sukses | Equivalence Partitioning | Functional | Login sebagai Superadmin | 1. Buka Data Master → Jenis Tagihan<br>2. Klik "Tambah"<br>3. Isi nama, jatuh tempo, jumlah | Nama: "SPP Juli 2026", Jatuh Tempo: 2026-07-15, Jumlah: 150000 | Notif "Berhasil Ditambahkan". Item muncul di tabel. | High | | | | | |
| **JTG-002** | Tambah jenis tagihan — jumlah 0 | Boundary Value Analysis | Functional | Berada di form Tambah | 1. Isi jumlah = 0 | Jumlah: 0 | Form menolak (minValue: 1) atau 422. | Medium | | | | | |
| **JTG-003** | Tambah jenis tagihan — nama kosong | Error Guessing | Functional | Berada di form Tambah | 1. Nama kosong | Nama: "" | Validasi client: "Nama Tagihan wajib diisi". | Medium | | | | | |
| **JTG-004** | Lihat daftar — filter periode | Equivalence Partitioning | Functional | Ada data lintas periode | 1. Ganti filter periode | N/A | Data berubah sesuai periode. | Medium | | | | | |
| **JTG-005** | Ubah jenis tagihan — sukses | Equivalence Partitioning | Functional | Jenis tagihan tersedia | 1. Klik edit | Nama: "SPP Agustus 2026", Jumlah: 200000 | Notif "Berhasil Diubah". | High | | | | | |
| **JTG-006** | Ubah — batal | Exploratory Testing | UI/UX | Modal edit terbuka | 1. Klik "Batal" | N/A | Modal tertutup, data tdk berubah. | Low | | | | | |
| **JTG-007** | Hapus jenis tagihan — sukses | Equivalence Partitioning | Functional | Jenis tagihan tidak terpakai | 1. Klik hapus, konfirmasi | N/A | Notif "Berhasil Dihapus". | High | | | | | |
| **JTG-008** | Hapus — sudah dipakai tagihan | Error Guessing | Functional | Jenis tagihan digunakan | 1. Coba hapus | N/A | 409/422: "masih digunakan". | High | | | | | |
| **JTG-009** | Bulk delete — sukses | Equivalence Partitioning | Functional | 1. Centang 2 item, Hapus Terpilih | N/A | Notif "2 berhasil dihapus". | Medium | | | | | |
| **JTG-010** | RBAC: Tambah tanpa `create-jenis-tagihan` | Security | UI | 1. Buka halaman | N/A | Tombol "Tambah" tidak tampil. | High | | | | | |
| **JTG-011** | RBAC: Edit tanpa `update-jenis-tagihan` | Security | UI | 1. Buka halaman | N/A | Ikon edit tidak tampil. | High | | | | | |
| **JTG-012** | RBAC: Hapus tanpa `delete-jenis-tagihan` | Security | UI | 1. Buka halaman | N/A | Ikon hapus tidak tampil. | High | | | | | |
| **TGH-001** | Tampilkan tagihan per jenjang | Equivalence Partitioning | Functional | 1. Buka Transaksi → Tagihan - KB | N/A | Card siswa muncul dgn daftar tagihan. | High | | | | | |
| **TGH-002** | Filter — status "Belum Dibayar" | Equivalence Partitioning | Functional | 1. Pilih filter status | Status: Belum Dibayar | Hanya siswa dgn tagihan Belum Dibayar. | Medium | | | | | |
| **TGH-003** | Filter — kelas | Equivalence Partitioning | Functional | 1. Pilih filter kelas | Kelas: KB-A | Hanya siswa di kelas tsb. | Medium | | | | | |
| **TGH-004** | Cari siswa | Equivalence Partitioning | Functional | 1. Ketik nama di search | Nama: "Ahmad" | Tabel terfilter. | Medium | | | | | |
| **TGH-005** | Tambah tagihan batch — sukses | Equivalence Partitioning | Functional | 1. Klik "Tambah Tagihan", pilih Periode/Jenis/Kelas/Kategori | N/A | Notif "Berhasil". Semua siswa di kelas dapet tagihan. | High | | | | | |
| **TGH-006** | Tambah tagihan — tanpa periode aktif | Error Guessing | Functional | 1. Nonaktifkan periode, tambah tagihan | N/A | 422: "Periode aktif harus diatur". | High | | | | | |
| **TGH-007** | Hapus tagihan — sukses | Equivalence Partitioning | Functional | 1. Buka detail siswa, klik hapus | Kode: TAG-001 | Notif "Berhasil Dihapus". | High | | | | | |
| **TGH-008** | Hapus tagihan — sudah dibayar | Error Guessing | Functional | 1. Coba hapus tagihan lunas | N/A | 409: "sudah dibayar dan tidak dapat dihapus". | High | | | | | |
| **TGH-018** | Cek email tagihan baru via Mailpit | Functional Integration | Functional | 1. Buka Mailpit http://127.0.0.1:8025<br>2. Cari email subjek "Tagihan Baru" | N/A | Mailpit tampilkan email baru berisi info tagihan. | High | | | | | |
| **TGH-009** | Bayar batch lunas — offline | Equivalence Partitioning | Functional | 1. Checklist, Bayar, metode offline | Metode: offline | Notif "Berhasil". Status Lunas. | High | | | | | |
| **TGH-010** | Bayar batch — via Midtrans | Equivalence Partitioning | Functional | 1. Pilih tagihan, metode online_midtrans | Metode: online_midtrans | Redirect ke Midtrans Snap. | Medium | | | | | |
| **TGH-011** | Bayar cicilan — jumlah valid | Equivalence Partitioning | Functional | 1. Klik "Bayar Cicilan", isi jumlah | Jumlah: 50000 | Notif "Pembayaran Cicilan Berhasil". | High | | | | | |
| **TGH-012** | Bayar cicilan — melebihi sisa | Boundary Value Analysis | Functional | 1. Isi jumlah > sisa | Jumlah: 150000 (sisa: 100000) | Error: "melebihi sisa/jumlah biaya tagihan". | High | | | | | |
| **TGH-013** | Bayar tagihan — sudah lunas | Error Guessing | Functional | 1. Coba bayar tagihan Lunas | N/A | Error: "tagihan sudah dibayar lunas". | High | | | | | |
| **TGH-014** | Export PDF tagihan | Equivalence Partitioning | Functional | 1. Klik "Export PDF", pilih filter | Status: Belum Dibayar | File PDF terdownload. | High | | | | | |
| **TGH-015** | Export Excel tagihan | Equivalence Partitioning | Functional | 1. Klik Export, format xlsx | Format: xlsx | File terdownload. | High | | | | | |
| **TGH-016** | RBAC: Tambah Tagihan tanpa `create-tagihan` | Security | UI | 1. Buka halaman | N/A | Tombol tidak tampil. | High | | | | | |
| **TGH-017** | RBAC: akses tanpa `view-tagihan` | Security | Security | 1. Paksa buka URL | N/A | 403 Forbidden. | High | | | | | |
| **BYR-001** | Tampilkan pembayaran — grouped | Equivalence Partitioning | Functional | 1. Buka Transaksi → Pembayaran | N/A | Card per siswa muncul. | High | | | | | |
| **BYR-002** | Filter — jenjang | Equivalence Partitioning | Functional | 1. Pilih filter jenjang | Jenjang: TK | Hanya siswa TK. | Medium | | | | | |
| **BYR-003** | Filter — metode | Equivalence Partitioning | Functional | 1. Pilih filter offline | Metode: offline | Hanya offline. | Medium | | | | | |
| **BYR-004** | Filter — sort | Equivalence Partitioning | Functional | 1. Ubah sort | Sort: oldest | Urutan berubah. | Low | | | | | |
| **BYR-005** | Cari — nama siswa | Equivalence Partitioning | Functional | 1. Ketik nama | Nama: "Ahmad" | Hasil terfilter. | Medium | | | | | |
| **BYR-006** | Hapus pembayaran — offline sukses | Equivalence Partitioning | Functional | 1. Klik hapus, konfirmasi | N/A | Notif "Berhasil Dihapus". | High | | | | | |
| **BYR-007** | Hapus — online midtrans | Error Guessing | Security | 1. Coba hapus online_midtrans | N/A | Gagal tanpa kedua permission. | High | | | | | |
| **BYR-008** | Download kwitansi PDF | Equivalence Partitioning | Functional | 1. Klik ikon kwitansi | N/A | PDF terdownload. | High | | | | | |
| **BYR-012** | Cek email kwitansi via Mailpit | Functional Integration | Functional | 1. Buka Mailpit http://127.0.0.1:8025<br>2. Cari email subjek "Kwitansi" | N/A | Mailpit tampilkan email berisi info pembayaran. | High | | | | | |
| **BYR-009** | Export Excel | Equivalence Partitioning | Functional | 1. Klik Export, format xlsx | Format: xlsx | File terdownload. | High | | | | | |
| **BYR-010** | RBAC: Hapus tanpa `delete-pembayaran` | Security | UI | 1. Buka halaman | N/A | Ikon hapus tidak tampil. | High | | | | | |
| **BYR-011** | RBAC: akses tanpa `view-pembayaran` | Security | Security | 1. Paksa buka URL | N/A | 403. | High | | | | | |
| **PRQ-001** | Buat request — isi valid | Equivalence Partitioning | Functional | 1. Klik "Buat Request", isi uraian, jumlah, tgl | Uraian: "Beli ATK", Jumlah: 500000 | Notif "Request berhasil dibuat". Status "draft". | High | | | | | |
| **PRQ-002** | Buat request — jumlah 0 | Boundary Value Analysis | Functional | 1. Isi jumlah 0 | Jumlah: 0 | Form menolak (minValue:1). | Medium | | | | | |
| **PRQ-003** | Buat request — uraian kosong | Error Guessing | Functional | 1. Uraian kosong | Uraian: "" | Validasi wajib diisi. | Medium | | | | | |
| **PRQ-004** | Submit — dari status draft | State Transition Testing | Functional | 1. Klik "Submit" | N/A | Notif sukses. Status "submitted". | High | | | | | |
| **PRQ-005** | Submit — dari rejected (re-submit) | State Transition Testing | Functional | 1. Klik "Submit" item rejected | N/A | Status "submitted" lagi. | High | | | | | |
| **PRQ-006** | Submit — bukan milik sendiri | Security | Security | 1. Cek item org lain | N/A | Tombol Submit tidak muncul. | High | | | | | |
| **PRQ-007** | Approve — dari submitted | State Transition Testing | Functional | 1. Klik "Approve" | Catatan: "Setuju" | Notif "Request disetujui". Status "approved". | High | | | | | |
| **PRQ-008** | Approve tanpa permission | Security | UI | 1. Buka item submitted | N/A | Tombol Approve tidak tampil. | High | | | | | |
| **PRQ-009** | Reject — dengan alasan | State Transition Testing | Functional | 1. Klik "Reject", isi alasan | Alasan: "Dana belum tersedia" | Notif "Request ditolak". Status "rejected". | High | | | | | |
| **PRQ-010** | Reject — alasan kosong | Error Guessing | Functional | 1. Klik Reject | Alasan: "" | Form minta alasan (required). | High | | | | | |
| **PRQ-011** | Lihat alasan ditolak | Equivalence Partitioning | UI/UX | 1. Klik "Alasan Ditolak" | N/A | Modal tampilkan alasan. | High | | | | | |
| **PRQ-012** | Lihat catatan approval | Equivalence Partitioning | UI/UX | 1. Klik "Catatan Approval" | N/A | Modal tampilkan catatan. | Medium | | | | | |
| **PRQ-013** | Disburse (cairkan) — sukses | State Transition Testing | Functional | 1. Klik "Cairkan" | N/A | Notif "Pencairan berhasil". Status "disbursed". | High | | | | | |
| **PRQ-014** | Disburse tanpa permission | Security | UI | 1. Buka item approved | N/A | Tombol Cairkan tidak tampil. | High | | | | | |
| **PRQ-015** | Disburse — bukan milik sendiri | Security | Security | 1. Cek item approved org lain | N/A | Tombol tidak muncul. | High | | | | | |
| **PRQ-016** | Info pencairan — modal | Equivalence Partitioning | UI/UX | 1. Klik "Info Pencairan" | N/A | Modal muncul. | Low | | | | | |
| **PRQ-017** | Ubah request draft — sukses | State Transition Testing | Functional | 1. Edit item draft | N/A | Data berubah. Status draft. | High | | | | | |
| **PRQ-018** | Hapus request draft — sukses | State Transition Testing | Functional | 1. Hapus item draft | N/A | Request terhapus. | High | | | | | |
| **PRQ-019** | Hapus — bukan status draft | State Transition Testing | Security | 1. Hapus submitted/approved via API | N/A | Backend tolak 422. | High | | | | | |
| **PRQ-020** | Filter status request | Equivalence Partitioning | Functional | 1. Pilih filter "approved" | Status: approved | Hanya approved tampil. | Medium | | | | | |
| **PRQ-021** | RBAC: Buat Request tanpa permission | Security | UI | 1. Buka halaman | N/A | Tombol tidak tampil. | High | | | | | |
| **PNG-001** | Lihat pengeluaran per tanggal | Equivalence Partitioning | Functional | 1. Buka Kas Harian, klik "Detail" | N/A | Modal tabel: Nama, Pengaju, Penyetuju, Jumlah. | High | | | | | |
| **PNG-002** | Lihat pengeluaran per bulan | Equivalence Partitioning | Functional | 1. Buka Rekap Bulanan, klik "Detail" | N/A | Modal tabel pengeluaran. | Medium | | | | | |
| **PNG-003** | Daftar — label status benar | Exploratory Testing | UI/UX | 1. Buka modal detail | N/A | Kolom terisi. | Low | | | | | |
| **MDT-001** | Tampilkan transaksi Midtrans | Equivalence Partitioning | Functional | 1. Buka Laporan → Transaksi Midtrans | N/A | Tabel: Order ID, Kode Tagihan, Nama Siswa, dll. | High | | | | | |
| **MDT-002** | Filter — status settlement | Equivalence Partitioning | Functional | 1. Pilih filter "Settlement" | Status: settlement | Hanya settlement tampil. | Medium | | | | | |
| **MDT-003** | Filter — range tanggal | Equivalence Partitioning | Functional | 1. Isi Dari & Sampai Tanggal | Dari: 2026-07-01, Sampai: 2026-07-08 | Hanya transaksi rentang tsb. | Medium | | | | | |
| **MDT-004** | Search — order ID | Equivalence Partitioning | Functional | 1. Ketik order ID | Order ID: "MID-2026..." | Tabel terfilter. | Medium | | | | | |
| **MDT-005** | Klik baris — detail transaksi | Exploratory Testing | UI/UX | 1. Klik baris | N/A | Redirect ke `/transaksi-midtrans/{id}`. | High | | | | | |
| **MDT-006** | Polling 5s — auto-refresh | Exploratory Testing | UI/UX | 1. Buka halaman, tunggu 5 detik | N/A | Tabel auto-refresh. | Low | | | | | |
| **MDT-007** | RBAC: akses tanpa permission | Security | Security | 1. Paksa buka URL | N/A | 403 / menu tidak muncul. | Medium | | | | | |
| **PTS-001** | Portal — lihat tagihan sendiri | Equivalence Partitioning | Functional | 1. Login siswa, buka Tagihan | NIS: 000001 | Daftar tagihan siswa muncul. | High | | | | | |
| **PTS-002** | Portal — ganti sibling | Equivalence Partitioning | Functional | 1. Pilih sibling dari dropdown | N/A | Tagihan sibling muncul. | High | | | | | |
| **PTS-003** | Portal — bayar online Midtrans | Equivalence Partitioning | Functional | 1. Pilih tagihan, "Bayar Online" | Kode: TAG-xxx | Redirect ke Midtrans Snap. | High | | | | | |
| **PTS-004** | Portal — batch payment online | Equivalence Partitioning | Functional | 1. Checklist beberapa, bayar batch | N/A | Midtrans Snap total = semua tagihan. | High | | | | | |
| **PTS-005** | Portal — riwayat pembayaran | Equivalence Partitioning | Functional | 1. Buka "Riwayat Pembayaran" | N/A | Riwayat muncul + pending transaksi. | Medium | | | | | |
| **RMD-001** | Jalankan reminder — cek email via Mailpit | Equivalence Partitioning | Functional | 1. `php artisan notifications:send-reminders`<br>2. Buka Mailpit | N/A | Mailpit tampilkan email reminder subjek "Pengingat Tagihan". | High | | | | | |
| **RMD-002** | Jalankan overdue — cek email via Mailpit | Equivalence Partitioning | Functional | 1. `php artisan notifications:send-reminders`<br>2. Buka Mailpit | N/A | Mailpit tampilkan email overdue subjek "Tagihan Jatuh Tempo". | High | | | | | |

---

## Ringkasan Pengujian

- **Total test case:** 80
- **Pass:** 0 | **Fail:** 0 | **Blocked:** 0 | **Untested:** 80
- **Tanggal pengujian:** 8 Juli 2026
- **Penguji:** (Manual — diisi setelah eksekusi)

### Cakupan per Sub-Fitur

| Sub-Fitur | Jumlah TC | Prioritas Tinggi |
|-----------|----------|-----------------|
| Jenis Tagihan (JTG) | 12 | 6 |
| Tagihan (TGH) | 18 | 10 |
| Pembayaran (BYR) | 12 | 7 |
| Pengeluaran Request (PRQ) | 21 | 12 |
| Pengeluaran (PNG) | 3 | 2 |
| Midtrans (MDT) | 7 | 3 |
| Portal Siswa (PTS) | 5 | 4 |
| Scheduled Reminder (RMD) | 2 | 2 |
| **Total** | **80** | **46** |

### Daftar Bug Ditemukan

| Bug ID | Terkait TC | Deskripsi Singkat | Severity | Langkah Reproduksi | Evidence |
|---|---|---|---|---|---|
| *(diisi manual)* | | | | | |

### Catatan Tambahan

*(diisi manual setelah pengujian)*

---

*Dokumen hasil pengujian — kolom Actual Result, Status, Severity, Bug ID, Evidence siap diisi manual.*
