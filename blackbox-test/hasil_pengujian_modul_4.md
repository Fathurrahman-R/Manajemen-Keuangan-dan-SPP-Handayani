# Laporan Hasil Pengujian Blackbox — Modul 4: Import & Ekspor Data

> **Tanggal:** 8 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing — Manual)
> **Lingkungan Uji:** `http://127.0.0.1:8000` (Frontend-v2 Filament)
> **Akun:** `admin@handayani.test` / `admin123` (Superadmin)

---

## Hasil Eksekusi Test Case

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Priority | Actual Result | Status | Severity | Bug ID | Evidence |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **TMP-001** | Download template import siswa | Equivalence Partitioning | Functional | Login sbg Superadmin, di halaman Data Master → Siswa | 1. Klik tombol "Template"<br>2. Tunggu download | N/A | File `template_import_siswa.xlsx` terdownload. File dapat dibuka di Excel. | High | | | | | |
| **TMP-002** | Download template import tagihan (via API) | Equivalence Partitioning | Functional | Login sbg Superadmin | 1. Akses langsung via API / lakukan dari halaman Tagihan jika tombol ada | N/A | File template tagihan terdownload. | Medium | | | | | |
| **TMP-003** | RBAC: tombol Template tanpa permission `import-data` | Security (dasar) | Security & UI | Login admin tanpa `import-data` | 1. Buka halaman Siswa<br>2. Amati tombol "Template" | N/A | Tombol Template tidak tampil. | High | | | | | |
| **IMS-001** | Import siswa — file valid dengan data baru | Equivalence Partitioning | Functional | File Excel template sudah diisi 3 siswa baru valid | 1. Klik "Import"<br>2. Pilih file Excel valid<br>3. Upload | File: `import_siswa_valid.xlsx` (3 baris) | Notif validasi "3 valid, 0 error". Auto-confirm: "3 data berhasil diimport." Siswa muncul di tabel. | High | | | | | |
| **IMS-002** | Import siswa — file dengan baris error | Error Guessing | Functional | File Excel berisi 2 valid + 1 error (NIS duplikat) | 1. Upload file | File: `import_siswa_error.xlsx` | Notifikasi "Validasi: 2 valid, 1 error". Hanya 2 baris valid yg diimport. | High | | | | | |
| **IMS-003** | Import siswa — file kosong (0 baris) | Boundary Value Analysis | Functional | File Excel tanpa data (hanya header) | 1. Upload file kosong | File: hanya header | Gagal: "Tidak ada baris valid untuk diimport." | Medium | | | | | |
| **IMS-004** | Import siswa — file bukan format Excel | Error Guessing | Functional | File PDF/gambar disiapkan | 1. Upload file non-Excel | File: PDF/jpg | Form upload menolak (acceptedFileTypes). | Medium | | | | | |
| **IMS-005** | Import siswa — file > 5MB | Boundary Value Analysis | Functional | File Excel > 5MB | 1. Upload file besar | File > 5MB | Validasi client-side: "Maks 5MB". | Medium | | | | | |
| **IMS-006** | Import siswa — NIS sudah ada | Equivalence Partitioning | Functional | NIS `KB001` sudah ada | 1. Upload file berisi NIS `KB001` | File dengan NIS existing | Preview: baris error. Hanya baris unik diimport. | High | | | | | |
| **IMS-007** | Import siswa — file header salah | Error Guessing | Functional | File Excel dimodifikasi headernya | 1. Upload file dengan header salah | File: header tdk sesuai template | Preview error: peringatan format. | Medium | | | | | |
| **IMS-008** | RBAC: tombol Import tanpa `import-data` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Buka halaman Siswa | N/A | Tombol Import tidak tampil. | High | | | | | |
| **RIW-001** | Lihat riwayat import — modal terbuka | Exploratory | UI/UX | Ada ≥1 batch import | 1. Klik "Riwayat Import" | N/A | Modal tabel: File, Sukses, Error, Status, Tanggal, Aksi. | Medium | | | | | |
| **RIW-002** | Riwayat import — batch 'completed' | Equivalence Partitioning | Functional | Batch sebelumnya sukses | 1. Buka Riwayat Import | N/A | Batch 'completed' badge hijau + tombol Rollback. | Medium | | | | | |
| **RIW-003** | Riwayat import — batch 'failed' | Equivalence Partitioning | Functional | Ada batch gagal | 1. Buka Riwayat Import | N/A | Batch 'failed' badge merah, tanpa Rollback. | Low | | | | | |
| **RIW-004** | Rollback batch import — sukses | State Transition | Functional | Batch sukses, ≤48 jam | 1. Klik "Rollback"<br>2. Konfirmasi | N/A | Notif "Rollback Berhasil". Data siswa berkurang. | High | | | | | |
| **RIW-005** | Rollback batch — expired >48 jam | Boundary Value Analysis | Security & Functional | Batch >48 jam | 1. Buka Riwayat Import untuk batch lama | N/A | Tombol Rollback tidak muncul. | High | | | | | |
| **RIW-006** | Rollback batch — status 'processing' | Error Guessing | Security | Batch masih diproses | 1. Rollback via API | Batch processing | Backend tolak: 422. | Medium | | | | | |
| **RIW-007** | RBAC: Riwayat Import tanpa `import-data` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Amati halaman Siswa | N/A | Tombol Riwayat Import tidak tampil. | High | | | | | |
| **EXP-001** | Export siswa ke Excel (.xlsx) | Equivalence Partitioning | Functional | Ada data siswa KB | 1. Klik "Export"<br>2. Pilih xlsx<br>3. Export | Format: xlsx | File export terdownload, berisi data siswa. | High | | | | | |
| **EXP-002** | Export siswa ke CSV (.csv) | Equivalence Partitioning | Functional | Ada data siswa | 1. Pilih CSV<br>2. Export | Format: csv | File CSV terdownload. | Medium | | | | | |
| **EXP-003** | Export siswa — data kosong | Error Guessing | Functional | Tdk ada siswa di jenjang | 1. Export jenjang kosong | N/A | File header saja / notif "Tidak ada data". | Low | | | | | |
| **EXP-004** | RBAC: Export tanpa `export-data` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Buka halaman Siswa | N/A | Tombol Export tidak tampil. | High | | | | | |
| **EXT-001** | Export tagihan — format Excel | Equivalence Partitioning | Functional | Ada data tagihan | 1. Buka Tagihan - KB<br>2. Klik Export<br>3. Format xlsx<br>4. Export | N/A | File tagihan terdownload. | High | | | | | |
| **EXT-002** | Export tagihan — filter dibawa | Equivalence Partitioning | Functional | Filter status aktif | 1. Set filter<br>2. Export | Status: Belum Dibayar | File hanya berisi tagihan sesuai filter. | Medium | | | | | |
| **EXT-003** | RBAC: Export Tagihan tanpa `export-data` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Buka Tagihan | N/A | Tombol Export tidak tampil. | High | | | | | |
| **EXB-001** | Export pembayaran — default | Equivalence Partitioning | Functional | Ada data pembayaran | 1. Buka Pembayaran<br>2. Klik Export<br>3. Format xlsx<br>4. Export | N/A | File `export_pembayaran_*.xlsx` terdownload. | High | | | | | |
| **EXB-002** | RBAC: Export Pembayaran tanpa `export-data` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Buka Pembayaran | N/A | Tombol Export tidak tampil. | High | | | | | |
| **EXK-001** | Export kas harian — Excel | Equivalence Partitioning | Functional | Ada data kas harian | 1. Buka Kas Harian<br>2. Klik Export Excel/CSV<br>3. Pilih bulan, tahun, format xlsx<br>4. Export | Bulan: 7, Tahun: 2026, xlsx | File terdownload. | High | | | | | |
| **EXK-002** | Export kas harian — CSV | Equivalence Partitioning | Functional | Ada data kas harian | 1. Export CSV | Format: csv | File CSV terdownload. | Medium | | | | | |
| **EXK-003** | Export kas harian — bulan kosong | Boundary Value Analysis | Functional | Belum ada transaksi | 1. Export bulan tanpa data | Bulan: 1 (Jan) | File header saja / notif. | Low | | | | | |
| **EXK-004** | Export PDF Kas Harian | Equivalence Partitioning | Functional | Ada data | 1. Klik Export PDF<br>2. Pilih bulan & tahun | Bulan: 7, Tahun: 2026 | File PDF terdownload. | High | | | | | |
| **EXK-005** | RBAC: Export Excel tanpa `export-laporan` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Buka Kas Harian | N/A | Tombol Export Excel/CSV tidak tampil. | High | | | | | |
| **EXR-001** | Export rekap bulanan — Excel | Equivalence Partitioning | Functional | Ada data rekap | 1. Buka Rekap Bulanan<br>2. Export xlsx | Tahun: 2026, xlsx | File terdownload. | High | | | | | |
| **EXR-002** | Export rekap bulanan — CSV | Equivalence Partitioning | Functional | Ada data | 1. Export CSV | Format: csv | File CSV terdownload. | Medium | | | | | |
| **EXR-003** | Export rekap — tahun tanpa data | Error Guessing | Functional | Tdk ada data tahun | 1. Export tahun tanpa data | Tahun: 2020 | Notif / file kosong. | Low | | | | | |
| **EXR-004** | RBAC: Export Rekap tanpa `export-laporan` | Security (dasar) | Security & UI | Admin tanpa permission | 1. Buka Rekap Bulanan | N/A | Tombol Export tidak tampil. | High | | | | | |
| **IMT-001** | Import tagihan via API — file valid | Equivalence Partitioning | Functional | File tagihan valid | 1. POST upload<br>2. POST confirm | File valid | Preview success, confirm sukses. | High | | | | | |
| **IMT-002** | Import tagihan — jenis tagihan tdk ditemukan | Error Guessing | Functional | Referensi jenis tagihan invalid | 1. Upload file dgn jenis tagihan invalid | File: jenis tagihan tidak ada | Preview error count. | High | | | | | |
| **IMT-003** | RBAC: akses import tagihan tanpa `import-data` | Security (dasar) | Security | Token tanpa permission | 1. POST ke endpoint import | Token tanpa permission | 403 Forbidden. | High | | | | | |
| **JOB-001** | Cek status job ID valid (export completed) | Equivalence Partitioning | Functional | Export job selesai | 1. GET `/api/import-export/job/{id}/status` | Job ID valid | `{"type":"export","status":"completed","download_url":"..."}` | Medium | | | | | |
| **JOB-002** | Cek status job ID tidak ditemukan | Error Guessing | Functional | Job ID palsu | 1. GET `/api/import-export/job/FAKE123/status` | Job ID: `FAKE123` | 404: "Job tidak ditemukan." | Low | | | | | |

---

## Ringkasan Pengujian

- **Total test case:** 41
- **Pass:** 0 | **Fail:** 0 | **Blocked:** 0 | **Untested:** 41
- **Tanggal pengujian:** 8 Juli 2026
- **Penguji:** (Manual — diisi setelah eksekusi)

### Cakupan per Sub-Fitur

| Sub-Fitur | Jumlah TC | Prioritas Tinggi |
|-----------|----------|-----------------|
| Template Download (TMP) | 3 | 2 |
| Import Siswa (IMS) | 8 | 3 |
| Riwayat Import & Rollback (RIW) | 7 | 4 |
| Export Siswa (EXP) | 4 | 2 |
| Export Tagihan (EXT) | 3 | 2 |
| Export Pembayaran (EXB) | 2 | 2 |
| Export Kas Harian (EXK) | 5 | 3 |
| Export Rekap Bulanan (EXR) | 4 | 2 |
| Import Tagihan (IMT) | 3 | 2 |
| Job Status (JOB) | 2 | 0 |
| **Total** | **41** | **24** |

### Daftar Bug Ditemukan

| Bug ID | Terkait TC | Deskripsi Singkat | Severity | Langkah Reproduksi | Evidence |
|---|---|---|---|---|---|
| *(diisi manual)* | | | | | |

### Catatan Tambahan

*(diisi manual setelah pengujian)*

---

*Dokumen hasil pengujian — kolom Actual Result, Status, Severity, Bug ID, Evidence siap diisi manual.*
