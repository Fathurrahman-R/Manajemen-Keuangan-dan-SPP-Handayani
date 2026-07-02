# Requirements Document

## Introduction

Fitur ini menyediakan kemampuan **import dan export data** secara bulk melalui file Excel (.xlsx) dan CSV (.csv) pada sistem manajemen tagihan sekolah. Fitur mencakup:

1. Export data siswa dengan filter (jenjang, kelas, status) ke Excel/CSV
2. Import data siswa dari file Excel/CSV dengan validasi dan preview
3. Export data tagihan dengan filter (tahun ajaran, jenjang, kelas, status)
4. Import tagihan secara bulk dari file Excel/CSV
5. Export data pembayaran untuk pelaporan dan rekonsiliasi
6. Manajemen template import (download template dengan header dan contoh data)
7. Riwayat dan logging import (siapa, kapan, berapa record, rollback)
8. Pembatasan akses berdasarkan permission dan branch

Fitur ini bergantung pada `periode-tahun-ajaran` karena data yang diimport/export harus di-scope ke tahun ajaran tertentu. File besar diproses melalui queue untuk menghindari timeout.

## Glossary

- **Import_Service**: Komponen backend yang memproses file upload, melakukan validasi, dan menyimpan data ke database
- **Export_Service**: Komponen backend yang mengambil data dari database dan menghasilkan file Excel/CSV untuk diunduh
- **Backend_API**: Laravel API yang menyediakan endpoint untuk operasi import dan export
- **Frontend_App**: Aplikasi Laravel + Livewire + Filament yang menampilkan antarmuka pengguna untuk import/export
- **Admin_Operator**: Pengguna dengan role admin atau operator yang melakukan operasi import/export
- **Import_File**: File Excel (.xlsx) atau CSV (.csv) yang diunggah oleh Admin_Operator untuk diproses
- **Export_File**: File Excel (.xlsx) atau CSV (.csv) yang dihasilkan oleh sistem untuk diunduh
- **Import_Template**: File Excel yang berisi header kolom, contoh data, dan validasi dropdown sebagai panduan pengisian data import
- **Import_Preview**: Tampilan hasil validasi file import sebelum data di-commit ke database, menampilkan baris valid dan baris bermasalah
- **Import_Batch**: Satu operasi import yang mencakup metadata (user, waktu, jumlah record, status) dan referensi ke record yang dibuat
- **Branch**: Cabang sekolah; semua operasi import/export di-scope ke branch pengguna yang terautentikasi
- **TahunAjaran**: Periode tahun ajaran yang menjadi scope data tagihan dan penempatan kelas
- **Periode_Aktif**: TahunAjaran dengan status "Aktif" untuk branch tertentu
- **Siswa**: Entitas murid dengan NIS, nama, jenjang, kelas, dan data orang tua
- **Tagihan**: Record kewajiban pembayaran siswa yang terikat ke TahunAjaran
- **JenisTagihan**: Template/tipe tagihan dengan nama, jumlah, dan jatuh tempo
- **Pembayaran**: Record pembayaran yang dilakukan terhadap tagihan
- **Queue_Job**: Proses background (Laravel Queue) untuk memproses file import/export berukuran besar

## Requirements

### Requirement 1: Export Data Siswa

**User Story:** Sebagai Admin_Operator, saya ingin mengekspor data siswa ke file Excel atau CSV, sehingga saya dapat menggunakan data tersebut untuk pelaporan, arsip, atau keperluan administrasi lainnya.

#### Acceptance Criteria

1. WHEN the Admin_Operator requests a siswa export, THE Export_Service SHALL generate a file containing the following columns: NIS, NISN, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, agama, alamat, jenjang, kelas (nama kelas), kategori (nama kategori), status, tahun_diterima, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu, nama_wali, pekerjaan_wali, no_hp_wali
2. WHEN the Admin_Operator specifies filter parameters (jenjang, kelas_id, status), THE Export_Service SHALL include only Siswa records matching all specified filter criteria
3. WHEN no filter parameters are specified, THE Export_Service SHALL export all Siswa records belonging to the authenticated user's branch
4. THE Export_Service SHALL scope all exported Siswa data to the authenticated user's branch_id
5. WHEN the Admin_Operator specifies a tahun_ajaran_id filter, THE Export_Service SHALL resolve each Siswa's kelas from the SiswaKelas record matching the specified TahunAjaran
6. WHEN no tahun_ajaran_id filter is specified, THE Export_Service SHALL resolve each Siswa's kelas from the SiswaKelas record matching the Periode_Aktif of the authenticated user's branch
7. WHEN the Admin_Operator selects "xlsx" as the format, THE Export_Service SHALL generate a valid .xlsx file with proper column headers in the first row
8. WHEN the Admin_Operator selects "csv" as the format, THE Export_Service SHALL generate a valid UTF-8 encoded .csv file with comma separators and proper column headers in the first row
9. WHEN the export contains more than 1000 records, THE Backend_API SHALL process the export via Queue_Job and return a download link upon completion
10. WHEN the export contains 1000 records or fewer, THE Backend_API SHALL process the export synchronously and return the file as a direct download response

### Requirement 2: Import Data Siswa

**User Story:** Sebagai Admin_Operator, saya ingin mengimpor data siswa dari file Excel atau CSV, sehingga saya dapat mendaftarkan banyak siswa sekaligus tanpa input manual satu per satu.

#### Acceptance Criteria

1. WHEN the Admin_Operator uploads an Import_File for siswa, THE Import_Service SHALL accept files with extension .xlsx or .csv and reject files with other extensions by returning an error message indicating the accepted formats
2. WHEN the Admin_Operator uploads an Import_File, THE Import_Service SHALL reject files larger than 5MB and return an error message indicating the maximum file size
3. WHEN the Import_File is accepted, THE Import_Service SHALL validate each row for: required fields (nis, nama, jenis_kelamin, jenjang), NIS format (numeric, max 20 characters), NISN format (numeric, exactly 10 digits) if provided, jenis_kelamin value (L or P), jenjang value (TK, MI, or KB), tanggal_lahir format (YYYY-MM-DD), and agama value matching the predefined list (Islam, Kristen, Katolik, Hindu, Buddha, Konghucu)
4. WHEN the Import_File contains a NIS that already exists within the same branch_id, THE Import_Service SHALL mark that row as a duplicate error in the Import_Preview
5. WHEN the Import_File contains a kelas column value, THE Import_Service SHALL match the value against existing Kelas records (by nama and jenjang) within the same branch_id, and mark the row as an error if no matching Kelas is found
6. WHEN the Import_File contains parent data columns (nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu), THE Import_Service SHALL create new Ayah and Ibu records for each Siswa row that has parent data filled
7. WHEN the Import_File contains wali data columns (nama_wali, pekerjaan_wali, no_hp_wali, alamat_wali), THE Import_Service SHALL create a new Wali record for each Siswa row that has wali data filled
8. AFTER validation completes, THE Import_Service SHALL return an Import_Preview containing: total rows, valid rows count, error rows count, and a list of error details (row number, column, error message) for each invalid row
9. WHEN the Admin_Operator confirms the import after reviewing the Import_Preview, THE Import_Service SHALL insert only the valid rows into the database, assign each Siswa to the authenticated user's branch_id, and create a SiswaKelas record linking each Siswa to the specified kelas and the Periode_Aktif
10. IF no Periode_Aktif exists for the authenticated user's branch when confirming the import, THEN THE Import_Service SHALL reject the confirmation and return an error message indicating that an active period must be set first
11. WHEN the import is confirmed, THE Import_Service SHALL create an Import_Batch record storing: user_id, timestamp, file_name, total_rows, success_count, error_count, and import_type ("siswa")
12. WHEN the Import_File contains more than 500 rows, THE Import_Service SHALL process the import via Queue_Job and notify the Admin_Operator upon completion

### Requirement 3: Download Template Import Siswa

**User Story:** Sebagai Admin_Operator, saya ingin mengunduh template Excel untuk import siswa, sehingga saya mengetahui format dan kolom yang diperlukan.

#### Acceptance Criteria

1. WHEN the Admin_Operator requests the siswa import template, THE Backend_API SHALL return an .xlsx file containing: column headers in the first row (nis, nisn, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, agama, alamat, jenjang, kelas, kategori, status, tahun_diterima, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu, nama_wali, pekerjaan_wali, no_hp_wali, alamat_wali), one example data row in the second row, and a notes row or separate sheet explaining each column's format and constraints
2. THE Import_Template SHALL include Excel data validation (dropdown list) for the following columns: jenis_kelamin (L, P), jenjang (TK, MI, KB), agama (Islam, Kristen, Katolik, Hindu, Buddha, Konghucu), and status (Aktif, Non-Aktif, Lulus, Pindah)
3. THE Import_Template SHALL include the list of available Kelas names (filtered by the authenticated user's branch_id) as a dropdown validation on the kelas column
4. THE Backend_API SHALL generate the template dynamically to reflect the current Kelas data for the authenticated user's branch

### Requirement 4: Export Data Tagihan

**User Story:** Sebagai Admin_Operator, saya ingin mengekspor data tagihan ke file Excel atau CSV, sehingga saya dapat melakukan rekonsiliasi, pelaporan, atau analisis data tagihan.

#### Acceptance Criteria

1. WHEN the Admin_Operator requests a tagihan export, THE Export_Service SHALL generate a file containing the following columns: kode_tagihan, NIS, nama_siswa, jenjang, kelas, jenis_tagihan (nama), jumlah_tagihan, total_sudah_dibayar (tmp), sisa_tagihan (jumlah - tmp), status, jatuh_tempo
2. WHEN the Admin_Operator specifies filter parameters (tahun_ajaran_id, jenjang, kelas_id, status), THE Export_Service SHALL include only Tagihan records matching all specified filter criteria
3. WHEN no tahun_ajaran_id filter is specified, THE Export_Service SHALL default to exporting Tagihan belonging to the Periode_Aktif of the authenticated user's branch
4. THE Export_Service SHALL scope all exported Tagihan data to the authenticated user's branch_id
5. WHEN the Admin_Operator selects "xlsx" as the format, THE Export_Service SHALL generate a valid .xlsx file with proper column headers in the first row
6. WHEN the Admin_Operator selects "csv" as the format, THE Export_Service SHALL generate a valid UTF-8 encoded .csv file with comma separators and proper column headers in the first row
7. WHEN the export contains more than 1000 records, THE Backend_API SHALL process the export via Queue_Job and return a download link upon completion

### Requirement 5: Import Data Tagihan

**User Story:** Sebagai Admin_Operator, saya ingin mengimpor tagihan secara bulk dari file Excel atau CSV, sehingga saya dapat membuat banyak tagihan sekaligus untuk siswa di awal periode.

#### Acceptance Criteria

1. WHEN the Admin_Operator uploads an Import_File for tagihan, THE Import_Service SHALL accept files with extension .xlsx or .csv and reject files with other extensions by returning an error message indicating the accepted formats
2. WHEN the Admin_Operator uploads an Import_File, THE Import_Service SHALL reject files larger than 5MB and return an error message indicating the maximum file size
3. WHEN the Import_File is accepted, THE Import_Service SHALL validate each row for: required fields (nis, jenis_tagihan), NIS exists in the Siswa table within the same branch_id, and jenis_tagihan matches an existing JenisTagihan record (by nama) within the same branch_id and the target TahunAjaran
4. WHEN the Import_File contains a row where the combination of NIS and jenis_tagihan already has an existing Tagihan record for the target TahunAjaran, THE Import_Service SHALL mark that row as a duplicate error in the Import_Preview
5. AFTER validation completes, THE Import_Service SHALL return an Import_Preview containing: total rows, valid rows count, error rows count, and a list of error details (row number, column, error message) for each invalid row
6. WHEN the Admin_Operator confirms the tagihan import after reviewing the Import_Preview, THE Import_Service SHALL create Tagihan records for valid rows with: auto-generated kode_tagihan, jenis_tagihan_id resolved from the nama, nis from the file, tmp set to 0, status set to "Belum Lunas", branch_id from the authenticated user, and tahun_ajaran_id from the Periode_Aktif
7. IF no Periode_Aktif exists for the authenticated user's branch when confirming the tagihan import, THEN THE Import_Service SHALL reject the confirmation and return an error message indicating that an active period must be set first
8. WHEN the tagihan import is confirmed, THE Import_Service SHALL create an Import_Batch record storing: user_id, timestamp, file_name, total_rows, success_count, error_count, and import_type ("tagihan")
9. WHEN the Import_File contains more than 500 rows, THE Import_Service SHALL process the import via Queue_Job and notify the Admin_Operator upon completion

### Requirement 6: Download Template Import Tagihan

**User Story:** Sebagai Admin_Operator, saya ingin mengunduh template Excel untuk import tagihan, sehingga saya mengetahui format kolom yang diperlukan dan mapping jenis tagihan yang tersedia.

#### Acceptance Criteria

1. WHEN the Admin_Operator requests the tagihan import template, THE Backend_API SHALL return an .xlsx file containing: column headers in the first row (nis, nama_siswa, jenis_tagihan), one example data row in the second row, and a reference sheet listing all available JenisTagihan names for the Periode_Aktif of the authenticated user's branch
2. THE Import_Template SHALL include Excel data validation (dropdown list) for the jenis_tagihan column populated with JenisTagihan names from the Periode_Aktif of the authenticated user's branch
3. THE Backend_API SHALL generate the template dynamically to reflect the current JenisTagihan data for the authenticated user's branch and Periode_Aktif

### Requirement 7: Export Data Pembayaran

**User Story:** Sebagai Admin_Operator, saya ingin mengekspor data pembayaran ke file Excel atau CSV, sehingga saya dapat melakukan rekonsiliasi keuangan dan membuat laporan pembayaran.

#### Acceptance Criteria

1. WHEN the Admin_Operator requests a pembayaran export, THE Export_Service SHALL generate a file containing the following columns: kode_pembayaran, kode_tagihan, NIS, nama_siswa, jenis_tagihan, tanggal_pembayaran, metode, jumlah_pembayaran, pembayar
2. WHEN the Admin_Operator specifies a date range filter (tanggal_mulai, tanggal_selesai), THE Export_Service SHALL include only Pembayaran records with tanggal between tanggal_mulai and tanggal_selesai (inclusive)
3. WHEN the Admin_Operator specifies a tahun_ajaran_id filter, THE Export_Service SHALL include only Pembayaran records whose associated Tagihan belongs to the specified TahunAjaran
4. WHEN no tahun_ajaran_id filter and no date range filter are specified, THE Export_Service SHALL default to exporting Pembayaran records whose associated Tagihan belongs to the Periode_Aktif of the authenticated user's branch
5. THE Export_Service SHALL scope all exported Pembayaran data to the authenticated user's branch_id
6. WHEN the Admin_Operator selects "xlsx" as the format, THE Export_Service SHALL generate a valid .xlsx file with proper column headers in the first row
7. WHEN the Admin_Operator selects "csv" as the format, THE Export_Service SHALL generate a valid UTF-8 encoded .csv file with comma separators and proper column headers in the first row
8. WHEN the export contains more than 1000 records, THE Backend_API SHALL process the export via Queue_Job and return a download link upon completion

### Requirement 8: Rincian Kas Harian dan Rekap Bulanan (Tampilan & Export)

**User Story:** Sebagai Admin_Operator, saya ingin melihat dan mengekspor rincian pemasukan dan pengeluaran pada laporan kas harian dan rekap bulanan, sehingga saya dapat melakukan audit keuangan, verifikasi transaksi, dan dokumentasi yang lebih detail.

#### Acceptance Criteria

1. WHEN the Admin_Operator views the kas harian page for a specific date, THE Frontend_App SHALL display a detail section showing rincian pemasukan (kode_pembayaran, kode_tagihan, NIS, nama_siswa, jenis_tagihan, metode, jumlah, pembayar) and rincian pengeluaran (uraian, jumlah) for that date
2. WHEN the Admin_Operator clicks on a row in the kas harian summary table, THE Frontend_App SHALL expand or navigate to show the detailed pemasukan and pengeluaran records for that specific date
3. WHEN the Admin_Operator views the rekap bulanan page and clicks on a specific month row, THE Frontend_App SHALL display the detailed pemasukan and pengeluaran records for that month
4. THE Backend_API SHALL provide an endpoint that returns rincian pemasukan (Pembayaran records with related Tagihan, Siswa, and JenisTagihan data) and rincian pengeluaran (Pengeluaran records) filtered by date or month, scoped to the authenticated user's branch_id
5. WHEN the Admin_Operator requests a kas harian export with parameters bulan and tahun, THE Export_Service SHALL generate a file containing two sections: rincian pemasukan (kode_pembayaran, kode_tagihan, NIS, nama_siswa, jenis_tagihan, tanggal, metode, jumlah, pembayar) and rincian pengeluaran (id, tanggal, uraian, jumlah) for each date in the specified month
6. WHEN the Admin_Operator requests a rekap bulanan export with parameter tahun, THE Export_Service SHALL generate a file containing a monthly summary (bulan, total_pemasukan, total_pengeluaran, saldo) and separate sheets for rincian pemasukan and rincian pengeluaran aggregated per month
7. THE Export_Service SHALL scope all kas harian and rekap bulanan export data to the authenticated user's branch_id
8. WHEN the Admin_Operator selects "xlsx" as the format for kas export, THE Export_Service SHALL generate a valid .xlsx file with multiple sheets: "Ringkasan" for the summary, "Rincian Pemasukan" for detailed income records, and "Rincian Pengeluaran" for detailed expense records
9. WHEN the Admin_Operator selects "csv" as the format for kas export, THE Export_Service SHALL generate a single .csv file containing all records with a "tipe" column indicating "pemasukan" or "pengeluaran" for each row
10. WHEN the export contains more than 1000 records total (pemasukan + pengeluaran combined), THE Backend_API SHALL process the export via Queue_Job and return a download link upon completion
11. THE Backend_API SHALL support pagination on the rincian endpoint with a default of 20 records per page for the detail view display

### Requirement 9: Riwayat dan Logging Import

**User Story:** Sebagai Admin_Operator, saya ingin melihat riwayat semua operasi import yang pernah dilakukan, sehingga saya dapat melacak siapa yang mengimpor data, kapan, dan berapa record yang berhasil/gagal.

#### Acceptance Criteria

1. THE Backend_API SHALL store each Import_Batch with the following attributes: id (auto-increment), user_id (foreign key), import_type (enum: "siswa", "tagihan"), file_name (string), total_rows (integer), success_count (integer), error_count (integer), status (enum: "completed", "processing", "failed"), batch_reference (UUID for linking created records), created_at (timestamp), and branch_id (foreign key)
2. WHEN the Admin_Operator requests the import history list, THE Backend_API SHALL return Import_Batch records scoped to the authenticated user's branch_id, sorted by created_at descending, with pagination (default 15 per page)
3. THE Frontend_App SHALL display the import history as a table showing: import_type, file_name, user name (who performed), total_rows, success_count, error_count, status, and created_at
4. WHEN an import is processed via Queue_Job, THE Import_Service SHALL update the Import_Batch status from "processing" to "completed" upon successful completion, or to "failed" if an unrecoverable error occurs
5. IF an import via Queue_Job fails, THEN THE Import_Service SHALL store the error message in the Import_Batch record and set the status to "failed"

### Requirement 10: Rollback Import

**User Story:** Sebagai Admin_Operator, saya ingin dapat membatalkan (rollback) operasi import yang baru dilakukan, sehingga saya dapat memperbaiki kesalahan tanpa harus menghapus data satu per satu.

#### Acceptance Criteria

1. WHEN the Admin_Operator requests a rollback for an Import_Batch, THE Import_Service SHALL delete all records that were created by that Import_Batch (identified by batch_reference) within a single database transaction
2. THE Backend_API SHALL allow rollback only for Import_Batch records with status "completed" and created within the last 48 hours
3. IF the Admin_Operator requests a rollback for an Import_Batch older than 48 hours, THEN THE Backend_API SHALL reject the request and return an error message indicating that the rollback window has expired
4. IF the Admin_Operator requests a rollback for an Import_Batch of type "siswa" and any of the imported Siswa records have associated Tagihan records created after the import, THEN THE Import_Service SHALL reject the rollback and return an error message listing the affected NIS numbers
5. IF the Admin_Operator requests a rollback for an Import_Batch of type "tagihan" and any of the imported Tagihan records have associated Pembayaran records, THEN THE Import_Service SHALL reject the rollback and return an error message listing the affected kode_tagihan values
6. WHEN a rollback is successfully completed, THE Import_Service SHALL update the Import_Batch status to "rolled_back" and record the rollback timestamp and user_id who performed the rollback
7. THE Frontend_App SHALL display a "Rollback" button on each eligible Import_Batch row (status "completed", within 48 hours) with a confirmation dialog before executing

### Requirement 11: Pemrosesan File Besar via Queue

**User Story:** Sebagai Admin_Operator, saya ingin file import/export yang besar diproses di background, sehingga saya tidak perlu menunggu dan browser tidak timeout.

#### Acceptance Criteria

1. WHEN an import file contains more than 500 rows, THE Backend_API SHALL dispatch a Queue_Job to process the file and return an HTTP 202 response with the Import_Batch id and status "processing"
2. WHEN an export request will generate more than 1000 records, THE Backend_API SHALL dispatch a Queue_Job to generate the file and return an HTTP 202 response with a job reference id
3. WHEN a Queue_Job for export completes, THE Export_Service SHALL store the generated file in a temporary storage location accessible via a signed URL valid for 24 hours
4. WHEN a Queue_Job for import completes successfully, THE Import_Service SHALL update the Import_Batch status to "completed" and store the success_count and error_count
5. IF a Queue_Job fails after 3 retry attempts, THEN THE Backend_API SHALL mark the job as "failed", update the Import_Batch status to "failed" with the error message, and log the failure details
6. THE Frontend_App SHALL display the processing status of ongoing import/export jobs, showing a progress indicator for jobs with status "processing"
7. WHEN a Queue_Job completes (import or export), THE Frontend_App SHALL display a notification to the Admin_Operator indicating completion with a link to view results or download the file

### Requirement 12: Permission dan Keamanan Import/Export

**User Story:** Sebagai Admin_Operator, saya ingin akses ke fitur import dan export dibatasi berdasarkan permission, sehingga hanya pengguna yang berwenang yang dapat melakukan operasi bulk data.

#### Acceptance Criteria

1. THE Backend_API SHALL register two new permissions in the spatie/laravel-permission system: "import-data" for all import operations and "export-data" for all export operations
2. WHEN a request to perform an import operation (upload, preview, confirm, rollback) is received, THE Backend_API SHALL verify that the authenticated user has the "import-data" permission before processing the request
3. WHEN a request to perform an export operation (export siswa, export tagihan, export pembayaran) is received, THE Backend_API SHALL verify that the authenticated user has the "export-data" permission before processing the request
4. IF the authenticated user does not have the required permission, THEN THE Backend_API SHALL return an HTTP 403 response with a JSON body containing an error message indicating insufficient permission
5. THE Backend_API SHALL scope all import operations to the authenticated user's branch_id, ensuring imported data is assigned to the correct branch
6. THE Backend_API SHALL scope all export operations to the authenticated user's branch_id, ensuring only data from the user's branch is included in the export
7. WHEN a request to download an Import_Template is received, THE Backend_API SHALL verify that the authenticated user has the "import-data" permission before generating the template
8. IF the authenticated user does not have the "import-data" permission, THEN THE Frontend_App SHALL hide the import-related menu items and buttons from the interface
9. IF the authenticated user does not have the "export-data" permission, THEN THE Frontend_App SHALL hide the export-related menu items and buttons from the interface

### Requirement 13: Antarmuka Frontend Import/Export

**User Story:** Sebagai Admin_Operator, saya ingin antarmuka yang jelas dan mudah digunakan untuk melakukan import dan export data, sehingga saya dapat menyelesaikan operasi bulk dengan cepat dan tanpa kebingungan.

#### Acceptance Criteria

1. THE Frontend_App SHALL provide an "Import & Export" menu item in the main navigation, accessible to users with either "import-data" or "export-data" permission
2. THE Frontend_App SHALL display the import/export page with separate tabs or sections for: Import Siswa, Import Tagihan, Export Siswa, Export Tagihan, Export Pembayaran, Export Kas, and Riwayat Import
3. WHEN the Admin_Operator initiates a siswa import, THE Frontend_App SHALL display a step-by-step flow: (1) download template, (2) upload file, (3) review preview with validation results, (4) confirm or cancel
4. WHEN the Admin_Operator initiates a tagihan import, THE Frontend_App SHALL display a step-by-step flow: (1) download template, (2) upload file, (3) review preview with validation results, (4) confirm or cancel
5. WHEN the Import_Preview is displayed, THE Frontend_App SHALL show valid rows highlighted in green and error rows highlighted in red with the specific error message for each row
6. WHEN the Admin_Operator initiates an export, THE Frontend_App SHALL display filter options relevant to the export type (jenjang, kelas, status for siswa; tahun_ajaran, jenjang, kelas, status for tagihan; date range and tahun_ajaran for pembayaran; bulan and tahun for kas harian; tahun for rekap bulanan) and a format selector (xlsx or csv)
7. THE Frontend_App SHALL display a file upload area that accepts drag-and-drop or click-to-browse, showing the accepted file types (.xlsx, .csv) and maximum file size (5MB)
8. IF the uploaded file exceeds 5MB or has an unsupported extension, THEN THE Frontend_App SHALL display an immediate client-side error message without sending the file to the server
9. WHEN an export or import is being processed via Queue_Job, THE Frontend_App SHALL display a loading indicator with the message "Sedang diproses..." and disable the submit button to prevent duplicate submissions
