# Requirements Document

## Introduction

Fitur ini menangani proses **Kenaikan Kelas** (promosi siswa ke kelas berikutnya) dan **Kelulusan** (siswa menyelesaikan jenjang pendidikan) pada akhir setiap periode tahun ajaran. Proses ini merupakan operasi kritis akhir tahun yang mempengaruhi banyak siswa sekaligus dan harus:

1. Mendukung promosi massal (bulk) seluruh siswa dalam satu kelas ke kelas berikutnya
2. Mendukung penyesuaian individual (tinggal kelas, kelulusan, pindah jenjang)
3. Membuat record SiswaKelas baru untuk TahunAjaran tujuan
4. Mengubah status siswa yang lulus menjadi "Lulus"
5. Mendukung pembatalan (undo) proses jika terjadi kesalahan
6. Beroperasi secara independen per branch (multi-branch)

Fitur ini bergantung pada spec `periode-tahun-ajaran` yang menyediakan entitas TahunAjaran dan tabel SiswaKelas.

## Glossary

- **Backend_API**: Laravel API yang menyediakan endpoint untuk proses kenaikan kelas dan kelulusan
- **Frontend_App**: Aplikasi Laravel + Livewire + Filament yang menampilkan antarmuka pengguna
- **Admin_Operator**: Pengguna dengan role admin atau operator yang memproses kenaikan kelas dan kelulusan
- **Siswa**: Entitas murid yang memiliki NIS, nama, jenjang, status, dan riwayat penempatan kelas
- **Kelas**: Entitas kelas yang memiliki jenjang (MI/TK/KB) dan nama, di-scope per branch
- **TahunAjaran**: Entitas periode tahun ajaran dengan status Aktif/Non-Aktif per branch
- **Periode_Aktif**: TahunAjaran yang sedang berlaku untuk suatu branch
- **SiswaKelas**: Tabel pivot yang mencatat penempatan siswa ke kelas pada suatu TahunAjaran tertentu
- **Branch**: Cabang sekolah yang memisahkan data antar lokasi
- **Kenaikan_Kelas**: Proses memindahkan siswa dari kelas saat ini ke kelas berikutnya dalam jenjang yang sama untuk periode baru
- **Kelulusan**: Proses mengubah status siswa menjadi "Lulus" ketika siswa berada di kelas tertinggi jenjangnya
- **Tinggal_Kelas**: Kondisi di mana siswa tidak dipromosikan dan tetap di kelas yang sama pada periode baru
- **Pindah_Jenjang**: Proses perpindahan siswa dari jenjang satu ke jenjang berikutnya (KB→TK, TK→MI)
- **Hierarki_Kelas**: Urutan kelas dalam setiap jenjang yang dikonfigurasi oleh Admin_Operator melalui manajemen kelas, ditentukan oleh atribut urutan (level) pada setiap Kelas
- **Kelas_Tertinggi**: Kelas dengan level tertinggi dalam suatu jenjang dan branch, ditentukan berdasarkan atribut urutan yang dikonfigurasi oleh Admin_Operator
- **Periode_Tujuan**: TahunAjaran baru yang menjadi target penempatan kelas setelah proses kenaikan/kelulusan
- **Batch_Promosi**: Satu set operasi kenaikan kelas/kelulusan yang diproses bersamaan dan dapat di-undo sebagai satu kesatuan

## Requirements

### Requirement 1: Konfigurasi Hierarki Kelas

**User Story:** Sebagai Admin_Operator, saya ingin mengatur urutan (level) kelas per jenjang melalui manajemen kelas, sehingga proses kenaikan kelas otomatis menentukan kelas tujuan yang benar sesuai konfigurasi sekolah.

#### Acceptance Criteria

1. THE Backend_API SHALL add a "level" integer column to the kelas table that represents the order/position of the class within its jenjang, where a higher level number indicates a higher class
2. THE Frontend_App SHALL provide a level configuration field on the Kelas management page (create and edit forms) allowing the Admin_Operator to assign a numeric level to each Kelas
3. WHEN a Kenaikan_Kelas process is initiated for a Siswa, THE Backend_API SHALL determine the next class by finding the Kelas with the same jenjang and branch_id that has the next higher level value compared to the Siswa's current Kelas level
4. THE Backend_API SHALL identify Kelas_Tertinggi as the Kelas with the highest level value within each jenjang and branch_id combination
5. IF a Siswa is in a Kelas_Tertinggi and a Kenaikan_Kelas is attempted, THEN THE Backend_API SHALL reject the promotion and return an error message indicating the student must be graduated or transferred to the next jenjang
6. THE Backend_API SHALL validate that no two Kelas within the same jenjang and branch_id have the same level value, returning HTTP 422 with a validation error if a duplicate level is detected during Kelas creation or update
7. IF a Kelas does not have a level value assigned (NULL), THEN THE Backend_API SHALL exclude that Kelas from the Hierarki_Kelas and it SHALL NOT be available as a source or target in the Kenaikan_Kelas process

### Requirement 2: Promosi Massal (Bulk Promotion)

**User Story:** Sebagai Admin_Operator, saya ingin mempromosikan seluruh siswa dalam satu kelas ke kelas berikutnya sekaligus, sehingga proses akhir tahun dapat dilakukan secara efisien.

#### Acceptance Criteria

1. WHEN the Admin_Operator initiates a bulk promotion for a specific Kelas and Periode_Tujuan, THE Backend_API SHALL create SiswaKelas records for all Siswa with status "Aktif" who have a SiswaKelas record in the source Kelas for the Periode_Aktif, assigning each Siswa to the next Kelas in the Hierarki_Kelas for the Periode_Tujuan
2. WHEN a bulk promotion is executed, THE Backend_API SHALL update the siswas.kelas_id column for each promoted Siswa to reflect the new Kelas assignment if the Periode_Tujuan is the Periode_Aktif of the branch
3. WHEN a bulk promotion is executed, THE Backend_API SHALL process all SiswaKelas record creations within a single database transaction, so that if any individual record fails, all changes are rolled back
4. WHEN a bulk promotion is completed successfully, THE Backend_API SHALL return a summary containing: total students processed, total students promoted, and the Batch_Promosi identifier
5. IF a Siswa already has a SiswaKelas record for the Periode_Tujuan, THEN THE Backend_API SHALL skip that Siswa during bulk promotion and include the Siswa in a "skipped" list in the response summary
6. THE Backend_API SHALL scope the bulk promotion operation to Siswa and Kelas records belonging to the authenticated user's branch_id

### Requirement 3: Promosi Individual

**User Story:** Sebagai Admin_Operator, saya ingin memindahkan siswa tertentu ke kelas yang berbeda dari kelas default promosi, sehingga saya dapat menangani kasus khusus seperti siswa pindahan atau akselerasi.

#### Acceptance Criteria

1. WHEN the Admin_Operator submits an individual promotion request specifying siswa_id, target kelas_id, and Periode_Tujuan tahun_ajaran_id, THE Backend_API SHALL create a SiswaKelas record assigning the Siswa to the specified target Kelas for the Periode_Tujuan
2. THE Backend_API SHALL validate that the target Kelas belongs to the same branch_id as the Siswa
3. THE Backend_API SHALL validate that the target Kelas jenjang matches the Siswa's jenjang field, unless the promotion is explicitly marked as a Pindah_Jenjang operation
4. IF the Siswa already has a SiswaKelas record for the specified Periode_Tujuan, THEN THE Backend_API SHALL update the existing record's kelas_id to the new target Kelas instead of creating a duplicate
5. WHEN an individual promotion is executed where the Periode_Tujuan is the Periode_Aktif of the branch, THE Backend_API SHALL update the siswas.kelas_id column to match the new target Kelas
6. THE Backend_API SHALL record the individual promotion as part of a Batch_Promosi for traceability and undo capability

### Requirement 4: Kelulusan Siswa

**User Story:** Sebagai Admin_Operator, saya ingin meluluskan siswa yang berada di kelas tertinggi jenjangnya, sehingga status siswa terupdate dan siswa tidak ditempatkan di kelas manapun pada periode baru.

#### Acceptance Criteria

1. WHEN the Admin_Operator submits a graduation request for one or more Siswa, THE Backend_API SHALL change the status of each specified Siswa from "Aktif" to "Lulus"
2. THE Backend_API SHALL validate that each Siswa being graduated is currently assigned to a Kelas_Tertinggi in the Periode_Aktif before processing the graduation
3. WHEN a Siswa is graduated, THE Backend_API SHALL NOT create a SiswaKelas record for that Siswa in the Periode_Tujuan
4. WHEN a Siswa is graduated, THE Backend_API SHALL set the siswas.kelas_id to NULL for the graduated Siswa
5. THE Backend_API SHALL support bulk graduation by accepting an array of siswa_id values in a single request and processing all graduations within a single database transaction
6. WHEN a graduation batch is completed, THE Backend_API SHALL return a summary containing: total students graduated and the Batch_Promosi identifier
7. IF a Siswa specified for graduation does not have status "Aktif", THEN THE Backend_API SHALL skip that Siswa and include the Siswa in a "skipped" list in the response with a reason indicating the current status
8. IF a Siswa specified for graduation is not in a Kelas_Tertinggi, THEN THE Backend_API SHALL skip that Siswa and include the Siswa in a "skipped" list in the response with a reason indicating the student is not in the highest class

### Requirement 5: Tinggal Kelas (Retention)

**User Story:** Sebagai Admin_Operator, saya ingin menandai siswa tertentu sebagai tinggal kelas, sehingga siswa tersebut tetap di kelas yang sama pada periode baru.

#### Acceptance Criteria

1. WHEN the Admin_Operator marks one or more Siswa as Tinggal_Kelas for a specified Periode_Tujuan, THE Backend_API SHALL create SiswaKelas records assigning each specified Siswa to the same Kelas they occupied in the Periode_Aktif for the Periode_Tujuan
2. THE Backend_API SHALL validate that each specified Siswa has a SiswaKelas record in the Periode_Aktif before processing the retention
3. IF a Siswa already has a SiswaKelas record for the Periode_Tujuan, THEN THE Backend_API SHALL update the existing record's kelas_id to the same Kelas from the Periode_Aktif
4. THE Backend_API SHALL process all Tinggal_Kelas operations within a single database transaction
5. WHEN a Tinggal_Kelas batch is completed, THE Backend_API SHALL return a summary containing: total students retained and the Batch_Promosi identifier
6. THE Backend_API SHALL record the Tinggal_Kelas operation as part of a Batch_Promosi for traceability and undo capability

### Requirement 6: Pindah Jenjang (Cross-Level Transfer)

**User Story:** Sebagai Admin_Operator, saya ingin memindahkan siswa lulusan KB ke TK atau lulusan TK ke MI, sehingga siswa dapat melanjutkan pendidikan di jenjang berikutnya.

#### Acceptance Criteria

1. WHEN the Admin_Operator submits a Pindah_Jenjang request for a Siswa specifying the target Kelas in the next jenjang and the Periode_Tujuan, THE Backend_API SHALL update the Siswa's jenjang field to match the target Kelas jenjang, create a SiswaKelas record assigning the Siswa to the target Kelas for the Periode_Tujuan, and change the Siswa's status from "Lulus" back to "Aktif"
2. THE Backend_API SHALL validate that the Pindah_Jenjang follows the allowed transitions: KB graduates can transfer to TK (TK A), and TK graduates can transfer to MI (Kelas 1)
3. THE Backend_API SHALL validate that the Siswa's current status is "Lulus" before allowing a Pindah_Jenjang operation
4. THE Backend_API SHALL validate that the target Kelas belongs to the same branch_id as the Siswa
5. IF the Pindah_Jenjang target jenjang does not follow the allowed transition path from the Siswa's current jenjang, THEN THE Backend_API SHALL reject the request and return an error message indicating the invalid transition
6. WHEN a Pindah_Jenjang is executed where the Periode_Tujuan is the Periode_Aktif of the branch, THE Backend_API SHALL update the siswas.kelas_id column to match the target Kelas
7. THE Backend_API SHALL record the Pindah_Jenjang operation as part of a Batch_Promosi for traceability and undo capability

### Requirement 7: Pembatalan Proses (Undo)

**User Story:** Sebagai Admin_Operator, saya ingin dapat membatalkan proses kenaikan kelas atau kelulusan yang sudah dijalankan, sehingga kesalahan dapat diperbaiki tanpa harus mengedit data satu per satu.

#### Acceptance Criteria

1. THE Backend_API SHALL store each Batch_Promosi with the following attributes: id (UUID), batch_type (enum: "promosi", "kelulusan", "tinggal_kelas", "pindah_jenjang"), source_tahun_ajaran_id, target_tahun_ajaran_id, kelas_id (source class, nullable), processed_by (user_id), processed_at (timestamp), status (enum: "completed", "undone"), and branch_id
2. THE Backend_API SHALL store individual operations within a Batch_Promosi in a detail table containing: id, batch_id, siswa_id, action (enum: "naik_kelas", "lulus", "tinggal_kelas", "pindah_jenjang"), source_kelas_id, target_kelas_id (nullable for graduation), previous_status (siswa status before operation), and previous_jenjang (siswa jenjang before operation, nullable)
3. WHEN the Admin_Operator requests an undo for a Batch_Promosi, THE Backend_API SHALL reverse all operations in the batch: delete created SiswaKelas records for the Periode_Tujuan, restore siswas.status to the previous_status value, restore siswas.jenjang to the previous_jenjang value where applicable, and restore siswas.kelas_id to the source_kelas_id
4. WHEN an undo is executed, THE Backend_API SHALL process all reversals within a single database transaction
5. WHEN an undo is completed, THE Backend_API SHALL set the Batch_Promosi status to "undone"
6. IF the Admin_Operator attempts to undo a Batch_Promosi that already has status "undone", THEN THE Backend_API SHALL reject the request and return an error message indicating the batch has already been reversed
7. IF a Siswa's SiswaKelas record for the Periode_Tujuan has been manually modified after the batch was processed (kelas_id differs from the batch detail's target_kelas_id), THEN THE Backend_API SHALL skip that Siswa during undo, include the Siswa in a "skipped" list in the response, and continue processing the remaining operations
8. THE Backend_API SHALL scope undo operations to Batch_Promosi records belonging to the authenticated user's branch_id

### Requirement 8: Validasi Proses Kenaikan Kelas

**User Story:** Sebagai Admin_Operator, saya ingin sistem memvalidasi proses kenaikan kelas sebelum dieksekusi, sehingga kesalahan data dapat dicegah.

#### Acceptance Criteria

1. WHEN a promotion or graduation process is initiated, THE Backend_API SHALL validate that the Periode_Tujuan tahun_ajaran_id references a TahunAjaran that exists and belongs to the same branch_id as the authenticated user
2. WHEN a promotion or graduation process is initiated, THE Backend_API SHALL validate that the Periode_Tujuan is different from the Periode_Aktif (source period)
3. IF the Periode_Tujuan is the same as the Periode_Aktif, THEN THE Backend_API SHALL reject the request and return an error message indicating that promotion must target a different period
4. WHEN a bulk promotion is initiated for a Kelas, THE Backend_API SHALL validate that the Kelas has at least one Siswa with status "Aktif" and a SiswaKelas record in the Periode_Aktif
5. IF the source Kelas has no eligible Siswa for promotion, THEN THE Backend_API SHALL reject the request and return an error message indicating no students are available for promotion in the specified class
6. THE Backend_API SHALL validate that the target Kelas exists and belongs to the same branch_id before creating any SiswaKelas records

### Requirement 9: Antarmuka Kenaikan Kelas dan Kelulusan

**User Story:** Sebagai Admin_Operator, saya ingin antarmuka yang memudahkan proses kenaikan kelas dan kelulusan, sehingga saya dapat memproses seluruh siswa dengan cepat dan akurat.

#### Acceptance Criteria

1. THE Frontend_App SHALL provide a "Kenaikan Kelas & Kelulusan" page accessible to Admin_Operator with permission "manage-kenaikan-kelas"
2. THE Frontend_App SHALL display a form to select the source period (defaulting to Periode_Aktif) and the Periode_Tujuan (dropdown of available TahunAjaran excluding the source period)
3. WHEN the Admin_Operator selects a source period, THE Frontend_App SHALL display a list of all Kelas for the current branch grouped by jenjang (KB, TK, MI), showing the count of Siswa with status "Aktif" in each Kelas for that period
4. WHEN the Admin_Operator selects a Kelas, THE Frontend_App SHALL display a table of all Siswa in that Kelas for the source period with columns: NIS, Nama, and an "Aksi" column with a dropdown defaulting to the appropriate action (Naik Kelas for non-highest class, Lulus for Kelas_Tertinggi)
5. THE Frontend_App SHALL allow the Admin_Operator to change the "Aksi" for individual Siswa to one of: "Naik Kelas" (with target class auto-filled), "Tinggal Kelas", "Lulus" (only for Kelas_Tertinggi), or "Pindah Jenjang" (only for Kelas_Tertinggi with target class selector)
6. WHEN the Admin_Operator changes the "Aksi" to "Naik Kelas", THE Frontend_App SHALL display the default next Kelas in the Hierarki_Kelas and allow the Admin_Operator to select a different target Kelas within the same jenjang and branch
7. THE Frontend_App SHALL display a summary panel showing the count of students per action type (naik kelas, tinggal kelas, lulus, pindah jenjang) before the Admin_Operator confirms the process
8. WHEN the Admin_Operator confirms the process, THE Frontend_App SHALL send the batch request to the Backend_API, display a loading indicator during processing, and upon success display a success notification with the summary of processed students
9. IF the Backend_API returns an error during processing, THEN THE Frontend_App SHALL display a danger notification with the error message and preserve the current form state so the Admin_Operator can correct and retry
10. IF the user does not have the "manage-kenaikan-kelas" permission, THEN THE Frontend_App SHALL hide the "Kenaikan Kelas & Kelulusan" page from navigation

### Requirement 10: Antarmuka Riwayat dan Undo

**User Story:** Sebagai Admin_Operator, saya ingin melihat riwayat proses kenaikan kelas dan dapat membatalkan proses yang salah, sehingga saya memiliki kontrol penuh atas data siswa.

#### Acceptance Criteria

1. THE Frontend_App SHALL display a "Riwayat Kenaikan Kelas" section on the Kenaikan Kelas page showing a table of all Batch_Promosi records for the current branch, sorted by processed_at descending
2. THE Frontend_App SHALL display each Batch_Promosi row with columns: tanggal proses, tipe batch, kelas asal, periode asal, periode tujuan, jumlah siswa diproses, status (completed/undone), and the name of the user who processed it
3. WHEN the Admin_Operator clicks on a Batch_Promosi row, THE Frontend_App SHALL display the detail list showing each Siswa affected, the action taken, source class, and target class
4. THE Frontend_App SHALL display an "Undo" button on each Batch_Promosi row with status "completed"
5. WHEN the Admin_Operator clicks the "Undo" button, THE Frontend_App SHALL display a confirmation dialog stating the number of students that will be affected, and upon confirmation send the undo request to the Backend_API
6. WHEN the undo is completed successfully, THE Frontend_App SHALL display a success notification with the undo summary (including any skipped students) and refresh the history table
7. IF the undo response contains skipped students, THEN THE Frontend_App SHALL display a warning notification listing the skipped students and the reason each was skipped
8. THE Frontend_App SHALL disable the "Undo" button on Batch_Promosi rows with status "undone" and display a visual indicator that the batch has been reversed

### Requirement 11: Permission dan Keamanan

**User Story:** Sebagai Admin_Operator, saya ingin akses ke proses kenaikan kelas dan kelulusan dibatasi berdasarkan permission, sehingga hanya pengguna yang berwenang yang dapat memproses perubahan kelas siswa.

#### Acceptance Criteria

1. THE Backend_API SHALL register a new permission "manage-kenaikan-kelas" in the spatie/laravel-permission system during the database seeder execution
2. WHEN a request to process promotion, graduation, retention, cross-level transfer, or undo is received, THE Backend_API SHALL verify that the authenticated user has the "manage-kenaikan-kelas" permission before processing the request
3. IF the authenticated user does not have the "manage-kenaikan-kelas" permission, THEN THE Backend_API SHALL return an HTTP 403 response with a JSON body containing an error message indicating insufficient permission
4. THE Backend_API SHALL allow all authenticated users with the "manage-kenaikan-kelas" permission to view Batch_Promosi history for their branch
5. WHEN any kenaikan kelas or kelulusan operation is processed, THE Backend_API SHALL scope all data access and modifications to records belonging to the authenticated user's branch_id
6. IF a request targets Siswa or Kelas records that do not belong to the authenticated user's branch_id, THEN THE Backend_API SHALL return an HTTP 403 response and SHALL NOT modify any records
