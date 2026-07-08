# Skenario Pengujian Blackbox — Modul 8: Portal Siswa & Wali

> **Tanggal:** 8 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing)
> **Lingkungan Uji:** `http://127.0.0.1:8000/portal` (Frontend-v2 Filament Portal)
> **Akun:** Siswa NIS `000001` / default password atau wali dari siswa tersebut
> **Teknik:** Equivalence Partitioning, Boundary Value Analysis, State Transition Testing, Error Guessing, Exploratory Testing
> **Aspek:** Functional (wajib), UI/UX, Security/RBAC, Payment Flow

---

## Daftar Sub-Modul

| No | Sub-Modul | Halaman Filament | Livewire Component | Backend Controller |
|---|-----------|-----------------|-------------------|--------------------|
| 1 | **Portal Beranda** | `PortalBerandaPage` | — | `DashboardController@siswaDashboard` |
| 2 | **Portal Tagihan & Bayar Online** | `PortalTagihanPage` | `TagihanSiswa` | `TagihanController@siswaView` + `MidtransTransactionController` |
| 3 | **Portal Riwayat Pembayaran** | `PortalRiwayatPembayaranPage` | — | `PembayaranController@siswaView` + `PdfGeneratorController` |
| 4 | **Portal Status Pembayaran Midtrans** | `PortalStatusPembayaranPage` | — | `MidtransTransactionController@show` |
| 5 | **Portal Profil & Pengaturan** | `PortalProfilPage` | — | `UserController` + `AuthController` |

**Catatan Penting:**
- Portal Siswa & Wali diakses via path `/portal` dengan panel terpisah (`PortalPanelProvider`).
- Siswa login dengan NIS + password, wali login dengan username/email + password.
- Halaman portal terdaftar di `PortalPanelProvider` dan navigasi manual via `NavigationBuilder`.
- Midtrans online payment hanya aktif jika `config('handayani.features.midtrans_enabled') = true`.
- Semua halaman portal memiliki RBAC: siswa/wali dicek via session role `siswa` atau `wali`.

---

## 1. Sub-Modul: Portal Beranda

**Halaman:** `/portal/beranda`
**Fitur:** Filter periode, stats widget, daftar tagihan (table), pembayaran terbaru (table)
**Polling:** 5 detik (wire:poll)

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| BRD-001 | Portal beranda termuat | Equivalence Partitioning | Functional | Login sebagai siswa dengan data tagihan | 1. Buka `/portal/beranda` | N/A | Halaman beranda tampil: filter periode, stats widget, tabel tagihan, tabel pembayaran terbaru. | High |
| BRD-002 | Filter periode — tampilkan semua data | Equivalence Partitioning | Functional | Ada data di >1 periode | 1. Pilih "Semua Periode" pada dropdown | Periode: Semua | Stats menampilkan akumulasi semua periode. Tabel tagihan & pembayaran dari semua periode. | High |
| BRD-003 | Filter periode — pilih periode spesifik | Equivalence Partitioning | Functional | Ada data di 2 periode berbeda | 1. Pilih periode tertentu (mis. 2025/2026) | Periode: 2025/2026 | Stats & data hanya untuk periode tersebut. | High |
| BRD-004 | Stats widget — angka akurat | Equivalence Partitioning | Functional | Ada tagihan & pembayaran | 1. Buka beranda<br>2. Cocokkan angka stats dengan data manual | N/A | Total Tagihan = ∑ seluruh tagihan. Total Dibayar = ∑ pembayaran. Sisa = selisih. | High |
| BRD-005 | Tabel tagihan — data muncul sesuai | Equivalence Partitioning | UI/UX | Ada tagihan | 1. Scroll ke section "Daftar Tagihan" | N/A | Tabel: Jenis Tagihan, Jumlah, Terbayar, Sisa, Status. Data sesuai. | High |
| BRD-006 | Tabel tagihan — auto-refresh (polling 5s) | Exploratory | UI/UX | Ada tagihan | 1. Buka halaman<br>2. Tunggu 10-15 detik | N/A | Data terlihat refresh (wire:poll aktif). | Medium |
| BRD-007 | Tabel pembayaran terbaru — data muncul | Equivalence Partitioning | UI/UX | Ada transaksi pembayaran | 1. Scroll ke "Pembayaran Terbaru" | N/A | Tabel: kode, tanggal, jumlah, metode, status. | High |
| BRD-008 | RBAC — akses tanpa permission `view-own-billing` | Security (dasar) | Security | Login sebagai user tanpa permission | 1. Paksa buka `/portal/beranda` | N/A | 403 Forbidden. | High |
| BRD-009 | Loading state saat data dimuat | Exploratory | UI/UX | Koneksi lambat | 1. Buka halaman | N/A | Loading indicator muncul saat fetch data. | Medium |
| BRD-010 | Empty state — tagihan kosong | Equivalence Partitioning | UI/UX | Siswa tanpa tagihan | 1. Buka beranda | N/A | Teks "Belum Ada Tagihan" atau empty state serupa. | Medium |
| BRD-011 | Empty state — pembayaran kosong | Equivalence Partitioning | UI/UX | Siswa tanpa pembayaran | 1. Buka beranda | N/A | Teks "Belum Ada Pembayaran". | Medium |
| BRD-012 | Ganti filter periode — stats berubah | State Transition | Functional | Data di beberapa periode | 1. Pilih periode A, catat stats<br>2. Ganti ke periode B | N/A | Stats berubah sesuai periode. | High |
| BRD-013 | Wali — dropdown anak tidak muncul (1 anak) | Error Guessing | UI/UX | Login wali dengan 1 siswa | 1. Buka beranda | N/A | Dropdown pilih anak tidak muncul (UI di-comment). | Low |
| BRD-014 | Loading overlay saat ganti filter | Exploratory | UI/UX | Data besar | 1. Ganti filter periode | N/A | `opacity-50 pointer-events-none` overlay pada div data saat loading. | Medium |
| BRD-015 | Akses beranda sebagai Admin | Security (dasar) | Security | Login sebagai admin | 1. Buka `/portal/beranda` | N/A | 403 Forbidden (admin bukan siswa/wali). | High |
| BRD-016 | Stats — data 0/null handling | Equivalence Partitioning | Functional | Siswa baru tanpa data | 1. Buka beranda | N/A | Stats tampilkan 0 / "-", bukan error. | Medium |
| BRD-017 | Dropdown periode — daftar valid | Equivalence Partitioning | UI/UX | Ada >1 periode | 1. Buka dropdown periode | N/A | Dropdown berisi "Semua Periode" + daftar TA dengan label (Aktif)/(Historis). | Low |
| BRD-018 | Welcome message atau identitas siswa | Exploratory | UI/UX | Login sebagai siswa | 1. Buka beranda | N/A | Nama siswa atau identitas tampil di header/widget. | Low |

---

## 2. Sub-Modul: Portal Tagihan & Bayar Online

**Halaman:** `/portal/tagihan`
**Fitur:** Card view tagihan, multi-select batch payment via Midtrans, sibling switcher (wali)
**Field:** Checkbox centang, Select All, Pilih Channel, Bayar batch
**Midtrans-scoped:** Ya

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| PTG-001 | Halaman tagihan termuat | Equivalence Partitioning | Functional | Login sebagai siswa | 1. Buka `/portal/tagihan` | N/A | Card view tagihan tampil: nama tagihan, jumlah, sisa, status, checkbox. | High |
| PTG-002 | Status tagihan — badge benar | Equivalence Partitioning | Functional | Ada tagihan Lunas & Belum Lunas | 1. Buka halaman<br>2. Cek badge status | N/A | Lunas: badge hijau "Lunas". Belum Lunas: badge merah/kuning "Belum Lunas". | High |
| PTG-003 | Midtrans disabled — tombol bayar tidak muncul | State Transition | Functional | `midtrans_enabled=false` | 1. Set fitur Midtrans mati<br>2. Buka halaman | N/A | Tidak ada checkbox, select all, atau tombol bayar. Hanya tabel read-only. | High |
| PTG-004 | Centang checkbox tagihan eligible | Equivalence Partitioning | Functional | Tagihan sisa>0, tidak pending | 1. Centang 1 checkbox | N/A | Checkbox tercentang. Total di summary berubah sesuai sisa tagihan. | High |
| PTG-005 | Select All — centang semua eligible | Equivalence Partitioning | Functional | Ada ≥2 tagihan eligible | 1. Klik "Pilih Semua" | N/A | Semua checkbox eligible tercentang. Total = sum sisa. | High |
| PTG-006 | Select All — uncheck semua | State Transition | Functional | Semua tercentang | 1. Klik "Pilih Semua" lagi | N/A | Semua uncheck. Total = 0. | High |
| PTG-007 | Bayar batch — pilih payment channel | Equivalence Partitioning | Functional | Ada tagihan tercentang | 1. Klik "Bayar"<br>2. Pilih channel pembayaran | Channel: BCA Virtual Account | Muncul modal konfirmasi dengan detail transaksi & total. | High |
| PTG-008 | Bayar batch — total di bawah min amount | Boundary Value Analysis | Functional | Sisa tagihan < Rp10.000 | 1. Centang tagihan sisa 5000<br>2. Klik Bayar | Sisa: 5000 | Tombol disabled atau notifikasi "Minimal pembayaran Rp10.000". | High |
| PTG-009 | Bayar batch — redirect ke Midtrans Snap | State Transition | Functional | Semua valid | 1. Centang tagihan<br>2. Pilih channel<br>3. Submit | N/A | Redirect ke Midtrans Snap / muncul snap-in modal pembayaran. | High |
| PTG-010 | Bayar batch — gagal (server error) | Error Guessing | Functional | API Midtrans error | 1. Centang tagihan<br>2. Submit | N/A | Notifikasi error. Status tagihan tidak berubah. | High |
| PTG-011 | Tagihan dengan status pending Midtrans | Equivalence Partitioning | UI/UX | Ada tagihan yg memiliki transaksi pending | 1. Buka halaman | N/A | Tagihan tsb checkbox disabled, tidak bisa dicentang. Ada indikator pending. | High |
| PTG-012 | Wali — lihat tagihan anak | Equivalence Partitioning | Functional | Login sebagai wali dengan anak | 1. Buka tagihan | N/A | Data tagihan anak yang dipilih muncul. | High |
| PTG-013 | Wali — switch ke anak lain | State Transition | Functional | Wali dengan >1 anak | 1. Switch ke anak lain via sibling selector | N/A | Tagihan anak lain tampil. | High |
| PTG-014 | Tagihan kosong — empty state | Equivalence Partitioning | UI/UX | Siswa tanpa tagihan | 1. Buka halaman | N/A | "Belum Ada Tagihan" atau empty state. | Medium |
| PTG-015 | Sibling selector — loading indicator | Exploratory | UI/UX | Switch anak | 1. Switch anak | N/A | Loading indicator saat memuat data anak lain. | Low |
| PTG-016 | RBAC — akses tanpa permission `view-tagihan-siswa` | Security (dasar) | Security | User tanpa permission | 1. Paksa buka `/portal/tagihan` | N/A | 403 Forbidden. | High |
| PTG-017 | Checkbox disabled untuk tagihan lunas | Equivalence Partitioning | Functional | Tagihan status "Lunas" | 1. Coba centang tagihan lunas | N/A | Checkbox disabled (tidak bisa diinteraksi). | High |
| PTG-018 | Loading state saat muat data tagihan | Exploratory | UI/UX | Koneksi lambat | 1. Buka halaman | N/A | Skeleton/loading indicator saat render card. | Medium |
| PTG-019 | Daftar payment channel muncul | Equivalence Partitioning | Functional | Midtrans enabled | 1. Klik Bayar<br>2. Buka dropdown channel | N/A | Daftar channel dari API `/midtrans/fee-channels` muncul. | High |
| PTG-020 | Payment channel default terpilih | Equivalence Partitioning | UI/UX | Ada channel | 1. Klik Bayar | N/A | Channel default otomatis terpilih. | Low |

---

## 3. Sub-Modul: Portal Riwayat Pembayaran

**Halaman:** `/portal/riwayat-pembayaran`
**Fitur:** Tabel riwayat, search, pagination, aksi lihat status (pending), download kwitansi (completed)
**Polling:** 5 detik (wire:poll)

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| PRW-001 | Halaman riwayat termuat | Equivalence Partitioning | Functional | Ada pembayaran selesai | 1. Buka `/portal/riwayat-pembayaran` | N/A | Tabel: Kode, Tanggal, Jumlah, Metode (badge), Status (badge), Jenis Tagihan. Aksi: Kwitansi / Lihat Status. | High |
| PRW-002 | Cari riwayat via kolom Search | Equivalence Partitioning | UI/UX | Banyak transaksi | 1. Ketik kode pembayaran di search | Kode: `INV-001` | Tabel terfilter hanya menampilkan transaksi dengan kode tsb. | Medium |
| PRW-003 | Pagination — pindah halaman | Equivalence Partitioning | UI/UX | >10 transaksi | 1. Klik halaman 2 pada pagination | N/A | Halaman 2 tampil, data berbeda dari halaman 1. | Low |
| PRW-004 | Download kwitansi (completed) | Equivalence Partitioning | Functional | Transaksi completed, user punya `print-kwitansi` | 1. Klik ikon Kwitansi pada baris completed | N/A | File PDF terdownload: `kwitansi-{kode}.pdf`. | High |
| PRW-005 | Lihat status pembayaran pending | Equivalence Partitioning | Functional | Ada transaksi pending Midtrans | 1. Klik "Lihat Status" pada baris pending | N/A | Redirect ke `/portal/status-pembayaran?order_id=...`. | High |
| PRW-006 | Tombol Kwitansi untuk transaksi pending | Error Guessing | UI/UX | Transaksi pending | 1. Cek baris bertstatus pending | N/A | Tombol Kwitansi tidak muncul. Hanya tombol "Lihat Status". | High |
| PRW-007 | Empty state — belum ada pembayaran | Equivalence Partitioning | UI/UX | Siswa baru tanpa pembayaran | 1. Buka halaman | N/A | "Belum Ada Pembayaran" atau empty state serupa. | Medium |
| PRW-008 | Polling 5s — data baru muncul otomatis | Exploratory | UI/UX | Bayar di tab lain | 1. Bayar via Midtrans di tab lain<br>2. Kembali ke tab riwayat, tunggu ≤10s | N/A | Transaksi baru muncul tanpa refresh manual. | Medium |

---

## 4. Sub-Modul: Portal Status Pembayaran Midtrans

**Halaman:** `/portal/status-pembayaran?order_id=...`
**Fitur:** Polling status transaksi Midtrans real-time (5s, max 24× = 120s), auto-stop saat terminal status

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| STP-001 | Halaman status termuat — pending | Equivalence Partitioning | Functional | Ada transaksi pending | 1. Buka `/portal/status-pembayaran?order_id=ORDER001` | order_id valid | Halaman tampil: status "Menunggu Pembayaran" (badge kuning), detail transaksi. | High |
| STP-002 | Polling — status berubah jadi settlement | State Transition | Functional | Transaksi dibayar di channel lain | 1. Buka status pending<br>2. Bayar transaksi tsb di channel lain | N/A | Dalam ≤120s, status berubah jadi "Pembayaran Berhasil" (hijau). Polling berhenti. | High |
| STP-003 | Polling — max 24 kali (120 detik) | State Transition | Functional | Transaksi tetap pending | 1. Buka halaman<br>2. Tunggu 2 menit | N/A | Setelah 24× poll, polling berhenti. Status tetap "Menunggu Pembayaran". | High |
| STP-004 | Status terminal — polling langsung berhenti | State Transition | Functional | Transaksi sudah settlement/expired | 1. Buka status untuk transaksi yg sudah settlement | N/A | Status "Berhasil". `isPolling = false`. Tidak ada polling lanjutan. | High |
| STP-005 | Status expired — badge & teks | Equivalence Partitioning | UI/UX | Transaksi expired | 1. Buka status order yg expired | N/A | Badge abu-abu: "Kedaluwarsa". | High |
| STP-006 | Status denied/failed — badge & teks | Equivalence Partitioning | UI/UX | Transaksi ditolak | 1. Buka status yg failed | N/A | Badge merah: "Gagal". | High |
| STP-007 | Order ID tidak ditemukan | Error Guessing | Functional | Order ID palsu | 1. Buka dengan `order_id=FAKE123` | `FAKE123` | Halaman error / 404 / notifikasi "Transaksi tidak ditemukan". | High |
| STP-008 | Parameter order_id kosong | Error Guessing | Functional | - | 1. Buka `/portal/status-pembayaran` tanpa parameter | N/A | 404 (abort(404) di mount). | High |
| STP-009 | RBAC — akses tanpa permission | Security (dasar) | Security | User tanpa `view-own-billing` | 1. Paksa buka halaman | N/A | 403 Forbidden. | High |
| STP-010 | Status settlement — tampilan sukses | Equivalence Partitioning | UI/UX | Transaksi settlement | 1. Buka status settlement | N/A | Badge hijau: "Pembayaran Berhasil". Ada tombol/detail ke riwayat. | High |

---

## 5. Sub-Modul: Portal Profil & Pengaturan

**Halaman:** `/portal/profil`
**Fitur:** Update email, ganti password, preferensi notifikasi (4 toggle), kirim & verifikasi OTP wali (ayah/ibu/wali)

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| PRF-001 | Halaman profil termuat | Equivalence Partitioning | Functional | Login sebagai siswa | 1. Buka `/portal/profil` | N/A | Informasi user: username, email, cabang. Form email, form password, preferensi notifikasi, info wali. | High |
| PRF-002 | Update email — valid | Equivalence Partitioning | Functional | Email belum terverifikasi | 1. Isi email baru + password saat ini<br>2. Klik Simpan | Email: `test@example.com` | Notifikasi "Email berhasil diperbarui". | High |
| PRF-003 | Update email — password salah | Error Guessing | Functional | - | 1. Isi email baru + password salah<br>2. Simpan | Password: `wrongpass` | Notifikasi error: password salah. | High |
| PRF-004 | Ganti password — valid | Equivalence Partitioning | Functional | - | 1. Isi current, new, confirmation<br>2. Klik Simpan | New: `NewPass123!` | Notifikasi "Password berhasil diubah". | High |
| PRF-005 | Ganti password — confirmation tidak cocok | Error Guessing | Functional | - | 1. Isi new & confirmation berbeda | Conf: berbeda | Notifikasi error. | High |
| PRF-006 | Ganti password — min length 8 | Boundary Value Analysis | Functional | - | 1. Isi new password 5 karakter | New: `12345` | Validasi: minimal 8 karakter. | High |
| PRF-007 | Preferensi notifikasi — toggle ON simpan | Equivalence Partitioning | Functional | Email sudah diatur | 1. Toggle "Tagihan Baru" ON<br>2. Klik Simpan | N/A | Notifikasi sukses. Refresh halaman: toggle masih ON. | High |
| PRF-008 | Preferensi notifikasi — simpan tanpa email | Error Guessing | Functional | Email belum diatur | 1. Toggle notif<br>2. Simpan | N/A | Notifikasi "Email belum diatur". | High |
| PRF-009 | Kirim OTP ke email wali | Equivalence Partitioning | Functional | Email wali terisi | 1. Klik "Verifikasi" pada baris Ayah/Ibu/Wali | N/A | Notifikasi "OTP berhasil dikirim ke email Ayah". | High |
| PRF-010 | Verifikasi OTP wali — kode benar | State Transition | Functional | OTP sudah dikirim | 1. Buka Mailpit (127.0.0.1:8025)<br>2. Ekstrak OTP 6 digit<br>3. Masukkan OTP | OTP dari Mailpit | Notifikasi "Email Ayah berhasil diverifikasi!". Status berubah jadi "Terverifikasi". | High |
| PRF-011 | Verifikasi OTP wali — kode salah | Error Guessing | Functional | OTP sudah dikirim | 1. Masukkan OTP asal | OTP: `000000` | Notifikasi "Gagal verifikasi OTP". | High |
| PRF-012 | Kirim OTP — email wali kosong | Error Guessing | Functional | Email wali belum diisi | 1. Klik Verifikasi pada Ayah | N/A | Notifikasi error: email belum diisi. | Medium |
| PRF-013 | RBAC — akses tanpa permission `view-own-billing` | Security (dasar) | Security | User tanpa permission | 1. Paksa buka `/portal/profil` | N/A | 403 Forbidden. | High |
| PRF-014 | Info email wali + status verifikasi | Equivalence Partitioning | UI/UX | Email wali terisi & terverifikasi | 1. Scroll ke section Wali | N/A | Email ayah/ibu/wali tampil. Badge "Terverifikasi" (hijau) / "Belum Verifikasi" (kuning). | High |
| PRF-015 | Reveal/hide toggle password | Exploratory | UI/UX | - | 1. Klik ikon mata di field password | N/A | Password berubah antara visible / hidden. | Low |
| PRF-016 | Loading state saat fetch data profil | Exploratory | UI/UX | Koneksi lambat | 1. Buka halaman | N/A | Loading indicator saat fetch data `/users/current`. | Low |

---

## Ringkasan

- **Total test case:** 72
- **Cakupan per Sub-Modul:**

| Sub-Modul | Jumlah TC | Prioritas Tinggi |
|-----------|----------|-----------------|
| Portal Beranda (BRD) | 18 | 10 |
| Portal Tagihan & Bayar (TGH) | 20 | 14 |
| Portal Riwayat (RIW) | 8 | 5 |
| Status Pembayaran (STP) | 10 | 9 |
| Portal Profil (PRF) | 16 | 12 |
| **Total** | **72** | **50** |

---

*Dokumen siap untuk direview dan dieksekusi.*
