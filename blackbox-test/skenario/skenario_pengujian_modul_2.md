# Skenario Pengujian Blackbox — Modul 2: Data Master

> **Tanggal:** 8 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing)
> **Lingkungan Uji:** `http://127.0.0.1:8000` (Frontend-v2 Filament)
> **Akun:** `admin@handayani.test` / `admin123` (Superadmin)
> **Teknik:** Equivalence Partitioning, Boundary Value Analysis, State Transition Testing, Error Guessing, Exploratory Testing
> **Aspek:** Functional (wajib), UI/UX, Usability, Security dasar

---

## Daftar Sub-Modul

| No | Sub-Modul | Halaman Filament | Livewire Component | Backend Controller |
|---|-----------|-----------------|-------------------|--------------------|
| 1 | **Kategori** | `DataMasterCategory` | `DataCategory` | `KategoriController` |
| 2 | **Kelas** | `DataMasterKelas` | `DataKelas` | `KelasController` |
| 3 | **Siswa** | `DataMasterSiswa` | `DataSiswa` | `SiswaController` |
| 4 | **Wali** | *(halaman tersembunyi — akses via URL)* | `DataWali` | `WaliController` |
| 5 | **Tahun Ajaran** | `TahunAjaranManagement` | `TahunAjaranManagement` | `TahunAjaranController` |
| 6 | **Kenaikan Kelas** | `KenaikanKelasPage` | `KenaikanKelas` | `KenaikanKelasController` |
| 7 | **Cabang (Branch)** | `BranchManagement` | `BranchManagement` | `BranchController` |
| 8 | **Jenis Tagihan ⚠️** | `TransaksiJenisTagihan` | `JenisTagihan` | `JenisTagihanController` |

**Catatan Penting:**
- **Jenis Tagihan** tercatat di implementation_plan sebagai bagian dari Modul Data Master (referensi harga SPP), namun di UI frontend masuk grup navigasi **Keuangan** (via `AdminPanelProvider.buildKeuanganItems()`). Akan diuji bersamaan Modul 5 (Keuangan).
- **Ayah/Ibu** adalah entitas backend (`Ayah`, `Ibu` models) dengan `ParentSearchController` untuk autocomplete, namun tidak memiliki halaman CRUD mandiri di frontend. Keduanya dikelola *embedded* di Wizard Tambah/Ubah **Siswa** (Step Data Ayah/Ibu untuk MI). Sudah tercakup di TC SIS-001, SIS-002, SIS-010, SIS-011.

---

## 1. Sub-Modul: Kategori

**Halaman:** Data Master → Kategori
**Fitur:** Tambah, Lihat, Ubah, Hapus (single & bulk)
**Field:** `nama` (diubah ke UPPERCASE oleh backend)
**Branch-scoped:** Ya

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| KAT-001 | Tambah kategori baru (happy path) | Equivalence Partitioning | Functional | Login sebagai Superadmin | 1. Klik tombol "Tambah"<br>2. Isi "Nama Kategori"<br>3. Klik "Simpan" | `SPP Reguler` | Kategori baru muncul di tabel dengan nama di-UPPERCASE-kan. Notifikasi sukses. | High |
| KAT-002 | Tambah kategori duplikat (nama sama) | Equivalence Partitioning | Functional | Kategori `SPP Reguler` sudah ada | 1. Klik "Tambah"<br>2. Isi nama `spp reguler` (huruf kecil)<br>3. Klik "Simpan" | `spp reguler` | Gagal: sistem menolak karena duplikasi (case-insensitive). Notifikasi error. | High |
| KAT-003 | Tambah kategori dengan nama kosong | Boundary Value Analysis | Functional | Berada di form Tambah | 1. Biarkan field nama kosong<br>2. Langsung klik "Simpan" | `""` (empty) | Form menolak: validasi client-side "Wajib diisi". | Medium |
| KAT-004 | Tambah kategori dengan spasi saja | Error Guessing | Functional | Berada di form Tambah | 1. Isi field nama dengan spasi<br>2. Klik "Simpan" | `"   "` | Form menolak atau backend memvalidasi sebagai trim kosong. | Low |
| KAT-005 | Ubah nama kategori (single edit) | Equivalence Partitioning | Functional | Kategori A sudah ada | 1. Klik ikon pensil pada kategori A<br>2. Ubah nama<br>3. Klik "Simpan" | Awal: `SPP REGULER` → `SPP KHUSUS` | Nama berubah, notifikasi sukses. Tabel ter-refresh. | High |
| KAT-006 | Ubah nama kategori menjadi duplikat | Equivalence Partitioning | Functional | Kategori `SPP REGULER` dan `DSP` sudah ada | 1. Klik edit pada `SPP REGULER`<br>2. Ubah nama menjadi `dsp`<br>3. Simpan | `dsp` (case berbeda) | Gagal: duplikasi terdeteksi. Notifikasi error. | High |
| KAT-007 | Hapus kategori tunggal | State Transition | Functional | Kategori tidak terpakai oleh siswa | 1. Klik ikon tong sampah pada kategori<br>2. Konfirmasi "Ya" | N/A | Kategori terhapus, notifikasi sukses. | Medium |
| KAT-008 | Hapus kategori yang sedang digunakan siswa | Error Guessing | Functional & Security | Kategori digunakan oleh ≥1 siswa | 1. Klik ikon tong sampah pada kategori<br>2. Konfirmasi "Ya" | N/A | Gagal: sistem menolak, notifikasi "kategori digunakan pada data siswa." | High |
| KAT-009 | Hapus massal (bulk delete) kategori | Equivalence Partitioning | Functional | Terdapat 2+ kategori yang tidak terpakai | 1. Centang 2+ kategori<br>2. Pilih aksi "Hapus Terpilih"<br>3. Konfirmasi | N/A | Semua terhapus, hitungan sukses ditampilkan. | Medium |
| KAT-010 | Cari kategori via kolom Search | Equivalence Partitioning | UI/UX | Terdapat ≥5 kategori | 1. Ketik kata kunci di kolom Search | `regu` | Tabel menampilkan hanya kategori yang mengandung "regu". | Low |
| KAT-011 | Visibilitas RBAC: tombol aksi tanpa permission | Security (dasar) | Security & UI | Login dengan admin tanpa `create-kategori` / `update-kategori` / `delete-kategori` | 1. Buka halaman Kategori<br>2. Amati tombol aksi | Akun tanpa `create/update/delete-kategori` | Tombol "Tambah", pensil, dan tong sampah tidak ditampilkan. | High |

---

## 2. Sub-Modul: Kelas

**Halaman:** Data Master → Kelas - {KB/TK/MI}
**Fitur:** Tambah, Lihat, Ubah, Hapus (single & bulk), filter jenjang (tab)
**Field:** `nama`, `level` (opsional, numerik)
**Branch-scoped:** Ya

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| KLS-001 | Tambah kelas baru untuk jenjang KB | Equivalence Partitioning | Functional | Login, tab KB aktif | 1. Klik "Tambah"<br>2. Isi "Nama Kelas" = `KB A`<br>3. Isi "Urutan Level" = `1`<br>4. Simpan | `KB A`, level:1 | Kelas baru muncul di tabel jenjang KB. | High |
| KLS-002 | Tambah kelas dengan level negatif | Boundary Value Analysis | Functional | Berada di form Tambah | 1. Isi "Nama Kelas" = `KB Z`<br>2. Isi "Urutan Level" = `-1`<br>3. Simpan | `KB Z`, level:-1 | Validasi client-side menolak atau backend memvalidasi minValue(1). | Medium |
| KLS-003 | Tambah kelas dengan level desimal | Boundary Value Analysis | Functional | Berada di form Tambah | 1. Isi "Urutan Level" = `2.5` | level:2.5 | Input numerik hanya menerima integer; form menolak. | Low |
| KLS-004 | Tambah kelas tanpa level (opsional) | Equivalence Partitioning | Functional | Berada di form Tambah | 1. Isi "Nama Kelas" = `TK B`<br>2. Biarkan "Urutan Level" kosong<br>3. Simpan | `TK B`, level:null | Kelas berhasil ditambahkan, kolom level menampilkan "-". | Medium |
| KLS-005 | Ganti tab jenjang (KB ↔ TK ↔ MI) | Exploratory | UI/UX | Berada di halaman Kelas | 1. Klik tab "TK"<br>2. Klik tab "MI"<br>3. Klik kembali tab "KB" | N/A | Data tabel berubah sesuai jenjang yang dipilih, tanpa reload halaman penuh. | Low |
| KLS-006 | Ubah nama kelas | Equivalence Partitioning | Functional | Kelas "KB A" ada di jenjang KB | 1. Klik ikon pensil pada kelas<br>2. Ubah nama menjadi `KB A1`<br>3. Simpan | `KB A` → `KB A1` | Nama berubah, tabel ter-refresh. | High |
| KLS-007 | Hapus kelas yang memiliki siswa | Error Guessing | Functional & Security | Kelas "KB A" memiliki siswa terdaftar | 1. Klik tong sampah pada kelas tersebut<br>2. Konfirmasi | N/A | Sistem menolak hapus (integrity constraint / business rule). Notifikasi error. | High |
| KLS-008 | Cari kelas via Search | Equivalence Partitioning | UI/UX | Terdapat ≥5 kelas | 1. Ketik di kolom Search | `KB` | Tabel menampilkan hanya kelas yang mengandung "KB". | Low |
| KLS-009 | Sorting kolom Nama atau Level | Equivalence Partitioning | UI/UX | Terdapat ≥5 kelas | 1. Klik header kolom "Nama"<br>2. Klik sekali lagi | N/A | Data terurut ascending → descending. | Low |
| KLS-010 | Tambah kelas dengan nama yang sudah ada di jenjang yang sama | Equivalence Partitioning | Functional | Kelas "KB A" sudah ada | 1. Tambah kelas baru dengan nama `KB A` | `KB A` | Notifikasi error duplikasi. | Medium |

---

## 3. Sub-Modul: Siswa

**Halaman:** Data Master → Siswa - {KB/TK/MI}
**Fitur:** Tambah (Wizard 2-3 langkah), Lihat Detail, Ubah (Wizard), Hapus (single & bulk), Import/Export, Filter multi-kriteria
**Field:** NIS, NISN (MI only), Nama, Tempat Lahir, Tgl Lahir, Agama, JK, Kelas, Kategori, Alamat, dsb.
**Branch-scoped:** Ya

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| SIS-001 | Tambah siswa baru (jenjang KB) — Wizard 2 langkah | Equivalence Partitioning | Functional | Login, tab KB aktif | 1. Klik "Tambah"<br>2. Isi Step 1 (Data Siswa): NIS, Nama, TTL, dll<br>3. Klik "Selanjutnya"<br>4. Isi Step 2 (Data Wali)<br>5. Klik "Simpan" | NIS: `KB001`, Nama: `Ani`, Wali: `Budi` | Siswa tersimpan, muncul di tabel KB. | High |
| SIS-002 | Tambah siswa baru (jenjang MI) — Wizard 3 langkah | Equivalence Partitioning | Functional | Login, tab MI aktif | 1. Klik "Tambah"<br>2. Isi Step 1 (Data Siswa MI: NIS, NISN, Asal Sekolah, dll)<br>3. Klik "Selanjutnya"<br>4. Isi Step 2 (Data Ayah)<br>5. "Selanjutnya"<br>6. Isi Step 3 (Data Ibu)<br>7. "Simpan" | NIS: `MI001`, NISN: `123456`, Ayah: `Ahmad`, Ibu: `Siti` | Siswa MI tersimpan. | High |
| SIS-003 | Tambah siswa dengan NIS duplikat | Equivalence Partitioning | Functional | NIS `KB001` sudah ada di jenjang KB | 1. Tambah siswa baru dengan NIS yang sama | NIS: `KB001` | Gagal: validasi NIS unik per jenjang. | High |
| SIS-004 | Tambah siswa — langkah Wali, kosongkan field wajib | Error Guessing | Functional | Berada di Step 2 (Data Wali) | 1. Kosongkan Nama Wali<br>2. Klik "Simpan" | Nama Wali: `""` | Validasi form: "Nama Lengkap wajib diisi." | Medium |
| SIS-005 | Lihat detail siswa (halaman DetailSiswa) | Exploratory | UI/UX | Siswa KB/MI tersedia | 1. Klik ikon "Lihat" (mata) pada baris siswa | N/A | Halaman detail menampilkan Info Siswa + Info Wali/Ayah/Ibu dalam format read-only. | Low |
| SIS-006 | Filter data siswa berdasarkan Kelas | Equivalence Partitioning | Functional | Terdapat siswa di berbagai kelas | 1. Pilih filter dropdown "Kelas"<br>2. Pilih kelas tertentu | Kelas: `KB A` | Tabel menampilkan hanya siswa di kelas tersebut. | Medium |
| SIS-007 | Filter data siswa berdasarkan Status | Equivalence Partitioning | Functional | Terdapat siswa Aktif & Lulus | 1. Pilih filter "Status" = `Lulus` | Status: `Lulus` | Hanya siswa berstatus Lulus yang tampil. | Medium |
| SIS-008 | Filter data siswa berdasarkan Jenis Kelamin | Equivalence Partitioning | Functional | Terdapat siswa L & P | 1. Pilih filter "Jenis Kelamin" = `Perempuan` | JK: `Perempuan` | Hanya siswa Perempuan tampil. | Low |
| SIS-009 | Cari siswa via Search (tabel filter) | Equivalence Partitioning | UI/UX | Terdapat ≥10 siswa | 1. Ketik nama/NIS di kolom Search | `Ani` | Tabel menampilkan hanya siswa yang namanya/NIS mengandung "Ani". | Medium |
| SIS-010 | Ubah data Siswa (KB) — semua field diubah | Equivalence Partitioning | Functional | Siswa KB aktif | 1. Klik ikon pensil<br>2. Ubah NIS, Nama, Kelas, Wali di Wizard<br>3. Simpan | Data baru | Semua perubahan tersimpan lewat API PUT. | High |
| SIS-011 | Ubah data Siswa (MI) — validasi field email Ayah/Ibu | Error Guessing | Functional & UI | Siswa MI aktif | 1. Klik ikon pensil detail<br>2. Di Step 2, isi Email Ayah dengan `bukanemail`<br>3. Simpan | Email: `bukanemail` | Validasi email client-side menolak format salah. | Medium |
| SIS-012 | Hapus siswa tunggal | State Transition | Functional | Siswa tidak memiliki tagihan aktif | 1. Klik tong sampah<br>2. Konfirmasi | N/A | Siswa terhapus (soft/hard delete). | High |
| SIS-013 | Hapus massal siswa (bulk delete) | Equivalence Partitioning | Functional | 2+ siswa tanpa tagihan | 1. Centang 2 siswa<br>2. Pilih "Hapus Terpilih"<br>3. Konfirmasi | N/A | Semua terhapus. | Medium |
| SIS-014 | Sorting kolom NIS, Nama, Tanggal Lahir | Exploratory | UI/UX | Terdapat ≥10 siswa | 1. Klik header kolom NIS<br>2. Klik header Nama | N/A | Data terurut sesuai kolom. | Low |
| SIS-015 | Toggle visibilitas kolom (column toggle) | Exploratory | UI/UX | Halaman Siswa | 1. Klik ikon toggle kolom<br>2. Centang/Hapus centang kolom | Kolom: Agama, JK | Kolom muncul/sembunyi sesuai toggle. | Low |
| SIS-016 | Import data siswa dari Excel | Equivalence Partitioning | Functional | File Excel template tersedia | 1. Klik "Import"<br>2. Pilih file Excel<br>3. Upload | File Excel valid | Data terimpor, siswa muncul di tabel. | Medium |
| SIS-017 | Export data siswa ke Excel | Equivalence Partitioning | Functional | Terdapat data siswa | 1. Klik "Export"<br>2. Pilih format Excel | N/A | File Excel terdownload. | Low |
| SIS-018 | Visibilitas RBAC: tombol aksi tanpa permission | Security (dasar) | Security & UI | Login admin tanpa `create/update/delete-siswa` | 1. Buka halaman Siswa<br>2. Amati tombol aksi | Akun terbatas | Tombol Tambah, Edit, Hapus tidak tampil. | High |

---

## 4. Sub-Modul: Wali

**Halaman:** URL `/admin/data-master-wali` (tidak ada di sidebar, hanya via breadcrumb/direct link)
**Fitur:** Tambah, Lihat Detail, Ubah, Hapus (single & bulk)
**Field:** Nama, Agama, JK, Pendidikan Terakhir, Pekerjaan, No HP, Alamat, Keterangan
**Branch-scoped:** Ya

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| WAL-001 | Akses halaman Wali via URL langsung | Exploratory | UI/UX | Login sebagai Superadmin | 1. Ketik URL `/admin/data-master-wali` | N/A | Halaman Wali terbuka dengan daftar wali (jika ada). | Medium |
| WAL-002 | Tambah wali baru (happy path) | Equivalence Partitioning | Functional | Login | 1. Klik "Tambah"<br>2. Isi semua field<br>3. Simpan | Nama: `Budi Santoso`, Agama: `Islam`, JK: `Laki-laki`, Pend: `S1`, Pekerjaan: `Guru`, No HP: `08123456789`, Alamat: `Jl. Merdeka` | Wali tersimpan, muncul di tabel, notifikasi sukses. | High |
| WAL-003 | Tambah wali — nomor HP tidak valid (huruf) | Error Guessing | Functional | Berada di form Tambah | 1. Isi No HP dengan `abc`<br>2. Simpan | No HP: `abc` | Validasi client-side: "Format nomor HP tidak valid" atau form menolak. | Medium |
| WAL-004 | Ubah data wali | Equivalence Partitioning | Functional | Wali sudah ada | 1. Klik ikon pensil<br>2. Ubah nama & pekerjaan<br>3. Simpan | Nama baru, pekerjaan baru | Data berubah, notifikasi sukses. | High |
| WAL-005 | Hapus wali yang sedang dipakai siswa | Error Guessing | Functional & Security | Wali digunakan oleh ≥1 siswa | 1. Klik tong sampah<br>2. Konfirmasi | N/A | Gagal: sistem menolak, notifikasi "wali digunakan pada data siswa." | High |
| WAL-006 | Hapus wali yang tidak terpakai | State Transition | Functional | Wali tidak terikat dengan siswa | 1. Klik tong sampah<br>2. Konfirmasi | N/A | Berhasil dihapus. | Medium |

---

## 5. Sub-Modul: Tahun Ajaran

**Halaman:** Data Master → Tahun Ajaran
**Fitur:** Tambah, Lihat, Ubah, Hapus, Aktifkan/Nonaktifkan
**Field:** `nama` (format `YYYY/YYYY`), `tanggal_mulai`, `tanggal_selesai`, `status` (Aktif/Non-Aktif)
**Branch-scoped:** Ya
**Aturan bisnis:** Hanya satu Tahun Ajaran yang bisa Aktif per branch; format nama harus tahun kedua = tahun pertama + 1.

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| TAJ-001 | Tambah tahun ajaran baru (format benar) | Equivalence Partitioning | Functional | Login | 1. Klik "Tambah"<br>2. Isi Nama: `2026/2027`<br>3. Isi Tgl Mulai & Selesai<br>4. Simpan | Nama: `2026/2027` | Tersimpan dengan status default "Non-Aktif". | High |
| TAJ-002 | Tambah tahun ajaran — format nama salah (selisih tahun ≠ 1) | Boundary Value Analysis | Functional | Berada di form Tambah | 1. Isi Nama: `2026/2028`<br>2. Simpan | `2026/2028` | Gagal: notifikasi error "Format nama harus YYYY/YYYY dengan tahun kedua = tahun pertama + 1." | High |
| TAJ-003 | Tambah tahun ajaran — format nama salah (tidak pakai slash) | Boundary Value Analysis | Functional | Berada di form Tambah | 1. Isi Nama: `2026-2027`<br>2. Simpan | `2026-2027` | Gagal: validasi format error. | High |
| TAJ-004 | Tambah tahun ajaran — nama duplikat | Equivalence Partitioning | Functional | `2026/2027` sudah ada | 1. Tambah dengan nama `2026/2027` kembali | `2026/2027` | Gagal: "Nama tahun ajaran sudah ada untuk branch ini." | High |
| TAJ-005 | Aktifkan tahun ajaran (status → Aktif) | State Transition | Functional | TA dalam status Non-Aktif | 1. Klik tombol "Aktifkan" pada baris TA baru | N/A | Status berubah jadi "Aktif". Semua TA lain di branch otomatis jadi Non-Aktif. | High |
| TAJ-006 | Nonaktifkan tahun ajaran | State Transition | Functional | TA dalam status Aktif | 1. Klik tombol "Nonaktifkan" | N/A | Status berubah jadi "Non-Aktif". | Medium |
| TAJ-007 | Ubah data tahun ajaran | Equivalence Partitioning | Functional | TA non-aktif tersedia | 1. Klik ikon pensil<br>2. Ubah tanggal<br>3. Simpan | Tanggal baru | Data berubah, notifikasi sukses. | High |
| TAJ-008 | Hapus tahun ajaran yang memiliki data terkait | Error Guessing | Functional & Security | TA memiliki tagihan/jenis_tagihan/siswa_kelas | 1. Klik tong sampah<br>2. Konfirmasi | N/A | Gagal: "Tahun ajaran tidak dapat dihapus karena memiliki data terkait." | High |
| TAJ-009 | Hapus tahun ajaran tanpa data terkait | State Transition | Functional | TA baru (non-aktif) tanpa data terkait | 1. Hapus TA tersebut | N/A | Berhasil dihapus. | Medium |

---

## 6. Sub-Modul: Kenaikan Kelas

**Halaman:** Data Master → Kenaikan Kelas
**Fitur:** Naik Kelas Massal (Bulk Promotion), Naik Kelas Individu, Kelulusan, Tinggal Kelas, Pindah Jenjang, Undo Batch, Riwayat Batch
**Kompleksitas:** Tinggi — multi-status dan multi-kondisi

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| KEN-001 | Naik kelas massal (bulk promotion) — happy path | Equivalence Partitioning | Functional | Terdapat kelas dengan siswa eligible | 1. Pilih Kelas sumber<br>2. Pilih TA target<br>3. Klik "Naik Kelas"<br>4. Konfirmasi | Kelas: KB A, TA: 2026/2027 | Semua siswa eligible naik ke kelas berikutnya. Batch tercatat di riwayat. | High |
| KEN-002 | Naik kelas individu | Equivalence Partitioning | Functional | Siswa tertentu eligible naik | 1. Pilih siswa<br>2. Tentukan target kelas<br>3. Konfirmasi | Siswa: Ani, Target Kelas: KB B | Siswa pindah ke kelas target. | High |
| KEN-003 | Kelulusan (graduation) siswa MI | State Transition | Functional | Siswa MI kelas VI eligible lulus | 1. Pilih siswa<br>2. Klik "Luluskan"<br>3. Konfirmasi | N/A | Status siswa berubah jadi "Lulus". | High |
| KEN-004 | Tinggal kelas (retention) | State Transition | Functional | Siswa eligible tinggal kelas | 1. Pilih siswa<br>2. Klik "Tinggal Kelas"<br>3. Konfirmasi | N/A | Siswa tetap di kelas yang sama untuk TA berikutnya. | Medium |
| KEN-005 | Pindah jenjang (cross-level transfer) | State Transition | Functional | Siswa KB pindah ke TK | 1. Pilih siswa<br>2. Target jenjang & kelas baru<br>3. Konfirmasi | Target: TK A | Siswa pindah jenjang, riwayat kelas tercatat. | Medium |
| KEN-006 | Undo (batalkan) batch promosi | State Transition | Functional | Batch promosi sudah diproses | 1. Buka riwayat batch<br>2. Klik "Undo" pada batch tertentu<br>3. Konfirmasi | N/A | Semua siswa dikembalikan ke kelas asal. | High |
| KEN-007 | Lihat riwayat batch promosi | Exploratory | UI/UX | Terdapat ≥1 batch | 1. Buka tab Riwayat Batch<br>2. Klik salah satu batch | N/A | Detail batch: kelas sumber, kelas target, TA, jumlah siswa, user pemroses. | Medium |
| KEN-008 | Naik kelas massal — kelas sumber kosong | Error Guessing | Functional & Usability | Kelas sumber tidak memiliki siswa eligible | 1. Pilih kelas kosong<br>2. Proses | N/A | Notifikasi/informasi: "Tidak ada siswa eligible untuk dinaikkan." | Medium |
| KEN-009 | Naik kelas — siswa sudah di kelas tertinggi (lulus) | Boundary Value Analysis | Functional | Siswa di kelas VI (MI) | 1. Proses naik kelas pada siswa kelas VI | N/A | Sistem otomatis mendeteksi bahwa siswa harus lulus, bukan naik kelas. | High |

---

## 7. Sub-Modul: Cabang (Branch)

**Halaman:** Pengaturan → Manajemen Cabang
**Fitur:** Tambah, Lihat, Ubah, Hapus
**Field:** `location` (unik, case-insensitive)
**Branch-scoped:** *(Tidak — ini adalah master Branch itu sendiri)*
**Catatan:** Berada di grup **Pengaturan**, bukan Akademik. Hanya user dengan `view-branch` yang bisa mengakses.

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| CAB-001 | Tambah cabang baru (happy path) | Equivalence Partitioning | Functional | Login sbg Superadmin | 1. Buka Pengaturan → Manajemen Cabang<br>2. Klik "Tambah"<br>3. Isi "Lokasi" = `Jakarta Selatan`<br>4. Simpan | `Jakarta Selatan` | Cabang baru muncul di tabel. Notifikasi sukses. | High |
| CAB-002 | Tambah cabang duplikat (case-insensitive) | Equivalence Partitioning | Functional | Cabang `Jakarta Selatan` sudah ada | 1. Tambah cabang `jakarta selatan`<br>2. Simpan | `jakarta selatan` | Gagal: "Nama cabang sudah ada." | High |
| CAB-003 | Tambah cabang — field lokasi kosong | Boundary Value Analysis | Functional | Berada di form Tambah | 1. Biarkan field kosong<br>2. Simpan | `""` | Validasi client-side: "Wajib diisi." | Medium |
| CAB-004 | Ubah lokasi cabang | Equivalence Partitioning | Functional | Cabang tersedia | 1. Klik ikon pensil<br>2. Ubah lokasi<br>3. Simpan | `Jakarta Pusat` | Berubah, notifikasi sukses. | High |
| CAB-005 | Ubah cabang — nama duplikat (termasuk dirinya sendiri) | Equivalence Partitioning | Functional | 2 cabang: `Jakarta` & `Bandung` | 1. Edit `Jakarta` → `Bandung`<br>2. Simpan | `Bandung` | Gagal: duplikasi terdeteksi (exclude self). | High |
| CAB-006 | Hapus cabang yang memiliki data terkait (user/siswa/kelas) | Error Guessing | Functional & Security | Cabang memiliki user/siswa/kelas | 1. Klik tong sampah<br>2. Konfirmasi | N/A | Gagal: "Cabang tidak dapat dihapus karena memiliki data terkait." | High |
| CAB-007 | Hapus cabang tanpa data terkait | State Transition | Functional | Cabang baru (kosong) | 1. Hapus cabang tersebut | N/A | Berhasil dihapus. | Medium |
| CAB-008 | RBAC: visibilitas halaman tanpa permission `view-branch` | Security (dasar) | Security & UI | Login admin tanpa `view-branch` | 1. Cari menu "Manajemen Cabang" di sidebar | N/A | Menu tidak muncul. Jika akses via URL langsung: 403. | High |
| CAB-009 | Cari cabang via Search (jika ada kolom search) | Equivalence Partitioning | UI/UX | ≥3 cabang | 1. Ketik kata kunci | `jakarta` | Tabel menampilkan cabang yang cocok. | Low |

## Ringkasan

| Sub-Modul | Jumlah TC | Teknik Utama | Prioritas Tinggi |
|-----------|----------|-------------|-----------------|
| Kategori (KAT) | 11 | EP, BVA, ST, EG | 5 |
| Kelas (KLS) | 10 | EP, BVA, ST, EG, Exp | 2 |
| Siswa (SIS) | 18 | EP, BVA, ST, EG, Exp | 7 |
| Wali (WAL) | 6 | EP, EG, ST | 3 |
| Tahun Ajaran (TAJ) | 9 | EP, BVA, ST, EG | 5 |
| Kenaikan Kelas (KEN) | 9 | EP, ST, EG, BVA | 5 |
| Cabang (CAB) | 9 | EP, BVA, ST, EG | 4 |
| **Total** | **72** | - | **31** |

**Keterangan Teknik:**
- **EP** = Equivalence Partitioning
- **BVA** = Boundary Value Analysis
- **ST** = State Transition Testing
- **EG** = Error Guessing
- **Exp** = Exploratory Testing

**Aspek yang Dicakup:**
- Functional: 100% test case
- UI/UX: ~15% (sorting, toggle kolom, navigasi tab)
- Security (dasar): ~5% (RBAC visibilitas tombol, proteksi hapus data terikat)
- Usability: ~5% (pesan error, konfirmasi sebelum aksi destruktif)

---

*Dokumen ini siap untuk direview dan mendapatkan approval sebelum Tahap 2 (Eksekusi Pengujian di Browser).*
