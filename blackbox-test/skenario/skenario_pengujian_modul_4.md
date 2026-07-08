# Skenario Pengujian Blackbox — Modul 4: Import & Ekspor Data

> **Tanggal:** 8 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing)
> **Lingkungan Uji:** `http://127.0.0.1:8000` (Frontend-v2 Filament)
> **Akun:** `admin@handayani.test` / `admin123` (Superadmin)
> **Teknik:** Equivalence Partitioning, Boundary Value Analysis, State Transition Testing, Error Guessing, Exploratory Testing
> **Aspek:** Functional (wajib), UI/UX, Security dasar

---

## Daftar Sub-Modul

| No | Fitur | Livewire Component | Backend Controller | Backend Service |
|----|-------|-------------------|--------------------|-----------------|
| 1 | **Import Siswa** | `DataSiswa` (via `HasImportExport`) | `ImportExportController::uploadSiswa/confirmSiswa` | `SiswaImportService` |
| 2 | **Export Siswa** | `DataSiswa` (via `HasImportExport`) | `ImportExportController::exportSiswa` | `SiswaExportService` |
| 3 | **Import Tagihan** | `TagihanCardView` (via `HasImportExport` tdk digunakan, tapi ada API) | `ImportExportController::uploadTagihan/confirmTagihan` | `TagihanImportService` |
| 4 | **Export Tagihan** | `TagihanCardView` (via `HasImportExport`) | `ImportExportController::exportTagihan` | `TagihanExportService` |
| 5 | **Export Pembayaran** | (via `HasImportExport`) | `ImportExportController::exportPembayaran` | `PembayaranExportService` |
| 6 | **Export Kas Harian** | `KasHarian` (via `HasImportExport`) | `ImportExportController::exportKasHarian` | `KasExportService` |
| 7 | **Export Rekap Bulanan** | `RekapBulanan` (via `HasImportExport`) | `ImportExportController::exportRekapBulanan` | `KasExportService` |
| 8 | **Template Download** | (via `HasImportExport`) | `ImportExportController::templateSiswa/templateTagihan` | `TemplateService` |
| 9 | **Riwayat Import & Rollback** | (via `HasImportExport`) | `ImportExportController::importHistory/rollbackImport` | `ImportBatchService` |
| 10 | **Job Status** | (via `HasImportExport`) | `ImportExportController::jobStatus` | `ExportJob`/`ImportBatch` model |

**Catatan Penting:**
- Semua fitur Import/Export ada di grup **Transaksi** (sidebar) — halaman **Tagihan** dan **Pembayaran**, serta di grup **Laporan** — **Kas Harian** & **Rekap Bulanan**.
- Import **Siswa** ada di halaman Data Master → Siswa (tombol Import + Template + Riwayat Import di header tabel).
- **Import Tagihan** tersedia via API tetapi belum terlihat di UI card-based TagihanCardView. Hanya **Export Tagihan** (via trait) yang terlihat di UI.
- Fitur **Rollback** hanya tersedia untuk batch import yang berusia ≤ 48 jam dengan status `completed`.
- Permissions: `import-data` untuk semua import, `export-data` untuk semua export.

---

## 1. Fitur: Template Download (TMP)

**Halaman:** Data Master → Siswa (tombol "Template")
**Fitur:** Download template Excel untuk import data

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| TMP-001 | Download template import siswa | Equivalence Partitioning | Functional | Login sbg Superadmin, di halaman Data Master → Siswa | 1. Klik tombol "Template"<br>2. Tunggu download | N/A | File `template_import_siswa.xlsx` terdownload. File dapat dibuka di Excel. | High |
| TMP-002 | Download template import tagihan (via API) | Equivalence Partitioning | Functional | Login sbg Superadmin | 1. Akses langsung via API / lakukan dari halaman Tagihan jika tombol ada | N/A | File template tagihan terdownload. | Medium |
| TMP-003 | RBAC: tombol Template tanpa permission `import-data` | Security (dasar) | Security & UI | Login admin tanpa `import-data` | 1. Buka halaman Siswa<br>2. Amati tombol "Template" | N/A | Tombol Template tidak tampil. | High |

---

## 2. Fitur: Import Siswa (IMS)

**Halaman:** Data Master → Siswa (tombol "Import")
**Fitur:** Upload file Excel → Preview → Konfirmasi → Sukses/Gagal
**Alur:** Upload file (.xlsx/.csv max 5MB) → validasi server → preview jumlah valid/error → auto-confirm jika ada data valid → notifikasi sukses

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| IMS-001 | Import siswa — file valid dengan data baru | Equivalence Partitioning | Functional | File Excel template sudah diisi 3 siswa baru valid | 1. Klik "Import"<br>2. Pilih file Excel valid<br>3. Upload | File: `import_siswa_valid.xlsx` (3 baris) | Notifikasi validasi muncul: "3 valid, 0 error". Auto-confirm: "3 data berhasil diimport." Siswa muncul di tabel. | High |
| IMS-002 | Import siswa — file dengan baris error (format salah) | Error Guessing | Functional | File Excel berisi 2 valid + 1 error (mis. NIS duplikat) | 1. Upload file | File: `import_siswa_error.xlsx` (2 valid, 1 error) | Notifikasi: "Validasi: 2 valid, 1 error". Hanya 2 baris valid yang diimport. | High |
| IMS-003 | Import siswa — file kosong (0 baris) | Boundary Value Analysis | Functional | File Excel tanpa data (hanya header) | 1. Upload file kosong | File: hanya header | Gagal: "Tidak ada baris valid untuk diimport." Notifikasi error. | Medium |
| IMS-004 | Import siswa — file bukan format Excel (PDF, gambar) | Error Guessing | Functional | File PDF/gambar disiapkan | 1. Upload file non-Excel | File: PDF/jpg | Form upload menolak (acceptedFileTypes) — tidak bisa memilih file non-Excel. Jika dipaksa via API: 422. | Medium |
| IMS-005 | Import siswa — file > 5MB | Boundary Value Analysis | Functional | File Excel > 5MB | 1. Upload file besar | File > 5MB | Validasi client-side: "Maks 5MB". Ditolak sebelum upload. | Medium |
| IMS-006 | Import siswa — NIS sudah ada di database | Equivalence Partitioning | Functional | NIS `KB001` sudah ada | 1. Upload file yang berisi NIS `KB001` | File dengan NIS existing | Preview: baris tersebut masuk error. Hanya baris unik yang diimport. | High |
| IMS-007 | Import siswa — file template header salah (kolom hilang) | Error Guessing | Functional | File Excel dengan header dimodifikasi | 1. Upload file dengan header salah | File: header tidak sesuai template | Preview error: "Format file tidak valid" peringatan. | Medium |
| IMS-008 | RBAC: tombol Import tanpa permission `import-data` | Security (dasar) | Security & UI | Login admin tanpa `import-data` | 1. Buka halaman Siswa<br>2. Amati tombol "Import" | N/A | Tombol Import tidak tampil. | High |

---

## 3. Fitur: Riwayat Import & Rollback (RIW)

**Halaman:** Data Master → Siswa (tombol "Riwayat Import")
**Fitur:** Lihat daftar batch import, rollback batch yang eligible (≤48 jam, status completed)

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| RIW-001 | Lihat riwayat import — modal terbuka | Exploratory | UI/UX | Sudah ada ≥1 batch import sebelumnya | 1. Klik "Riwayat Import"<br>2. Periksa modal | N/A | Modal terbuka menampilkan tabel: File, Sukses, Error, Status, Tanggal, Aksi. | Medium |
| RIW-002 | Riwayat import — batch status 'completed' tampil | Equivalence Partitioning | Functional | Batch import sebelumnya sukses | 1. Buka Riwayat Import | N/A | Batch dengan status 'completed' muncul dengan badge hijau dan tombol "Rollback". | Medium |
| RIW-003 | Riwayat import — batch status 'failed' tampil | Equivalence Partitioning | Functional | Ada batch yang gagal | 1. Buka Riwayat Import | N/A | Batch 'failed' muncul dengan badge merah, tanpa tombol Rollback. | Low |
| RIW-004 | Rollback batch import — sukses | State Transition | Functional | Batch import sukses, ≤48 jam | 1. Klik "Rollback"<br>2. Konfirmasi "Yakin ingin rollback?" | N/A | Notifikasi "Rollback Berhasil. Data import telah dihapus." Data siswa di tabel berkurang. | High |
| RIW-005 | Rollback batch import — batch expired (>48 jam) | Boundary Value Analysis | Security & Functional | Batch berusia >48 jam | 1. Buka Riwayat Import untuk batch lama | N/A | Tombol Rollback tidak muncul untuk batch >48 jam (method `isRollbackEligible()`). | High |
| RIW-006 | Rollback batch — batch dengan status 'processing' | Error Guessing | Security | Batch masih diproses | 1. Coba rollback batch 'processing' via API langsung | N/A | Backend menolak: 422/400 karena status bukan 'completed'. | Medium |
| RIW-007 | RBAC: tombol Riwayat Import tanpa permission `import-data` | Security (dasar) | Security & UI | Login admin tanpa `import-data` | 1. Amati halaman Siswa | N/A | Tombol Riwayat Import tidak tampil. | High |

---

## 4. Fitur: Export Siswa (EXP)

**Halaman:** Data Master → Siswa (tombol "Export")
**Fitur:** Export data siswa ke Excel (.xlsx) atau CSV (.csv) dengan filter jenjang

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| EXP-001 | Export siswa ke Excel (.xlsx) — happy path | Equivalence Partitioning | Functional | Terdapat data siswa di jenjang KB | 1. Klik "Export"<br>2. Pilih format `Excel (.xlsx)`<br>3. Klik "Export" | Format: `xlsx` | File `export_siswa_*.xlsx` terdownload, berisi data siswa KB. | High |
| EXP-002 | Export siswa ke CSV (.csv) | Equivalence Partitioning | Functional | Terdapat data siswa | 1. Pilih format `CSV (.csv)`<br>2. Export | Format: `csv` | File CSV terdownload, dapat dibuka di Excel/tools. | Medium |
| EXP-003 | Export siswa — data kosong (tidak ada siswa) | Error Guessing | Functional | Tidak ada siswa di jenjang tertentu | 1. Export dengan jenjang yang kosong | N/A | File tetap terdownload dengan header saja (0 baris data). Atau notifikasi "Tidak ada data untuk diexport." | Low |
| EXP-004 | RBAC: tombol Export tanpa permission `export-data` | Security (dasar) | Security & UI | Login admin tanpa `export-data` | 1. Buka halaman Siswa<br>2. Amati tombol "Export" | N/A | Tombol Export tidak tampil. | High |

---

## 5. Fitur: Export Tagihan (EXT)

**Halaman:** Transaksi → Tagihan - {jenjang} (tombol "Export")
**Fitur:** Export data tagihan ke Excel/CSV

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| EXT-001 | Export tagihan — format Excel | Equivalence Partitioning | Functional | Terdapat data tagihan | 1. Buka halaman Tagihan - KB<br>2. Klik "Export"<br>3. Pilih format xlsx<br>4. Export | N/A | File tagihan terdownload. | High |
| EXT-002 | Export tagihan — filter status dibawa | Equivalence Partitioning | Functional | Filter status "Belum Dibayar" aktif | 1. Set filter status<br>2. Export | Status: Belum Dibayar | File export hanya berisi tagihan dengan status Belum Dibayar. | Medium |
| EXT-003 | RBAC: tombol Export Tagihan tanpa `export-data` | Security (dasar) | Security & UI | Admin tanpa permission export | 1. Buka halaman Tagihan | N/A | Tombol Export tidak tampil. | High |

---

## 6. Fitur: Export Pembayaran (EXB)

**Halaman:** Transaksi → Pembayaran (tombol "Export")
**Fitur:** Export data pembayaran ke Excel/CSV

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| EXB-001 | Export pembayaran — default | Equivalence Partitioning | Functional | Terdapat data pembayaran | 1. Buka halaman Pembayaran<br>2. Klik "Export"<br>3. Pilih format xlsx<br>4. Export | N/A | File export_pembayaran_*.xlsx terdownload. | High |
| EXB-002 | RBAC: tombol Export Pembayaran tanpa `export-data` | Security (dasar) | Security & UI | Admin tanpa permission export | 1. Buka halaman Pembayaran | N/A | Tombol Export tidak tampil. | High |

---

## 7. Fitur: Export Kas Harian (EXK)

**Halaman:** Laporan → Kas Harian (tombol "Export Excel/CSV")
**Fitur:** Export laporan kas harian ke Excel/CSV per bulan & tahun

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| EXK-001 | Export kas harian — format Excel | Equivalence Partitioning | Functional | Ada data kas harian | 1. Buka Kas Harian<br>2. Klik "Export Excel/CSV"<br>3. Pilih bulan & tahun<br>4. Pilih format xlsx<br>5. Export | Bulan: Juli, Tahun: 2026, Format: xlsx | File `export_kas_harian_*.xlsx` terdownload. | High |
| EXK-002 | Export kas harian — format CSV | Equivalence Partitioning | Functional | Ada data kas harian | 1. Export dengan format CSV | Format: csv | File CSV terdownload. | Medium |
| EXK-003 | Export kas harian — bulan tanpa data | Boundary Value Analysis | Functional | Belum ada transaksi di bulan tertentu | 1. Export bulan dengan data kosong | Bulan: 1 (Jan) | File tetap terdownload (mungkin header saja). | Low |
| EXK-004 | Export PDF Kas Harian (fungsi terpisah) | Equivalence Partitioning | Functional | Ada data kas harian | 1. Klik tombol "Export PDF"<br>2. Pilih bulan & tahun<br>3. Export | Bulan: Juli, Tahun: 2026 | File PDF kas harian terdownload. | High |
| EXK-005 | RBAC: tombol Export Excel tanpa `export-laporan` | Security (dasar) | Security & UI | Admin tanpa `export-laporan` | 1. Buka halaman Kas Harian | N/A | Tombol Export Excel/CSV tidak tampil. | High |

---

## 8. Fitur: Export Rekap Bulanan (EXR)

**Halaman:** Laporan → Rekap Bulanan (tombol "Export Excel/CSV")
**Fitur:** Export laporan rekap bulanan ke Excel/CSV per tahun

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| EXR-001 | Export rekap bulanan — Excel | Equivalence Partitioning | Functional | Ada data rekap bulanan | 1. Buka Rekap Bulanan<br>2. Klik Export<br>3. Pilih tahun & format xlsx<br>4. Export | Tahun: 2026, Format: xlsx | File `export_rekap_bulanan_*.xlsx` terdownload. | High |
| EXR-002 | Export rekap bulanan — CSV | Equivalence Partitioning | Functional | Ada data rekap bulanan | 1. Export CSV | Format: csv | File CSV terdownload. | Medium |
| EXR-003 | Export rekap — tahun tanpa data | Error Guessing | Functional | Tidak ada data tahun tertentu | 1. Export tahun tanpa data | Tahun: 2020 | Notifikasi atau file kosong. | Low |
| EXR-004 | RBAC: tombol Export Rekap tanpa `export-laporan` | Security (dasar) | Security & UI | Admin tanpa `export-laporan` | 1. Buka halaman Rekap Bulanan | N/A | Tombol Export tidak tampil. | High |

---

## 9. Fitur: Import Tagihan (IMT)

**Halaman:** Transaksi → Tagihan (via API — belum ada tombol Import di UI CardView, diuji via API langsung)
**Fitur:** Upload file Excel → Preview → Konfirmasi tagihan

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| IMT-001 | Import tagihan via API — file valid | Equivalence Partitioning | Functional | File Excel tagihan valid | 1. POST `/api/import-export/import/tagihan/upload`<br>2. POST `/import/tagihan/confirm` | File valid | Preview success, confirm sukses. | High |
| IMT-002 | Import tagihan — jenis tagihan tidak ditemukan | Error Guessing | Functional | Jenis tagihan yang direferensi di file tidak ada di DB | 1. Upload file dengan jenis tagihan invalid | File: jenis tagihan tidak ada | Preview error: baris masuk error count. | High |
| IMT-003 | RBAC: akses import tagihan tanpa permission `import-data` | Security (dasar) | Security | Token tanpa `import-data` | 1. Coba POST ke endpoint import tagihan | Token tanpa permission | 403 Forbidden. | High |

---

## 10. Fitur: Job Status (JOB)

**Halaman:** N/A (background job, dicek via API callback)
**Fitur:** Polling status export/import job

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| JOB-001 | Cek status job ID yang valid (export selesai) | Equivalence Partitioning | Functional | Export job selesai diproses | 1. GET `/api/import-export/job/{jobId}/status` | Job ID valid, status `completed` | Response: `{"type":"export","status":"completed","download_url":"..."}` | Medium |
| JOB-002 | Cek status job ID yang tidak ditemukan | Error Guessing | Functional | Job ID palsu | 1. GET `/api/import-export/job/FAKE123/status` | Job ID: `FAKE123` | 404: "Job tidak ditemukan." | Low |

---

## Ringkasan

| Sub-Fitur | Jumlah TC | Teknik Utama | Prioritas Tinggi |
|-----------|----------|-------------|-----------------|
| Template Download (TMP) | 3 | EP, EG | 2 |
| Import Siswa (IMS) | 8 | EP, BVA, EG | 3 |
| Riwayat Import & Rollback (RIW) | 7 | EP, BVA, ST, EG | 4 |
| Export Siswa (EXP) | 4 | EP, EG | 2 |
| Export Tagihan (EXT) | 3 | EP, EG | 2 |
| Export Pembayaran (EXB) | 2 | EP, EG | 2 |
| Export Kas Harian (EXK) | 5 | EP, BVA, EG | 3 |
| Export Rekap Bulanan (EXR) | 4 | EP, EG | 2 |
| Import Tagihan (IMT) | 3 | EP, EG | 2 |
| Job Status (JOB) | 2 | EP, EG | 0 |
| **Total** | **41** | - | **24** |

**Keterangan Teknik:**
- **EP** = Equivalence Partitioning
- **BVA** = Boundary Value Analysis
- **ST** = State Transition Testing
- **EG** = Error Guessing
- **Exp** = Exploratory Testing

**Aspek yang Dicakup:**
- Functional: 100% test case
- UI/UX: ~10% (riwayat import modal, tombol visibility)
- Security (dasar): ~20% (RBAC permission checks)
- Usability: ~5% (pesan error, konfirmasi rollback)

---

*Dokumen siap untuk direview. Setelah approval, akan dibuatkan file hasil pengujian (template agregasi tabel hasil kosong) untuk pengisian manual.*
