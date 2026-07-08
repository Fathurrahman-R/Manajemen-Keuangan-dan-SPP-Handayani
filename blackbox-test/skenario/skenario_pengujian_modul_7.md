# Skenario Pengujian Blackbox — Modul 7: Sistem & Pengaturan

> **Tanggal:** 8 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing)
> **Lingkungan Uji:** `http://127.0.0.1:8000` (Frontend-v2 Filament)
> **Akun:** `admin@handayani.test` / `admin123` (Superadmin)
> **Teknik:** Equivalence Partitioning, Boundary Value Analysis, Error Guessing, Exploratory Testing, State Transition Testing
> **Aspek:** Functional (wajib), UI/UX, Security/RBAC

---

## Daftar Sub-Modul

| No | Fitur | Halaman | Backend Controller | API Endpoints | Prioritas |
|----|-------|---------|--------------------|---------------|-----------|
| 1 | **Branch Management (BRN)** | Pengaturan → Cabang | `BranchController` | `/branches` CRUD | High |
| 2 | **Tahun Ajaran Management (THA)** | Pengaturan → Tahun Ajaran | `TahunAjaranController` | `/tahun-ajaran` CRUD + activate/deactivate | High |
| 3 | **Pengaturan Sekolah (SET)** | Pengaturan → Setting | `AppSettingController` | `/setting` GET, `/setting/{id}` POST | High |
| 4 | **Kenaikan Kelas (KNK)** | Data Master → Kenaikan Kelas | `KenaikanKelasController` | `/kenaikan-kelas/*` (10+ endpoints) | High |
| 5 | **Branch Approval Settings (APR)** | Pengaturan → Approval Settings | `BranchApprovalSettingController` | `/branch-approval-settings` GET/PUT | Medium |

---

## 1. Fitur: Branch Management (BRN)

**Halaman:** Pengaturan → Cabang (`/admin/cabang`)
**Fitur:** Tabel daftar cabang, search, sort, tambah (modal), ubah (modal), hapus (konfirmasi), bulk delete, pagination

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **BRN-001** | Tampilkan daftar cabang | EP | Functional | Ada beberapa cabang | 1. Buka Pengaturan → Cabang | N/A | Tabel muncul dengan kolom Nama Cabang. Ada data yang tampil. | High |
| **BRN-002** | Search cabang | EP | Functional | Ada cabang dengan nama spesifik | 1. Ketik nama cabang di search | Nama: "Selat" | Tabel terfilter hanya cabang dengan nama mengandung "Selat". | High |
| **BRN-003** | Sort kolom | EP | UI/UX | Ada >1 cabang | 1. Klik header "Nama Cabang" | N/A | Urutan berubah (ASC ↔ DESC). | Low |
| **BRN-004** | Tambah cabang baru | EP | Functional | - | 1. Klik "Tambah"<br>2. Isi "Nama Cabang"<br>3. Simpan | Nama: "Cabang Test BRN" | Notifikasi "Cabang Berhasil Ditambahkan". Tabel refresh. | High |
| **BRN-005** | Cabang duplikasi nama | EG | Functional | Nama cabang sudah ada sebelumnya | 1. Coba tambah cabang dengan nama sama persis | Nama: "Selat Panjang" | API return error (400/422). Notifikasi gagal. | High |
| **BRN-006** | Ubah cabang | EP | Functional | Ada cabang | 1. Klik ikon "Ubah"<br>2. Ganti nama<br>3. Simpan | Nama: "Cabang Test BRN Renamed" | Notifikasi "Cabang Berhasil Diubah". Nama berubah di tabel. | High |
| **BRN-007** | Hapus cabang — konfirmasi | EP | Functional | Ada cabang yang bisa dihapus | 1. Klik ikon "Hapus"<br>2. Konfirmasi | N/A | Notifikasi "Cabang Berhasil Dihapus". Cabang hilang dari tabel. | High |
| **BRN-008** | Hapus cabang — batal | EG | UI/UX | Modal konfirmasi terbuka | 1. Klik "Hapus"<br>2. Klik "Batal" | N/A | Modal tertutup. Cabang tidak terhapus. | Medium |
| **BRN-009** | Bulk delete cabang | EP | Functional | Ada ≥2 cabang | 1. Centang 2 cabang<br>2. Klik "Hapus Terpilih"<br>3. Konfirmasi | N/A | Notifikasi "{n} cabang berhasil dihapus". | High |
| **BRN-010** | Bulk delete tanpa centang | EG | UI/UX | - | 1. Klik "Hapus Terpilih" tanpa centang | N/A | Aksi tidak aktif atau tidak ada efek. | Medium |
| **BRN-011** | Pagination | EP | UI/UX | Banyak cabang >10 | 1. Klik halaman 2 | N/A | Data halaman 2 tampil. | Low |
| **BRN-012** | Tombol Tambah tidak tampil tanpa `create-branch` | RBAC | Security | Admin tanpa permission create-branch | 1. Buka halaman | N/A | Tombol "Tambah" tidak muncul. | High |
| **BRN-013** | Tombol Hapus tidak tampil tanpa `delete-branch` | RBAC | Security | Admin tanpa permission delete-branch | 1. Buka halaman | N/A | Tombol "Hapus" tidak muncul. | High |

---

## 2. Fitur: Tahun Ajaran Management (THA)

**Halaman:** Pengaturan → Tahun Ajaran
**Fitur:** Tabel daftar tahun ajaran, search, filter status (Aktif/Tidak Aktif), tambah, ubah, hapus, activate/deactivate

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **THA-001** | Tampilkan daftar tahun ajaran | EP | Functional | Ada beberapa periode | 1. Buka Pengaturan → Tahun Ajaran | N/A | Tabel muncul: Nama, Tanggal Mulai, Tanggal Selesai, Status (badge). | High |
| **THA-002** | Filter status — Aktif | EP | Functional | Ada periode aktif & nonaktif | 1. Pilih filter Status = "Aktif" | Status: Aktif | Hanya periode aktif tampil. | High |
| **THA-003** | Filter status — Tidak Aktif | EP | Functional | Ada periode nonaktif | 1. Pilih filter Status = "Tidak Aktif" | Status: Tidak Aktif | Hanya nonaktif tampil. | Medium |
| **THA-004** | Search tahun ajaran | EP | Functional | Periode dengan nama spesifik | 1. Ketik nama | Nama: "2025/2026" | Tabel terfilter. | Medium |
| **THA-005** | Tambah tahun ajaran baru | EP | Functional | - | 1. Klik "Tambah"<br>2. Isi Nama, Tanggal Mulai, Tanggal Selesai<br>3. Simpan | Nama: "2026/2027 Test", Mulai: 2026-07-01, Selesai: 2027-06-30 | Notifikasi sukses. Data tampil di tabel. | High |
| **THA-006** | Tambah — tanggal selesai sebelum tanggal mulai | BVA | Functional | - | 1. Isi Tanggal Mulai > Tanggal Selesai | Mulai: 2027-01-01, Selesai: 2026-01-01 | Form menolak (validasi) atau API return 422. | High |
| **THA-007** | Ubah tahun ajaran | EP | Functional | Ada periode | 1. Klik "Ubah"<br>2. Ganti nama<br>3. Simpan | Nama baru | Berhasil. Data berubah. | High |
| **THA-008** | Hapus tahun ajaran | EP | Functional | Ada periode nonaktif tanpa relasi | 1. Klik "Hapus"<br>2. Konfirmasi | N/A | Berhasil dihapus. | High |
| **THA-009** | Hapus periode yang memiliki relasi data | EG | Functional | Periode yang sudah punya tagihan/siswa | 1. Coba hapus | N/A | API menolak (Integrity constraint violation → 409 Conflict). | High |
| **THA-010** | Activate periode | ST | Functional | Ada periode nonaktif | 1. Klik "Aktifkan" | N/A | Status berubah jadi "Aktif" (badge hijau). | High |
| **THA-011** | Deactivate periode | ST | Functional | Ada periode aktif | 1. Klik "Nonaktifkan" | N/A | Status berubah jadi "Tidak Aktif". | High |
| **THA-012** | Activate dua periode — sistem hanya 1 aktif | ST | Functional | Ada 1 periode aktif | 1. Aktifkan periode lain | N/A | Otomatis hanya 1 yang aktif (yg sebelumnya nonaktif jadi aktif, yg sebelumnya aktif jadi nonaktif). | High |
| **THA-013** | Pagination | EP | UI/UX | Banyak periode | 1. Klik halaman 2 | N/A | Pagination OK. | Low |
| **THA-014** | RBAC — akses tanpa permission | Security | Security | Admin tanpa permission | 1. Paksa buka URL | N/A | 403 Forbidden. | High |

---

## 3. Fitur: Pengaturan Sekolah (SET)

**Halaman:** Pengaturan → Setting
**Fitur:** Tampilan informasi sekolah (infolist: nama, alamat, kontak, logo, kepala sekolah, bendahara), edit via modal form (2 section: Informasi Sekolah, Kontak, Kepemimpinan + upload logo)

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **SET-001** | Tampilkan pengaturan sekolah | EP | Functional | Ada data setting | 1. Buka Pengaturan → Setting | N/A | Infolist muncul: Nama Sekolah, Email, Alamat, Lokasi, Kode Pos, Kepala Sekolah, Bendahara, Telepon, Logo. | High |
| **SET-002** | Edit — ubah nama sekolah | EP | Functional | - | 1. Klik "Ubah"<br>2. Ganti nama<br>3. Simpan | Nama baru: "SD Test Handayani" | Notifikasi "Pengaturan Berhasil Diubah". Nama berubah di infolist. | High |
| **SET-003** | Edit — ubah alamat & kode pos | EP | Functional | - | 1. Ubah alamat | Alamat baru | Berhasil. | High |
| **SET-004** | Edit — ubah kontak (email/telepon) | EP | Functional | - | 1. Ubah email & telepon | Email baru, telepon baru | Berhasil. | High |
| **SET-005** | Edit — ubah kepala sekolah & bendahara | EP | Functional | - | 1. Ubah nama kepala sekolah & bendahara | Nama baru | Berhasil. | High |
| **SET-006** | Edit — upload logo baru | EP | Functional | File gambar valid | 1. Upload file logo<br>2. Simpan | File: logo.png (<2MB) | Logo berubah. | High |
| **SET-007** | Edit — upload file non-gambar | EG | Functional | File tidak valid | 1. Upload file .txt sebagai logo | File: test.txt | Form menolak (hanya image). | Medium |
| **SET-008** | Edit — kosongkan field required | EG | Functional | - | 1. Kosongkan "Nama Sekolah"<br>2. Simpan | Nama: "" | Form menolak (required validation). | High |
| **SET-009** | Simpan perubahan — verifikasi persistensi | ST | Functional | Setelah simpan | 1. Simpan perubahan<br>2. Refresh halaman | N/A | Data yang diubah tetap tampil (persisten). | High |
| **SET-010** | RBAC — tombol Ubah tanpa `update-app-setting` | RBAC | Security | Admin tanpa permission | 1. Buka halaman | N/A | Tombol "Ubah" tidak muncul. | High |
| **SET-011** | RBAC — akses halaman tanpa `view-app-setting` | Security | Security | Admin tanpa permission | 1. Paksa buka URL | N/A | 403 Forbidden. | High |

---

## 4. Fitur: Kenaikan Kelas (KNK)

**Halaman:** Data Master → Kenaikan Kelas
**Fitur:** Selector periode sumber & tujuan, tab jenjang (MI/TK/KB), daftar kelas (sidebar), tabel siswa per kelas, aksi per siswa (dropdown: Naik Kelas/Tinggal Kelas/Lulus/Pindah Jenjang), target kelas untuk pindah jenjang, summary (naik/tinggal/lulus/pindah), tombol proses, riwayat batch (tabel + detail modal + undo)

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **KNK-001** | Halaman termuat — semua elemen | EP | Functional | Login sbg admin view-kenaikan-kelas | 1. Buka Data Master → Kenaikan Kelas | N/A | Halaman muncul: dropdown periode sumber, dropdown periode tujuan, tab jenjang (MI/TK/KB), sidebar kelas kosong, riwayat tabel. | High |
| **KNK-002** | Pilih periode sumber — dropdown terisi | EP | Functional | Ada >1 periode | 1. Pilih periode sumber | Periode: yg ada | Periode tujuan otomatis filter: tidak menampilkan periode sumber. | High |
| **KNK-003** | Pilih jenjang tab — kelas list berubah | EP | Functional | Ada kelas di berbagai jenjang | 1. Klik tab "TK" | Jenjang: TK | Sidebar menampilkan kelas jenjang TK. | High |
| **KNK-004** | Pilih kelas — siswa tampil | EP | Functional | Ada siswa eligible di kelas tsb | 1. Klik kelas di sidebar | Kelas: 1-MI | Tabel siswa muncul: NIS, Nama, Aksi (dropdown default "Naik Kelas"). Summary muncul. | High |
| **KNK-005** | Aksi siswa — ganti ke "Tinggal Kelas" | EP | Functional | Tabel siswa tampil | 1. Ganti dropdown aksi salah satu siswa ke "Tinggal Kelas" | Aksi: tinggal_kelas | Summary berubah: naik_kelas-1, tinggal_kelas+1. | High |
| **KNK-006** | Aksi siswa — ganti ke "Lulus" (kelas tertinggi) | EP | Functional | Pilih kelas tertinggi di jenjang | 1. Pilih kelas tertinggi (misal 6-MI)<br>2. Ganti aksi siswa ke "Lulus" | Aksi: lulus | Summary: lulus+1. Opsi pindah jenjang juga muncul. | High |
| **KNK-007** | Aksi siswa — "Pindah Jenjang" dengan target kelas | ST | Functional | Kelas tertinggi, ada jenjang berikutnya | 1. Pilih "Pindah Jenjang"<br>2. Pilih kelas tujuan dari dropdown | Target kelas: 7-MI → 1-TK? | Target kelas terisi. Summary pindah_jenjang+1. | High |
| **KNK-008** | Pindah Jenjang — tanpa pilih target kelas | EG | Functional | Aksi pindah jenjang dipilih | 1. Pilih "Pindah Jenjang"<br>2. Jangan pilih target kelas<br>3. Klik "Proses Kenaikan Kelas" | N/A | Tombol proses disabled ATAU setelah proses muncul error "Kelas tujuan belum dipilih". | High |
| **KNK-009** | Proses kenaikan kelas — bulk promotion | ST | Functional | Ada ≥1 siswa dengan aksi "Naik Kelas", periode tujuan valid | 1. Pilih "Proses Kenaikan Kelas"<br>2. Konfirmasi | N/A | Notifikasi berisi "Naik Kelas: X siswa". Riwayat batch muncul. | High |
| **KNK-010** | Proses setelah pilih semua aksi | ST | Functional | Campuran naik/tinggal/lulus/pindah | 1. Set berbagai aksi<br>2. Proses | N/A | Masing-masing aksi diproses. Notifikasi summary semua. | High |
| **KNK-011** | Proses tanpa periode tujuan | EG | Functional | Periode tujuan belum dipilih | 1. Kosongkan periode tujuan<br>2. Klik Proses | N/A | Tombol disabled: "Periode tujuan harus dipilih". | High |
| **KNK-012** | Proses tanpa ada siswa | EG | Functional | Tidak ada siswa eligible | 1. Pilih kelas tanpa siswa | N/A | Tombol disabled (count = 0). | Medium |
| **KNK-013** | Riwayat — tabel batch muncul | EP | Functional | Pernah melakukan proses | 1. Scroll ke section "Riwayat Proses" | N/A | Tabel riwayat: Tanggal, Tipe, Kelas Asal, Dari Periode, Jumlah Siswa. | High |
| **KNK-014** | Riwayat — badge tipe batch | EP | UI/UX | Ada batch berbagai tipe | 1. Perhatikan kolom Tipe | N/A | Label: "Kenaikan Kelas (Bulk)", "Kelulusan", "Tinggal Kelas", "Pindah Jenjang". | Medium |
| **KNK-015** | Riwayat — klik Detail | EP | Functional | Ada batch | 1. Klik ikon "Detail" | N/A | Modal detail: tabel siswa (NIS, Nama, Aksi badge, Kelas Asal, Kelas Tujuan). | High |
| **KNK-016** | Riwayat — search di detail modal | EP | UI/UX | Banyak siswa di batch | 1. Buka detail modal<br>2. Ketik NIS/nama di search | NIS: "000001" | Tabel terfilter. | Medium |
| **KNK-017** | Riwayat — undo batch | ST | Functional | Ada batch completed | 1. Klik ikon "Undo"<br>2. Konfirmasi | N/A | Notifikasi "{n} siswa dikembalikan". Jika ada yang dilewati, warning muncul. | High |
| **KNK-018** | Undo — verifikasi data kembali | ST | Functional | Batch sudah di-undo | 1. Cek siswa di kelas asal | N/A | Siswa yang di-naikkan/di-luluskan/di-pindahkan kembali ke posisi semula. | High |
| **KNK-019** | Undo — tombol tidak tampil untuk batch undone | ST | UI/UX | Batch sudah di-undo | 1. Perhatikan baris batch yg sudah di-undo | N/A | Tombol Undo tidak muncul (status bukan "completed"). | High |
| **KNK-020** | Loading state — saat pilih kelas | Exp | UI/UX | Koneksi normal | 1. Pilih kelas | N/A | Loading overlay muncul di area tabel, lalu hilang setelah data termuat. | Medium |
| **KNK-021** | Empty state — kelas tanpa siswa | EP | UI/UX | Pilih kelas tanpa eligible student | 1. Pilih kelas tanpa siswa | N/A | "Tidak ada siswa yang memenuhi syarat di kelas ini." | Medium |
| **KNK-022** | Empty state — belum pilih kelas | EP | UI/UX | Pertama buka halaman | 1. Lihat area tabel | N/A | "Pilih kelas untuk melihat daftar siswa." | Medium |
| **KNK-023** | Empty state — riwayat kosong | EP | UI/UX | Belum pernah proses | 1. Lihat riwayat | N/A | "Belum Ada Riwayat" + icon. | Low |
| **KNK-024** | RBAC — akses tanpa `view-kenaikan-kelas` | Security | Security | Admin tanpa permission | 1. Paksa buka URL | N/A | 403. | High |
| **KNK-025** | RBAC — tombol Proses tanpa `process-kenaikan-kelas` | RBAC | Security | Admin tanpa permission | 1. Buka halaman | N/A | Tombol "Proses Kenaikan Kelas" tidak muncul. | High |
| **KNK-026** | RBAC — tombol Undo tanpa `undo-kenaikan-kelas` | RBAC | Security | Admin tanpa permission | 1. Buka halaman | N/A | Tombol Undo tidak muncul di riwayat. | High |
| **KNK-027** | Pindah Jenjang — KB→TK & TK→MI | ST | Functional | Ada siswa di KB & TK tertinggi | 1. Tab KB, pilih kelas tertinggi<br>2. Pindah jenjang siswa<br>3. Proses | KB→TK | Berhasil. Siswa pindah ke jenjang TK. | High |
| **KNK-028** | Ganti tab jenjang — data ter-reset | EP | UI/UX | Data sudah dimuat di tab sebelumnya | 1. Pilih kelas & siswa di tab MI<br>2. Klik tab TK | N/A | Tabel siswa kosong (reset). Sidebar TK muncul. | High |

---

## 5. Fitur: Branch Approval Settings (APR)

**Halaman:** Pengaturan → *Branch Approval Settings* (jika ada)
**Fitur:** Melihat & mengupdate konfigurasi approval workflow per cabang

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **APR-001** | Lihat konfigurasi approval | EP | Functional | Ada setting approval | 1. Buka Pengaturan → Approval Settings | N/A | Form/menampilkan konfigurasi approval per cabang. | High |
| **APR-002** | Update — ubah level approval | EP | Functional | - | 1. Ubah nilai<br>2. Simpan | N/A | Notifikasi sukses. | High |
| **APR-003** | Update — nonaktifkan approval untuk cabang tertentu | EP | Functional | - | 1. Toggle off<br>2. Simpan | N/A | Approval untuk cabang tsb dinonaktifkan. | High |
| **APR-004** | Simpan — verifikasi persistensi | ST | Functional | Setelah simpan | 1. Refresh halaman | N/A | Data tetap. | High |
| **APR-005** | RBAC — akses tanpa `view-app-setting` | Security | Security | Admin tanpa permission | 1. Paksa buka URL | N/A | 403. | High |
| **APR-006** | RBAC — tombol simpan tanpa `update-app-setting` | RBAC | Security | Admin tanpa permission | 1. Buka halaman | N/A | Tombol simpan tidak muncul. | High |

---

## Ringkasan

| Sub-Fitur | Jumlah TC | Teknik Utama | Prioritas Tinggi |
|-----------|----------|-------------|-----------------|
| Branch Management (BRN) | 13 | EP, EG, Exp | 10 |
| Tahun Ajaran (THA) | 14 | EP, BVA, ST, EG, Exp | 10 |
| Pengaturan Sekolah (SET) | 11 | EP, EG, ST, Exp | 10 |
| Kenaikan Kelas (KNK) | 28 | EP, ST, EG, Exp | 17 |
| Branch Approval Settings (APR) | 6 | EP, ST | 5 |
| **Total** | **72** | - | **52** |

**Keterangan Teknik:**
- **EP** = Equivalence Partitioning
- **BVA** = Boundary Value Analysis
- **ST** = State Transition Testing
- **EG** = Error Guessing
- **Exp** = Exploratory Testing

**Aspek yang Dicakup:**
- Functional: 100% test case
- UI/UX: ~15% (loading state, empty state, badge, filter)
- Security (RBAC): ~15% (tombol/permission checks, 403)
- Business Logic: ~20% (activate only 1, undo data integrity, approval settings)

---

*Dokumen siap untuk direview.*
