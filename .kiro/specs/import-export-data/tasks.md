# Implementation Plan: Import & Export Data

## Overview

Implementasi fitur import dan export data secara bulk melalui file Excel (.xlsx) dan CSV (.csv) pada sistem manajemen tagihan sekolah. Menggunakan Laravel Excel (maatwebsite/excel) untuk operasi file, Laravel Queue untuk pemrosesan file besar, dan Filament + Livewire untuk antarmuka frontend.

## Tasks

- [x] 1. Setup project structure, database migrations, dan core models
  - [x] 1.1 Install maatwebsite/excel package dan konfigurasi
    - Jalankan `composer require maatwebsite/excel`
    - Publish config file dan sesuaikan chunk size (500)
    - Konfigurasi temporary file storage di `config/excel.php`
    - _Requirements: 1.7, 1.8, 2.1_

  - [x] 1.2 Buat migration untuk tabel `import_batches`
    - Buat migration dengan kolom: id, batch_reference (UUID unique), user_id (FK), import_type (enum: siswa, tagihan), file_name, total_rows, success_count, error_count, status (enum: processing, completed, failed, rolled_back), error_message, rolled_back_at, rolled_back_by (FK), branch_id (FK), timestamps
    - Tambahkan index pada (branch_id, created_at)
    - _Requirements: 9.1, 2.11, 5.8_

  - [x] 1.3 Buat migration untuk tabel `export_jobs`
    - Buat migration dengan kolom: id, job_reference (UUID unique), user_id (FK), export_type, filters (JSON), format, status (enum: processing, completed, failed), file_path, error_message, expires_at, branch_id (FK), timestamps
    - Tambahkan index pada (branch_id, status)
    - _Requirements: 11.2, 11.3_

  - [x] 1.4 Buat migration untuk menambahkan kolom `batch_reference` pada tabel `siswas` dan `tagihans`
    - ALTER siswas ADD batch_reference CHAR(36) NULL dengan index
    - ALTER tagihans ADD batch_reference CHAR(36) NULL dengan index
    - _Requirements: 10.1, 9.1_

  - [x] 1.5 Buat model `ImportBatch` dengan relationships dan helper methods
    - Definisikan fillable, casts, relationships (user, rolledBackByUser, branch)
    - Implementasi method `isRollbackEligible()`: status completed AND within 48 hours
    - _Requirements: 9.1, 10.2_

  - [x] 1.6 Buat model `ExportJob` dengan relationships dan helper methods
    - Definisikan fillable, casts, relationships (user, branch)
    - Implementasi method `getSignedUrl()` menggunakan `URL::temporarySignedRoute`
    - _Requirements: 11.3_

  - [x] 1.7 Buat DTOs: `ImportPreviewDTO` dan `ExportFilterDTO`
    - `ImportPreviewDTO`: previewId, totalRows, validRows, errorRows, errors array, validData, requiresQueue
    - `ExportFilterDTO`: jenjang, kelasId, status, tahunAjaranId, tanggalMulai, tanggalSelesai, bulan, tahun, format
    - _Requirements: 2.8, 5.5_

  - [x] 1.8 Register permissions `import-data` dan `export-data` di seeder/migration
    - Tambahkan ke `PermissionBinding.php` atau buat seeder baru
    - Pastikan permission terdaftar di spatie/laravel-permission
    - _Requirements: 12.1_

- [x] 2. Checkpoint - Pastikan migrations berjalan dan model terbuat dengan benar
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Implementasi Export Services
  - [x] 3.1 Buat `SiswaExportService` dengan method export, getRecordCount, dan buildQuery
    - Implementasi buildQuery dengan filter: jenjang, kelas_id, status, tahun_ajaran_id
    - Resolve kelas dari SiswaKelas berdasarkan tahun_ajaran_id atau Periode_Aktif
    - Scope ke branch_id user
    - Jika record count > 1000, dispatch queue job; jika ≤1000, return file langsung
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.9, 1.10_

  - [x] 3.2 Buat `SiswaExport` class (Laravel Excel) implementing FromQuery, WithHeadings, WithMapping, WithChunkReading
    - Definisikan headings: NIS, NISN, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, agama, alamat, jenjang, kelas, kategori, status, tahun_diterima, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu, nama_wali, pekerjaan_wali, no_hp_wali
    - Implementasi map() untuk resolve relasi (kelas nama, kategori nama, parent data)
    - Set chunkSize() = 500
    - _Requirements: 1.1, 1.7, 1.8_

  - [x] 3.3 Buat `TagihanExportService` dengan method export, getRecordCount, dan buildQuery
    - Implementasi buildQuery dengan filter: tahun_ajaran_id, jenjang, kelas_id, status
    - Default ke Periode_Aktif jika tahun_ajaran_id tidak diisi
    - Scope ke branch_id user
    - Jika record count > 1000, dispatch queue job; jika ≤1000, return file langsung
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [x] 3.4 Buat `TagihanExport` class (Laravel Excel)
    - Definisikan headings: kode_tagihan, NIS, nama_siswa, jenjang, kelas, jenis_tagihan, jumlah_tagihan, total_sudah_dibayar, sisa_tagihan, status, jatuh_tempo
    - Implementasi map() untuk resolve relasi dan hitung sisa_tagihan (jumlah - tmp)
    - _Requirements: 4.1, 4.5, 4.6_

  - [x] 3.5 Buat `PembayaranExportService` dengan method export, getRecordCount, dan buildQuery
    - Implementasi buildQuery dengan filter: tahun_ajaran_id, tanggal_mulai, tanggal_selesai
    - Default ke Periode_Aktif jika tidak ada filter
    - Scope ke branch_id user
    - Jika record count > 1000, dispatch queue job
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8_

  - [x] 3.6 Buat `PembayaranExport` class (Laravel Excel)
    - Definisikan headings: kode_pembayaran, kode_tagihan, NIS, nama_siswa, jenis_tagihan, tanggal_pembayaran, metode, jumlah_pembayaran, pembayar
    - Implementasi map() untuk resolve relasi (tagihan → siswa, jenis_tagihan)
    - _Requirements: 7.1, 7.6, 7.7_

  - [x] 3.7 Buat `KasExportService` dengan method exportKasHarian dan exportRekapBulanan
    - exportKasHarian: query pembayaran dan pengeluaran per tanggal dalam bulan tertentu
    - exportRekapBulanan: query summary per bulan dalam tahun tertentu
    - Scope ke branch_id user
    - Jika total records > 1000, dispatch queue job
    - _Requirements: 8.5, 8.6, 8.7, 8.8, 8.9, 8.10_

  - [x] 3.8 Buat `KasHarianExport` dan `RekapBulananExport` classes (WithMultipleSheets)
    - KasHarianExport: sheets [RingkasanSheet, PemasukanSheet, PengeluaranSheet]
    - RekapBulananExport: sheets [RingkasanSheet, PemasukanSheet, PengeluaranSheet]
    - Untuk CSV: single file dengan kolom "tipe" (pemasukan/pengeluaran)
    - _Requirements: 8.8, 8.9_

  - [x] 3.9 Write property tests untuk Export Services
    - **Property 1: Branch Isolation** - Verify export hanya mengandung data dari branch user
    - **Property 2: Export Filter Correctness** - Verify semua record match filter criteria
    - **Property 3: Export Data Mapping Integrity** - Verify kolom values match source DB
    - **Property 12: Queue Threshold Correctness** - Verify dispatch queue jika >1000 records
    - **Property 13: Kas Aggregation Consistency** - Verify summary totals = sum of details
    - **Validates: Requirements 1.2, 1.4, 1.9, 1.10, 4.2, 4.4, 4.7, 7.2, 7.5, 7.8, 8.7, 8.10**

- [x] 4. Checkpoint - Pastikan semua export services berfungsi
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implementasi Import Services
  - [x] 5.1 Buat `SiswaImportService` dengan method validate, confirm, dan processInBackground
    - validate(): Parse file, validasi setiap row (required fields, format, referensi), return ImportPreviewDTO
    - Validasi: nis required & numeric max 20, nisn numeric exactly 10 if provided, jenis_kelamin (L/P), jenjang (TK/MI/KB), tanggal_lahir (Y-m-d), agama (predefined list)
    - Check duplicate NIS dalam branch_id yang sama
    - Match kelas column terhadap Kelas records (by nama + jenjang + branch_id)
    - Cache valid data dengan previewId (TTL 1 jam)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.8_

  - [x] 5.2 Implementasi `SiswaImportService::confirm()` untuk insert valid rows
    - Retrieve cached valid data by previewId
    - Dalam database transaction: create Siswa records, create Ayah/Ibu records jika ada parent data, create Wali record jika ada wali data, create SiswaKelas record linking ke Periode_Aktif
    - Assign branch_id dan batch_reference ke setiap record
    - Reject jika tidak ada Periode_Aktif
    - Jika >500 rows, dispatch ProcessImportJob
    - Create ImportBatch record
    - _Requirements: 2.6, 2.7, 2.9, 2.10, 2.11, 2.12_

  - [x] 5.3 Buat `SiswaImportValidator` class (Laravel Excel) implementing ToCollection, WithHeadingRow
    - Implementasi collection() untuk validasi per-row
    - Track valid rows dan errors dengan row number, column, dan message
    - _Requirements: 2.3, 2.4, 2.5_

  - [x] 5.4 Buat `TagihanImportService` dengan method validate, confirm, dan processInBackground
    - validate(): Parse file, validasi setiap row (nis exists in branch, jenis_tagihan exists in branch + tahun_ajaran)
    - Check duplicate combination (NIS + jenis_tagihan) untuk target TahunAjaran
    - Cache valid data dengan previewId (TTL 1 jam)
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 5.5 Implementasi `TagihanImportService::confirm()` untuk create tagihan records
    - Retrieve cached valid data by previewId
    - Dalam database transaction: create Tagihan records dengan auto-generated kode_tagihan, resolve jenis_tagihan_id, set tmp=0, status="Belum Lunas", assign branch_id dan tahun_ajaran_id dari Periode_Aktif
    - Assign batch_reference ke setiap record
    - Reject jika tidak ada Periode_Aktif
    - Jika >500 rows, dispatch ProcessImportJob
    - Create ImportBatch record
    - _Requirements: 5.6, 5.7, 5.8, 5.9_

  - [x] 5.6 Buat `TagihanImportValidator` class (Laravel Excel) implementing ToCollection, WithHeadingRow
    - Implementasi collection() untuk validasi per-row
    - Track valid rows dan errors
    - _Requirements: 5.3, 5.4_

  - [x] 5.7 Write property tests untuk Import Services
    - **Property 4: Import File Validation** - Verify accept/reject berdasarkan extension dan size
    - **Property 5: Import Row Validation Correctness** - Verify error detection per row
    - **Property 6: Import Preview Count Invariant** - Verify total_rows = valid_rows + error_rows
    - **Property 7: Import Confirm Inserts Only Valid Rows** - Verify hanya valid rows yang di-insert
    - **Property 8: Import Batch Metadata Accuracy** - Verify counts match reality
    - **Property 12: Queue Threshold Correctness** - Verify dispatch queue jika >500 rows
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.8, 2.9, 2.11, 5.1, 5.2, 5.3, 5.5, 5.6, 5.8**

- [x] 6. Checkpoint - Pastikan import services berfungsi dengan validasi yang benar
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Implementasi Template Service
  - [x] 7.1 Buat `TemplateService::generateSiswaTemplate()`
    - Buat `SiswaImportTemplate` class implementing FromArray, WithHeadings, WithDataValidation, WithStyles
    - Headers: nis, nisn, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, agama, alamat, jenjang, kelas, kategori, status, tahun_diterima, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu, nama_wali, pekerjaan_wali, no_hp_wali, alamat_wali
    - Satu contoh data row
    - Dropdown validation: jenis_kelamin (L, P), jenjang (TK, MI, KB), agama (6 pilihan), status (Aktif, Non-Aktif, Lulus, Pindah)
    - Dropdown kelas: query Kelas by branch_id user (dynamic)
    - Notes sheet menjelaskan format setiap kolom
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 7.2 Buat `TemplateService::generateTagihanTemplate()`
    - Buat `TagihanImportTemplate` class implementing FromArray, WithHeadings, WithDataValidation, WithMultipleSheets
    - Main sheet headers: nis, nama_siswa, jenis_tagihan
    - Satu contoh data row
    - Dropdown validation: jenis_tagihan dari JenisTagihan records (by branch_id + Periode_Aktif)
    - Reference sheet: daftar semua JenisTagihan yang tersedia
    - _Requirements: 6.1, 6.2, 6.3_

- [x] 8. Implementasi Import Batch Service (History & Rollback)
  - [x] 8.1 Buat `ImportBatchService::getHistory()`
    - Query ImportBatch records by branch_id, sorted by created_at DESC, paginated (default 15)
    - Eager load user relationship untuk display nama
    - _Requirements: 9.2, 9.3_

  - [x] 8.2 Buat `ImportBatchService::rollback()`
    - Implementasi `canRollback()`: check status "completed", within 48 hours, no dependent records
    - Untuk type "siswa": check apakah imported siswa punya tagihan setelah import
    - Untuk type "tagihan": check apakah imported tagihan punya pembayaran
    - Dalam database transaction: delete semua records dengan matching batch_reference
    - Update ImportBatch: status → "rolled_back", set rolled_back_at dan rolled_back_by
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_

  - [x] 8.3 Write property tests untuk Import Batch Service
    - **Property 9: Rollback Completeness** - Verify semua records dengan batch_reference dihapus
    - **Property 10: Rollback Eligibility** - Verify eligibility rules (status, 48h, no dependents)
    - **Property 14: Import History Branch Scoping** - Verify history scoped ke branch dan sorted DESC
    - **Validates: Requirements 9.2, 10.1, 10.2, 10.3, 10.4, 10.5, 10.6**

- [x] 9. Implementasi Queue Jobs
  - [x] 9.1 Buat `ProcessImportJob` (ShouldQueue)
    - Properties: tries = 3, timeout = 300
    - Constructor: previewId, importType, branchId, userId, batchId
    - handle(): Retrieve cached data, process import berdasarkan type (siswa/tagihan), update ImportBatch status ke "completed"
    - failed(): Update ImportBatch status ke "failed" dengan error message
    - _Requirements: 11.1, 11.4, 11.5_

  - [x] 9.2 Buat `ProcessExportJob` (ShouldQueue)
    - Properties: tries = 3, timeout = 600
    - Constructor: exportType, filters, format, branchId, jobReferenceId
    - handle(): Generate file berdasarkan type, simpan ke storage/app/exports, update ExportJob dengan file_path dan expires_at (24 jam)
    - failed(): Update ExportJob status ke "failed" dengan error message
    - _Requirements: 11.2, 11.3, 11.5_

  - [x] 9.3 Write unit tests untuk Queue Jobs
    - Test ProcessImportJob dispatches correctly dan updates batch status
    - Test ProcessExportJob generates file dan stores signed URL
    - Test failure handling (3 retries, status update ke "failed")
    - _Requirements: 11.1, 11.2, 11.4, 11.5_

- [x] 10. Checkpoint - Pastikan queue jobs, rollback, dan template berfungsi
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Implementasi Controller dan Routes
  - [x] 11.1 Buat Form Request classes
    - `ImportUploadRequest`: file required, mimes:xlsx,csv, max:5120
    - `ImportConfirmRequest`: preview_id required, string, uuid
    - `ExportSiswaRequest`: format required in:xlsx,csv; jenjang nullable in:TK,MI,KB; kelas_id nullable integer exists:kelas,id; status nullable; tahun_ajaran_id nullable integer
    - `ExportTagihanRequest`: format required; tahun_ajaran_id, jenjang, kelas_id, status nullable
    - `ExportPembayaranRequest`: format required; tahun_ajaran_id, tanggal_mulai, tanggal_selesai nullable
    - `ExportKasRequest`: format required; bulan required integer between:1,12; tahun required integer digits:4
    - `ExportRekapRequest`: format required; tahun required integer digits:4
    - _Requirements: 2.1, 2.2, 5.1, 5.2_

  - [x] 11.2 Buat `ImportExportController` dengan semua endpoint methods
    - Export: exportSiswa, exportTagihan, exportPembayaran, exportKasHarian, exportRekapBulanan
    - Import: uploadSiswa, confirmSiswa, uploadTagihan, confirmTagihan
    - Template: templateSiswa, templateTagihan
    - History & Rollback: importHistory, rollbackImport
    - Job Status: jobStatus
    - Inject services via constructor
    - _Requirements: 1.1-1.10, 2.1-2.12, 4.1-4.7, 5.1-5.9, 7.1-7.8, 8.4-8.11_

  - [x] 11.3 Definisikan routes dengan middleware permission
    - Group prefix `import-export` dengan auth:sanctum
    - Export routes: middleware `permission:export-data`
    - Import routes: middleware `permission:import-data`
    - Job status: accessible dengan either permission
    - Tambahkan route untuk kas harian detail endpoint (paginated, default 20/page)
    - _Requirements: 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 8.4, 8.11_

  - [x] 11.4 Write property test untuk Permission Enforcement
    - **Property 11: Permission Enforcement** - Verify 403 tanpa permission, success dengan permission
    - **Validates: Requirements 12.2, 12.3, 12.4, 12.7**

- [x] 12. Implementasi Frontend (Filament + Livewire)
  - [x] 12.1 Buat Filament custom page `ImportExportPage` dengan navigasi dan tabs
    - Menu item "Import & Export" di main navigation
    - Visible hanya untuk user dengan permission import-data ATAU export-data
    - Tabs/sections: Import Siswa, Import Tagihan, Export Siswa, Export Tagihan, Export Pembayaran, Export Kas, Riwayat Import
    - _Requirements: 13.1, 13.2, 12.8, 12.9_

  - [x] 12.2 Buat Livewire component `ImportWizard` untuk step-by-step import flow
    - Step 1: Download template button
    - Step 2: File upload area (drag-and-drop, accept .xlsx/.csv, max 5MB client-side validation)
    - Step 3: Preview results (valid rows green, error rows red dengan error message per row)
    - Step 4: Confirm atau Cancel buttons
    - Client-side validation: reject file >5MB atau extension salah sebelum upload
    - Loading indicator "Sedang diproses..." saat queue processing
    - Disable submit button saat processing
    - _Requirements: 13.3, 13.4, 13.5, 13.7, 13.8, 13.9_

  - [x] 12.3 Buat Livewire component `ExportForm` untuk export dengan filter
    - Filter options per export type:
      - Siswa: jenjang, kelas, status
      - Tagihan: tahun_ajaran, jenjang, kelas, status
      - Pembayaran: date range, tahun_ajaran
      - Kas Harian: bulan, tahun
      - Rekap Bulanan: tahun
    - Format selector: xlsx atau csv
    - Loading indicator saat processing
    - _Requirements: 13.6, 13.9_

  - [x] 12.4 Buat Livewire component `ImportHistoryTable` untuk riwayat import
    - Tabel: import_type, file_name, user name, total_rows, success_count, error_count, status, created_at
    - Rollback button pada eligible rows (status completed, within 48h) dengan confirmation dialog
    - _Requirements: 9.3, 10.7, 13.2_

  - [x] 12.5 Buat Livewire component `JobStatusIndicator` untuk monitoring queue jobs
    - Tampilkan progress indicator untuk jobs dengan status "processing"
    - Notification saat job complete dengan link download/view results
    - Poll status endpoint secara periodik
    - _Requirements: 11.6, 11.7_

  - [x] 12.6 Buat halaman detail Kas Harian dan Rekap Bulanan
    - Kas Harian: tampilkan rincian pemasukan dan pengeluaran per tanggal
    - Klik row summary → expand/navigate ke detail records
    - Rekap Bulanan: klik bulan → tampilkan detail pemasukan dan pengeluaran
    - Pagination default 20 records per page
    - _Requirements: 8.1, 8.2, 8.3, 8.11_

  - [x] 12.7 Write unit tests untuk frontend components
    - Test ImportWizard step flow
    - Test ExportForm filter rendering
    - Test ImportHistoryTable rollback button visibility
    - Test permission-based UI visibility
    - _Requirements: 13.1-13.9, 12.8, 12.9_

- [x] 13. Checkpoint - Pastikan frontend terintegrasi dengan backend
  - Ensure all tests pass, ask the user if questions arise.

- [x] 14. Integration wiring dan final cleanup
  - [x] 14.1 Wire semua komponen: register service providers, bind interfaces
    - Register ImportExport services di AppServiceProvider atau dedicated ServiceProvider
    - Pastikan queue worker configured untuk handle ProcessImportJob dan ProcessExportJob
    - Konfigurasi storage disk untuk export files (storage/app/exports)
    - Tambahkan cleanup command untuk expired export files (>24 jam)
    - _Requirements: 11.1-11.7_

  - [x] 14.2 Update ImportBatchService untuk handle queue status updates
    - Pastikan status transitions: processing → completed/failed
    - Pastikan error_message tersimpan saat job gagal setelah 3 retries
    - _Requirements: 9.4, 9.5, 11.4, 11.5_

  - [x] 14.3 Write integration tests untuk full import/export flows
    - Test full import flow: upload → preview → confirm → verify DB records
    - Test full export flow: request → (queue if large) → download file → verify content
    - Test rollback flow: import → rollback → verify records deleted
    - Test permission enforcement end-to-end
    - _Requirements: 1.1-1.10, 2.1-2.12, 4.1-4.7, 5.1-5.9, 10.1-10.6, 12.1-12.9_

- [x] 15. Final checkpoint - Pastikan semua fitur terintegrasi dan tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties (14 properties defined in design)
- Unit tests validate specific examples and edge cases
- Menggunakan `maatwebsite/excel` untuk semua operasi file Excel/CSV
- Queue threshold: >500 rows untuk import, >1000 records untuk export
- Import preview data di-cache selama 1 jam menggunakan Laravel Cache
- Export files disimpan di storage/app/exports dengan signed URL valid 24 jam
- Semua operasi di-scope ke branch_id user yang terautentikasi

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "1.4", "1.7", "1.8"] },
    { "id": 1, "tasks": ["1.5", "1.6"] },
    { "id": 2, "tasks": ["3.1", "3.3", "3.5", "3.7"] },
    { "id": 3, "tasks": ["3.2", "3.4", "3.6", "3.8"] },
    { "id": 4, "tasks": ["3.9", "5.1", "5.4"] },
    { "id": 5, "tasks": ["5.2", "5.3", "5.5", "5.6"] },
    { "id": 6, "tasks": ["5.7", "7.1", "7.2"] },
    { "id": 7, "tasks": ["8.1", "8.2"] },
    { "id": 8, "tasks": ["8.3", "9.1", "9.2"] },
    { "id": 9, "tasks": ["9.3", "11.1"] },
    { "id": 10, "tasks": ["11.2", "11.3"] },
    { "id": 11, "tasks": ["11.4", "12.1"] },
    { "id": 12, "tasks": ["12.2", "12.3", "12.4", "12.5", "12.6"] },
    { "id": 13, "tasks": ["12.7", "14.1", "14.2"] },
    { "id": 14, "tasks": ["14.3"] }
  ]
}
```
