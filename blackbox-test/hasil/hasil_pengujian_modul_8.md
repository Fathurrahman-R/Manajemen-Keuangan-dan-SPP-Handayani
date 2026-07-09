# Laporan Hasil Pengujian Blackbox — Modul 8: Portal Siswa & Wali

> **Tanggal:** 8 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing — Manual)
> **Lingkungan Uji:** `http://127.0.0.1:8000/portal` (Frontend-v2 Filament Portal)
> **Akun:** Siswa NIS `000001` / default password atau wali dari siswa tersebut

---

## Hasil Eksekusi Test Case

### 1. Portal Beranda

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Priority | Actual Result | Status | Severity | Bug ID | Evidence |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **BRD-001** | Portal beranda termuat | Equivalence Partitioning | Functional | Login sebagai siswa dengan data tagihan | 1. Buka `/portal/beranda` | N/A | Halaman beranda tampil: filter periode, stats widget, tabel tagihan, tabel pembayaran terbaru. | High | | | | | |
| **BRD-002** | Filter periode — Semua data | Equivalence Partitioning | Functional | Ada data >1 periode | 1. Pilih "Semua Periode" | Periode: Semua | Stats akumulasi semua periode. | High | | | | | |
| **BRD-003** | Filter periode — spesifik | Equivalence Partitioning | Functional | Ada data di 2 periode | 1. Pilih periode tertentu | Periode: 2025/2026 | Stats & data hanya untuk periode tsb. | High | | | | | |
| **BRD-004** | Stats widget — angka akurat | Equivalence Partitioning | Functional | Ada tagihan & pembayaran | 1. Buka halaman, cocokkan angka | N/A | Total Tagihan = ∑ tagihan. Total Dibayar = ∑ pembayaran. Sisa = selisih. | High | | | | | |
| **BRD-005** | Tabel tagihan — data muncul | Equivalence Partitioning | UI/UX | Ada tagihan | 1. Scroll ke "Daftar Tagihan" | N/A | Tabel: Jenis, Jumlah, Terbayar, Sisa, Status. | High | | | | | |
| **BRD-006** | Tabel tagihan — polling 5s | Exploratory | UI/UX | Ada tagihan | 1. Buka, tunggu 10-15s | N/A | Data refresh otomatis. | Medium | | | | | |
| **BRD-007** | Tabel pembayaran terbaru | Equivalence Partitioning | UI/UX | Ada transaksi | 1. Scroll ke "Pembayaran Terbaru" | N/A | Tabel: kode, tanggal, jumlah, metode, status. | High | | | | | |
| **BRD-008** | RBAC tanpa permission | Security (dasar) | Security | User tanpa `view-own-billing` | 1. Paksa buka `/portal/beranda` | N/A | 403 Forbidden. | High | | | | | |
| **BRD-009** | Loading state | Exploratory | UI/UX | Koneksi lambat | 1. Buka halaman | N/A | Loading indicator saat fetch. | Medium | | | | | |
| **BRD-010** | Empty state tagihan | Equivalence Partitioning | UI/UX | Siswa tanpa tagihan | 1. Buka beranda | N/A | "Belum Ada Tagihan". | Medium | | | | | |
| **BRD-011** | Empty state pembayaran | Equivalence Partitioning | UI/UX | Siswa tanpa pembayaran | 1. Buka beranda | N/A | "Belum Ada Pembayaran". | Medium | | | | | |
| **BRD-012** | Ganti filter — stats berubah | State Transition | Functional | Data di >1 periode | 1. Pilih periode A → periode B | N/A | Stats berubah sesuai periode. | High | | | | | |
| **BRD-013** | Wali 1 anak — dropdown hilang | Error Guessing | UI/UX | Login wali 1 anak | 1. Buka beranda | N/A | Dropdown anak tidak muncul. | Low | | | | | |
| **BRD-014** | Loading overlay ganti filter | Exploratory | UI/UX | Data besar | 1. Ganti filter | N/A | Overlay opacity pada div data. | Medium | | | | | |
| **BRD-015** | Akses sebagai Admin | Security (dasar) | Security | Login admin | 1. Buka `/portal/beranda` | N/A | 403 Forbidden. | High | | | | | |
| **BRD-016** | Stats data 0/null | Equivalence Partitioning | Functional | Siswa baru tanpa data | 1. Buka beranda | N/A | Stats 0 / "-", bukan error. | Medium | | | | | |
| **BRD-017** | Dropdown periode — daftar valid | Equivalence Partitioning | UI/UX | Ada >1 periode | 1. Buka dropdown | N/A | "Semua Periode" + daftar TA. | Low | | | | | |
| **BRD-018** | Welcome message/identitas | Exploratory | UI/UX | Login siswa | 1. Buka beranda | N/A | Nama siswa tampil di header/widget. | Low | | | | | |

### 2. Portal Tagihan & Bayar Online

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Priority | Actual Result | Status | Severity | Bug ID | Evidence |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **PTG-001** | Halaman tagihan termuat | Equivalence Partitioning | Functional | Login siswa | 1. Buka `/portal/tagihan` | N/A | Card view tagihan: nama, jumlah, sisa, status, checkbox. | High | | | | | |
| **PTG-002** | Status badge benar | Equivalence Partitioning | Functional | Ada Lunas & Belum Lunas | 1. Buka, cek badge status | N/A | Lunas: hijau. Belum Lunas: merah/kuning. | High | | | | | |
| **PTG-003** | Midtrans disabled — bayar hilang | State Transition | Functional | `midtrans_enabled=false` | 1. Set Midtrans off<br>2. Buka tagihan | N/A | Tidak ada checkbox/select all/tombol bayar. | High | | | | | |
| **PTG-004** | Centang checkbox eligible | Equivalence Partitioning | Functional | Sisa>0, tidak pending | 1. Centang 1 checkbox | N/A | Tercentang. Total berubah. | High | | | | | |
| **PTG-005** | Select all — centang semua | Equivalence Partitioning | Functional | ≥2 eligible | 1. Klik "Pilih Semua" | N/A | Semua eligible tercentang. Total = sum sisa. | High | | | | | |
| **PTG-006** | Select all — uncheck | State Transition | Functional | Semua tercentang | 1. Klik "Pilih Semua" lagi | N/A | Semua uncheck, total = 0. | High | | | | | |
| **PTG-007** | Bayar batch — pilih channel | Equivalence Partitioning | Functional | Ada tercentang | 1. Klik Bayar<br>2. Pilih channel | Channel: BCA VA | Modal konfirmasi detail+total. | High | | | | | |
| **PTG-008** | Batch — di bawah min amount | Boundary Value Analysis | Functional | Sisa < Rp10.000 | 1. Centang sisa 5000<br>2. Klik Bayar | Sisa: 5000 | Tombol disabled / notif min amount. | High | | | | | |
| **PTG-009** | Batch — redirect Midtrans Snap | State Transition | Functional | Semua valid | 1. Centang, pilih channel, submit | N/A | Redirect Snap / snap-in modal. | High | | | | | |
| **PTG-010** | Batch — server error | Error Guessing | Functional | API Midtrans error | 1. Centang, submit | N/A | Notifikasi error. Tagihan tidak berubah. | High | | | | | |
| **PTG-011** | Tagihan pending Midtrans | Equivalence Partitioning | UI/UX | Ada tagihan dgn transaksi pending | 1. Buka halaman | N/A | Checkbox disabled. Indikator pending. | High | | | | | |
| **PTG-012** | Wali — lihat tagihan anak | Equivalence Partitioning | Functional | Login wali | 1. Buka tagihan | N/A | Tagihan anak tampil. | High | | | | | |
| **PTG-013** | Wali — switch anak | State Transition | Functional | Wali >1 anak | 1. Switch anak | N/A | Tagihan anak lain tampil. | High | | | | | |
| **PTG-014** | Tagihan kosong | Equivalence Partitioning | UI/UX | Tanpa tagihan | 1. Buka halaman | N/A | "Belum Ada Tagihan". | Medium | | | | | |
| **PTG-015** | Sibling selector loading | Exploratory | UI/UX | Switch anak | 1. Switch anak | N/A | Loading indicator. | Low | | | | | |
| **PTG-016** | RBAC tanpa permission | Security (dasar) | Security | Tanpa `view-tagihan-siswa` | 1. Paksa buka tagihan | N/A | 403 Forbidden. | High | | | | | |
| **PTG-017** | Checkbox lunas disabled | Equivalence Partitioning | Functional | Status "Lunas" | 1. Coba centang lunas | N/A | Checkbox disabled. | High | | | | | |
| **PTG-018** | Loading state card | Exploratory | UI/UX | Koneksi lambat | 1. Buka halaman | N/A | Skeleton/loading indicator. | Medium | | | | | |
| **PTG-019** | Daftar fee channel muncul | Equivalence Partitioning | Functional | Midtrans enabled | 1. Klik Bayar, buka dropdown | N/A | Daftar channel dari API muncul. | High | | | | | |
| **PTG-020** | Channel default terpilih | Equivalence Partitioning | UI/UX | Ada channel | 1. Klik Bayar | N/A | Channel default terpilih. | Low | | | | | |

### 3. Portal Riwayat Pembayaran

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Priority | Actual Result | Status | Severity | Bug ID | Evidence |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **PRW-001** | Halaman riwayat termuat | Equivalence Partitioning | Functional | Ada pembayaran selesai | 1. Buka `/portal/riwayat-pembayaran` | N/A | Tabel: Kode, Tanggal, Jumlah, Metode, Status, Jenis Tagihan. Aksi. | High | | | | | |
| **PRW-002** | Cari riwayat | Equivalence Partitioning | UI/UX | Banyak transaksi | 1. Ketik kode di search | `INV-001` | Tabel terfilter. | Medium | | | | | |
| **PRW-003** | Pagination | Equivalence Partitioning | UI/UX | >10 transaksi | 1. Klik halaman 2 | N/A | Halaman 2 tampil. | Low | | | | | |
| **PRW-004** | Download kwitansi | Equivalence Partitioning | Functional | Completed, punya `print-kwitansi` | 1. Klik ikon Kwitansi | N/A | PDF terdownload. | High | | | | | |
| **PRW-005** | Lihat status pending | Equivalence Partitioning | Functional | Transaksi pending | 1. Klik "Lihat Status" | N/A | Redirect ke `/portal/status-pembayaran?order_id=...`. | High | | | | | |
| **PRW-006** | Kwitansi untuk pending | Error Guessing | UI/UX | Pending | 1. Cek baris pending | N/A | Tombol Kwitansi tidak muncul. | High | | | | | |
| **PRW-007** | Empty state | Equivalence Partitioning | UI/UX | Tanpa pembayaran | 1. Buka halaman | N/A | "Belum Ada Pembayaran". | Medium | | | | | |
| **PRW-008** | Polling 5s | Exploratory | UI/UX | Bayar di tab lain | 1. Bayar, kembali, tunggu ≤10s | N/A | Data baru muncul otomatis. | Medium | | | | | |

### 4. Portal Status Pembayaran Midtrans

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Priority | Actual Result | Status | Severity | Bug ID | Evidence |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **STP-001** | Halaman termuat — pending | Equivalence Partitioning | Functional | Transaksi pending | 1. Buka dengan order_id valid | order_id valid | Status "Menunggu Pembayaran" (kuning). | High | | | | | |
| **STP-002** | Polling — pending → settlement | State Transition | Functional | Transaksi dibayar | 1. Buka status pending<br>2. Bayar di channel lain | N/A | Dalam ≤120s: status "Berhasil" (hijau). Polling stop. | High | | | | | |
| **STP-003** | Polling — max 24× (120s) | State Transition | Functional | Pending terus | 1. Buka, tunggu 2 menit | N/A | Setelah 24× poll, berhenti. Status tetap pending. | High | | | | | |
| **STP-004** | Terminal status — polling stop | State Transition | Functional | Transaksi sdh settlement | 1. Buka status settlement | N/A | Status "Berhasil". `isPolling=false`. | High | | | | | |
| **STP-005** | Status expired | Equivalence Partitioning | UI/UX | Transaksi expired | 1. Buka status expired | N/A | Badge abu-abu: "Kedaluwarsa". | High | | | | | |
| **STP-006** | Status denied/failed | Equivalence Partitioning | UI/UX | Transaksi failed | 1. Buka status failed | N/A | Badge merah: "Gagal". | High | | | | | |
| **STP-007** | Order ID tidak ditemukan | Error Guessing | Functional | Order ID palsu | 1. Buka `?order_id=FAKE123` | `FAKE123` | Error/404/notif "Transaksi tidak ditemukan". | High | | | | | |
| **STP-008** | Parameter order_id kosong | Error Guessing | Functional | - | 1. Buka tanpa parameter | N/A | 404. | High | | | | | |
| **STP-009** | RBAC tanpa permission | Security (dasar) | Security | Tanpa `view-own-billing` | 1. Paksa buka | N/A | 403 Forbidden. | High | | | | | |
| **STP-010** | Settlement — badge & teks | Equivalence Partitioning | UI/UX | Transaksi settlement | 1. Buka status settlement | N/A | Badge hijau: "Pembayaran Berhasil". Link ke riwayat. | High | | | | | |

### 5. Portal Profil & Pengaturan

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Priority | Actual Result | Status | Severity | Bug ID | Evidence |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **PRF-001** | Halaman profil termuat | Equivalence Partitioning | Functional | Login siswa | 1. Buka `/portal/profil` | N/A | Info: username, email, cabang. Form email, password, notif, info wali. | High | | | | | |
| **PRF-002** | Update email — valid | Equivalence Partitioning | Functional | Email belum terverifikasi | 1. Isi email + password, Simpan | Email: `test@example.com` | Notifikasi "Email berhasil diperbarui". | High | | | | | |
| **PRF-003** | Update email — password salah | Error Guessing | Functional | - | 1. Isi password salah, Simpan | Password: `wrongpass` | Notifikasi error. | High | | | | | |
| **PRF-004** | Ganti password — valid | Equivalence Partitioning | Functional | - | 1. Isi current, new, confirmation, Simpan | New: `NewPass123!` | Notifikasi "Password berhasil diubah". | High | | | | | |
| **PRF-005** | Ganti password — confirmation mismatch | Error Guessing | Functional | - | 1. Isi new & confirmation beda | Conf: beda | Notifikasi error. | High | | | | | |
| **PRF-006** | Ganti password — min length 8 | Boundary Value Analysis | Functional | - | 1. Isi new 5 karakter | New: `12345` | Validasi min 8 karakter. | High | | | | | |
| **PRF-007** | Preferensi notif — toggle ON | Equivalence Partitioning | Functional | Email sudah diatur | 1. Toggle ON, Simpan | N/A | Notifikasi sukses. Refresh: toggle masih ON. | High | | | | | |
| **PRF-008** | Preferensi — simpan tanpa email | Error Guessing | Functional | Email belum diatur | 1. Toggle, Simpan | N/A | Notifikasi "Email belum diatur". | High | | | | | |
| **PRF-009** | Kirim OTP ke email wali | Equivalence Partitioning | Functional | Email wali terisi | 1. Klik "Verifikasi" pada Ayah/Ibu/Wali | N/A | Notifikasi "OTP berhasil dikirim". | High | | | | | |
| **PRF-010** | Verifikasi OTP — kode benar | State Transition | Functional | OTP sudah dikirim | 1. Buka Mailpit<br>2. Ekstrak OTP<br>3. Masukkan OTP | OTP dari Mailpit | Notifikasi "berhasil diverifikasi!". Status "Terverifikasi". | High | | | | | |
| **PRF-011** | Verifikasi OTP — kode salah | Error Guessing | Functional | OTP sudah dikirim | 1. Masukkan OTP asal | `000000` | Notifikasi "Gagal verifikasi OTP". | High | | | | | |
| **PRF-012** | Kirim OTP — email kosong | Error Guessing | Functional | Email wali belum diisi | 1. Klik Verifikasi pada Ayah | N/A | Notifikasi error. | Medium | | | | | |
| **PRF-013** | RBAC tanpa permission | Security (dasar) | Security | Tanpa `view-own-billing` | 1. Paksa buka | N/A | 403 Forbidden. | High | | | | | |
| **PRF-014** | Info email wali + status verifikasi | Equivalence Partitioning | UI/UX | Email terisi & terverifikasi | 1. Scroll ke section Wali | N/A | Email tampil. Badge "Terverifikasi" (hijau) / "Belum Verifikasi" (kuning). | High | | | | | |
| **PRF-015** | Reveal/hide password | Exploratory | UI/UX | - | 1. Klik ikon mata | N/A | Password visible/hidden toggle. | Low | | | | | |
| **PRF-016** | Loading state profil | Exploratory | UI/UX | Koneksi lambat | 1. Buka halaman | N/A | Loading indicator. | Low | | | | | |
| **PRF-017** | Data Siswa — semua field tampil read-only | Equivalence Partitioning | Functional, UI/UX | Login siswa MI | 1. Buka `/portal/profil`<br>2. Scroll ke "Data Siswa" | N/A | Section "Data Siswa" muncul: NIS, NISN, Nama, JK, Tempat Lahir, Tanggal Lahir, Agama, Alamat, Jenjang, Kelas, Asal Sekolah, Tahun Diterima, Status. Semua teks (bukan input). | High | | | | | |
| **PRF-018** | Data Orang Tua — MI tampil Ayah & Ibu | Equivalence Partitioning | Functional | Login MI, data Ayah & Ibu terisi | 1. Buka profil<br>2. Scroll ke "Data Orang Tua" | N/A | Section "Data Orang Tua" muncul. Sub-section Ayah (Nama, Pendidikan, Pekerjaan, Email) & Ibu (Nama, Pendidikan, Pekerjaan, Email). Read-only. | High | | | | | |
| **PRF-019** | Data Wali — TK/KB tampil Wali | Equivalence Partitioning | Functional | Login TK/KB, data Wali terisi | 1. Buka profil<br>2. Scroll ke "Data Wali" | N/A | Section "Data Wali" muncul: Nama, JK, Agama, Pendidikan, Pekerjaan, Alamat, No HP, Email. Read-only. | High | | | | | |
| **PRF-020** | Data Ortu/Wali tidak bocor antar jenjang | Equivalence Partitioning | Security | Login MI (seharusnya tidak ada Wali) | 1. Login MI<br>2. Buka profil | N/A | Section "Data Wali" tidak muncul. Hanya "Data Orang Tua" (Ayah/Ibu). | Medium | | | | | |

---

## Ringkasan Pengujian

- **Total test case:** 76
- **Pass:** 0 | **Fail:** 0 | **Blocked:** 0 | **Untested:** 76
- **Tanggal pengujian:** 8 Juli 2026
- **Penguji:** (Manual — diisi setelah eksekusi)

### Cakupan per Sub-Modul

| Sub-Modul | Jumlah TC | Prioritas Tinggi |
|-----------|----------|-----------------|
| Portal Beranda (BRD) | 18 | 10 |
| Portal Tagihan & Bayar (TGH) | 20 | 14 |
| Portal Riwayat (RIW) | 8 | 5 |
| Status Pembayaran (STP) | 10 | 9 |
| Portal Profil (PRF) | 20 | 15 |
| **Total** | **76** | **53** |

### Daftar Bug Ditemukan

| Bug ID | Terkait TC | Deskripsi Singkat | Severity | Langkah Reproduksi | Evidence |
|---|---|---|---|---|---|
| *(diisi manual)* | | | | | |

### Catatan Tambahan

*(diisi manual setelah pengujian)*

---

*Dokumen hasil pengujian — kolom Actual Result, Status, Severity, Bug ID, Evidence siap diisi manual.*
