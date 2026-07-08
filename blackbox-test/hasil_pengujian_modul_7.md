# Laporan Hasil Pengujian Blackbox — Modul 7: Sistem & Pengaturan

> **Tanggal:** 8 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing — Manual)
> **Lingkungan Uji:** `http://127.0.0.1:8000` (Frontend-v2 Filament)
> **Akun:** `admin@handayani.test` / `admin123` (Superadmin)

---

## Hasil Eksekusi Test Case

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Priority | Actual Result | Status | Severity | Bug ID | Evidence |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **BRN-001** | Tampilkan daftar cabang | Equivalence Partitioning | Functional | Login sebagai Superadmin | 1. Buka Pengaturan → Cabang | N/A | Tabel muncul. | High | | | | | |
| **BRN-002** | Search cabang | Equivalence Partitioning | Functional | Ada ≥3 cabang | 1. Ketik nama cabang | "Selat" | Tabel terfilter. | High | | | | | |
| **BRN-003** | Sort kolom | Equivalence Partitioning | UI/UX | Ada ≥3 cabang | 1. Klik header | N/A | Urutan berubah. | Low | | | | | |
| **BRN-004** | Tambah cabang baru | Equivalence Partitioning | Functional | Login sebagai Superadmin | 1. Tambah, isi nama, Simpan | "Cabang Test BRN" | Sukses. | High | | | | | |
| **BRN-005** | Cabang duplikasi nama | Error Guessing | Functional | Cabang sudah ada | 1. Tambah nama yg sudah ada | "Selat Panjang" | 400/422. | High | | | | | |
| **BRN-006** | Ubah cabang | Equivalence Partitioning | Functional | Cabang tersedia | 1. Ubah, ganti nama, Simpan | Nama baru | Berhasil. | High | | | | | |
| **BRN-007** | Hapus cabang — konfirmasi | Equivalence Partitioning | Functional | Cabang tidak terpakai | 1. Hapus, Konfirmasi | N/A | Berhasil. | High | | | | | |
| **BRN-008** | Hapus cabang — batal | Error Guessing | UI/UX | Cabang tersedia | 1. Hapus, Batal | N/A | Tidak terhapus. | Medium | | | | | |
| **BRN-009** | Bulk delete cabang | Equivalence Partitioning | Functional | Ada ≥2 cabang | 1. Centang 2, Hapus Terpilih | N/A | Notif sukses. | High | | | | | |
| **BRN-010** | Bulk delete tanpa centang | Error Guessing | UI/UX | 1. Klik Hapus Terpilih | N/A | No effect. | Medium | | | | | |
| **BRN-011** | Pagination | Equivalence Partitioning | UI/UX | 1. Klik halaman 2 | N/A | OK. | Low | | | | | |
| **BRN-012** | Tombol Tambah tanpa `create-branch` | RBAC | Security | 1. Buka halaman | N/A | Tombol tidak muncul. | High | | | | | |
| **BRN-013** | Tombol Hapus tanpa `delete-branch` | RBAC | Security | 1. Buka halaman | N/A | Tombol tidak muncul. | High | | | | | |
| **THA-001** | Tampilkan daftar tahun ajaran | Equivalence Partitioning | Functional | 1. Buka Pengaturan → Tahun Ajaran | N/A | Tabel muncul. | High | | | | | |
| **THA-002** | Filter status — Aktif | Equivalence Partitioning | Functional | 1. Filter = Aktif | Aktif | Hanya aktif. | High | | | | | |
| **THA-003** | Filter status — Tidak Aktif | Equivalence Partitioning | Functional | 1. Filter = Tidak Aktif | Nonaktif | Hanya nonaktif. | Medium | | | | | |
| **THA-004** | Search tahun ajaran | Equivalence Partitioning | Functional | 1. Ketik nama | "2025/2026" | Tabel terfilter. | Medium | | | | | |
| **THA-005** | Tambah tahun ajaran baru | Equivalence Partitioning | Functional | 1. Isi nama, tgl, Simpan | "2026/2027 Test" | Sukses. | High | | | | | |
| **THA-006** | Tambah — tgl selesai < mulai | Boundary Value Analysis | Functional | 1. Mulai > Selesai | Mulai > Selesai | 422/validasi. | High | | | | | |
| **THA-007** | Ubah tahun ajaran | Equivalence Partitioning | Functional | 1. Ubah, Simpan | Nama baru | Berhasil. | High | | | | | |
| **THA-008** | Hapus tahun ajaran | Equivalence Partitioning | Functional | 1. Hapus, Konfirmasi | N/A | Berhasil. | High | | | | | |
| **THA-009** | Hapus periode dgn relasi data | Error Guessing | Functional | 1. Hapus periode yg punya data | N/A | 409 conflict. | High | | | | | |
| **THA-010** | Activate periode | State Transition Testing | Functional | 1. Aktifkan | N/A | Status jadi Aktif. | High | | | | | |
| **THA-011** | Deactivate periode | State Transition Testing | Functional | 1. Nonaktifkan | N/A | Status jadi Nonaktif. | High | | | | | |
| **THA-012** | Activate 2 — hanya 1 aktif | State Transition Testing | Functional | 1. Aktifkan periode lain | N/A | Otomatis hanya 1 aktif. | High | | | | | |
| **THA-013** | Pagination | Equivalence Partitioning | UI/UX | 1. Halaman 2 | N/A | OK. | Low | | | | | |
| **THA-014** | RBAC tanpa permission | Security | Security | 1. Paksa buka URL | N/A | 403. | High | | | | | |
| **SET-001** | Tampilkan pengaturan sekolah | Equivalence Partitioning | Functional | 1. Buka Pengaturan → Setting | N/A | Infolist: data sekolah. | High | | | | | |
| **SET-002** | Edit — nama sekolah | Equivalence Partitioning | Functional | 1. Ubah nama, Simpan | "SD Test Handayani" | Berhasil. | High | | | | | |
| **SET-003** | Edit — alamat & kode pos | Equivalence Partitioning | Functional | 1. Ubah, Simpan | Alamat baru | Berhasil. | High | | | | | |
| **SET-004** | Edit — kontak (email/telepon) | Equivalence Partitioning | Functional | 1. Ubah, Simpan | Email/telepon baru | Berhasil. | High | | | | | |
| **SET-005** | Edit — kepala sekolah & bendahara | Equivalence Partitioning | Functional | 1. Ubah, Simpan | Nama baru | Berhasil. | High | | | | | |
| **SET-006** | Edit — upload logo baru | Equivalence Partitioning | Functional | 1. Upload logo, Simpan | Logo.png | Logo berubah. | High | | | | | |
| **SET-007** | Upload file non-gambar | Error Guessing | Functional | 1. Upload .txt | test.txt | Form tolak. | Medium | | | | | |
| **SET-008** | Kosongkan field required | Error Guessing | Functional | 1. Kosongkan nama, Simpan | "" | Form tolak. | High | | | | | |
| **SET-009** | Simpan — verifikasi persistensi | State Transition Testing | Functional | 1. Simpan, Refresh | N/A | Data tetap. | High | | | | | |
| **SET-010** | Tombol Ubah tanpa `update-app-setting` | RBAC | Security | 1. Buka halaman | N/A | Tombol tidak muncul. | High | | | | | |
| **SET-011** | RBAC tanpa `view-app-setting` | Security | Security | 1. Paksa buka URL | N/A | 403. | High | | | | | |
| **KNK-001** | Halaman termuat | Equivalence Partitioning | Functional | 1. Buka Kenaikan Kelas | N/A | Semua elemen muncul. | High | | | | | |
| **KNK-002** | Pilih periode sumber | Equivalence Partitioning | Functional | 1. Pilih periode | N/A | Periode tujuan filter. | High | | | | | |
| **KNK-003** | Ganti tab jenjang — kelas list | Equivalence Partitioning | Functional | 1. Klik tab TK | TK | Sidebar TK. | High | | | | | |
| **KNK-004** | Pilih kelas — siswa tampil | Equivalence Partitioning | Functional | 1. Klik kelas | Kelas: 1-MI | Tabel + summary. | High | | | | | |
| **KNK-005** | Aksi → Tinggal Kelas | Equivalence Partitioning | Functional | 1. Ganti aksi | tinggal_kelas | Summary berubah. | High | | | | | |
| **KNK-006** | Aksi → Lulus (kelas tertinggi) | Equivalence Partitioning | Functional | 1. Pilih kelas tertinggi, aksi = lulus | lulus | Summary lulus. | High | | | | | |
| **KNK-007** | Aksi → Pindah Jenjang + target | State Transition Testing | Functional | 1. Pilih pindah_jenjang, target kelas | Target: kelas | Summary berubah. | High | | | | | |
| **KNK-008** | Pindah Jenjang tanpa target | Error Guessing | Functional | 1. Pilih pindah, tidak pilih target | N/A | Button disabled/error. | High | | | | | |
| **KNK-009** | Proses — bulk promotion | State Transition Testing | Functional | 1. Naik kelas, Proses, Konfirmasi | N/A | Notif sukses. | High | | | | | |
| **KNK-010** | Proses — campuran aksi | State Transition Testing | Functional | 1. Campur aksi, Proses | N/A | Semua diproses. | High | | | | | |
| **KNK-011** | Proses tanpa periode tujuan | Error Guessing | Functional | 1. Kosongkan tujuan | N/A | Button disabled. | High | | | | | |
| **KNK-012** | Proses tanpa siswa | Error Guessing | Functional | 1. Pilih kelas tanpa siswa | N/A | Button disabled. | Medium | | | | | |
| **KNK-013** | Riwayat — tabel batch | Equivalence Partitioning | Functional | 1. Scroll ke riwayat | N/A | Tabel: Tanggal, Tipe, dll. | High | | | | | |
| **KNK-014** | Riwayat — badge tipe batch | Equivalence Partitioning | UI/UX | 1. Perhatikan tipe | N/A | Label sesuai mapping. | Medium | | | | | |
| **KNK-015** | Riwayat — klik Detail | Equivalence Partitioning | Functional | 1. Klik Detail | N/A | Modal detail siswa. | High | | | | | |
| **KNK-016** | Detail — search | Equivalence Partitioning | UI/UX | 1. Search di modal | NIS | Tabel terfilter. | Medium | | | | | |
| **KNK-017** | Riwayat — undo batch | State Transition Testing | Functional | 1. Klik Undo, Konfirmasi | N/A | Notif sukses. | High | | | | | |
| **KNK-018** | Undo — verifikasi data kembali | State Transition Testing | Functional | 1. Cek siswa asal | N/A | Siswa kembali. | High | | | | | |
| **KNK-019** | Undo — tombol hilang stlh di-undo | State Transition Testing | UI/UX | 1. Batch undone | N/A | Tombol tidak muncul. | High | | | | | |
| **KNK-020** | Loading state | Exploratory Testing | UI/UX | 1. Pilih kelas | N/A | Overlay loading. | Medium | | | | | |
| **KNK-021** | Empty — kelas tanpa siswa | Equivalence Partitioning | UI/UX | 1. Pilih kelas kosong | N/A | "Tidak ada siswa". | Medium | | | | | |
| **KNK-022** | Empty — belum pilih kelas | Equivalence Partitioning | UI/UX | 1. Lihat tabel | N/A | "Pilih kelas". | Medium | | | | | |
| **KNK-023** | Empty — riwayat kosong | Equivalence Partitioning | UI/UX | 1. Lihat riwayat | N/A | "Belum Ada Riwayat". | Low | | | | | |
| **KNK-024** | RBAC tanpa `view-kenaikan-kelas` | Security | Security | 1. Paksa buka URL | N/A | 403. | High | | | | | |
| **KNK-025** | Tombol Proses tanpa `process-...` | RBAC | Security | 1. Buka halaman | N/A | Tombol tidak muncul. | High | | | | | |
| **KNK-026** | Tombol Undo tanpa `undo-...` | RBAC | Security | 1. Buka halaman | N/A | Tombol tidak muncul. | High | | | | | |
| **KNK-027** | Pindah Jenjang KB→TK & TK→MI | State Transition Testing | Functional | 1. Pindah jenjang, Proses | KB→TK | Berhasil. | High | | | | | |
| **KNK-028** | Ganti tab — data reset | Equivalence Partitioning | UI/UX | 1. Tab MI, klik TK | N/A | Data reset. | High | | | | | |
| **APR-001** | Lihat konfigurasi approval | Equivalence Partitioning | Functional | 1. Buka Approval Settings | N/A | Form muncul. | High | | | | | |
| **APR-002** | Update — level approval | Equivalence Partitioning | Functional | 1. Ubah, Simpan | N/A | Sukses. | High | | | | | |
| **APR-003** | Toggle off untuk cabang | Equivalence Partitioning | Functional | 1. Nonaktifkan, Simpan | N/A | Approval off. | High | | | | | |
| **APR-004** | Simpan — verifikasi persistensi | State Transition Testing | Functional | 1. Refresh | N/A | Data tetap. | High | | | | | |
| **APR-005** | RBAC tanpa `view-app-setting` | Security | Security | 1. Paksa buka URL | N/A | 403. | High | | | | | |
| **APR-006** | Tombol simpan tanpa `update-app-setting` | RBAC | Security | 1. Buka halaman | N/A | Tombol tidak muncul. | High | | | | | |

---

## Ringkasan Pengujian

- **Total test case:** 72
- **Pass:** 0 | **Fail:** 0 | **Blocked:** 0 | **Untested:** 72
- **Tanggal pengujian:** 8 Juli 2026
- **Penguji:** (Manual — diisi setelah eksekusi)

### Cakupan per Sub-Fitur

| Sub-Fitur | Jumlah TC | Prioritas Tinggi |
|-----------|----------|-----------------|
| Branch Management (BRN) | 13 | 10 |
| Tahun Ajaran (THA) | 14 | 10 |
| Pengaturan Sekolah (SET) | 11 | 10 |
| Kenaikan Kelas (KNK) | 28 | 17 |
| Branch Approval Settings (APR) | 6 | 5 |
| **Total** | **72** | **52** |

### Daftar Bug Ditemukan

| Bug ID | Terkait TC | Deskripsi Singkat | Severity | Langkah Reproduksi | Evidence |
|---|---|---|---|---|---|
| *(diisi manual)* | | | | | |

### Catatan Tambahan

*(diisi manual setelah pengujian)*

---

*Dokumen hasil pengujian — kolom Actual Result, Status, Severity, Bug ID, Evidence siap diisi manual.*
