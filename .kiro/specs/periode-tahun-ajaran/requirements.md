# Requirements Document

## Introduction

Fitur ini memperkenalkan entitas **Tahun Ajaran** (Academic Year/Period) sebagai fondasi data temporal dalam sistem manajemen tagihan sekolah. Saat ini, sistem tidak memiliki konsep periode — tagihan, jenis tagihan, dan penempatan kelas siswa tidak terikat ke tahun ajaran tertentu. Fitur ini akan:

1. Membuat entitas TahunAjaran dengan mekanisme aktivasi per branch (hanya satu periode aktif per cabang)
2. Mengaitkan Tagihan dan JenisTagihan ke periode tahun ajaran
3. Mengubah penempatan kelas siswa menjadi period-aware melalui tabel pivot (riwayat kelas per periode)
4. Menyediakan CRUD management untuk TahunAjaran
5. Menambahkan filter periode pada tampilan data
6. Menyediakan strategi migrasi data existing ke struktur baru

Fitur ini menjadi dependensi utama untuk spec lain: kenaikan-kelas-kelulusan, import-export-data, dan dashboard.

## Glossary

- **TahunAjaran**: Entitas yang merepresentasikan satu periode tahun ajaran (contoh: "2024/2025"), memiliki nama, tanggal mulai, tanggal selesai, dan status per branch
- **Periode_Aktif**: Status TahunAjaran yang sedang berlaku untuk suatu branch; hanya satu TahunAjaran yang boleh aktif per branch pada satu waktu
- **Backend_API**: Laravel API yang menyediakan endpoint untuk manajemen data tahun ajaran
- **Frontend_App**: Aplikasi Laravel + Livewire + Filament yang menampilkan antarmuka pengguna
- **Admin_Operator**: Pengguna dengan role admin atau operator yang mengelola data tahun ajaran
- **Branch**: Cabang sekolah yang memisahkan data antar lokasi; setiap branch memiliki set TahunAjaran sendiri
- **Siswa**: Entitas murid yang memiliki NIS, nama, jenjang, dan riwayat penempatan kelas per periode
- **Kelas**: Entitas kelas yang memiliki jenjang dan nama, di-scope per branch
- **Tagihan**: Record kewajiban pembayaran yang dimiliki siswa, terikat ke satu TahunAjaran
- **JenisTagihan**: Template/tipe tagihan yang memiliki nama, jumlah, dan jatuh tempo, terikat ke satu TahunAjaran
- **SiswaKelas**: Tabel pivot yang mencatat penempatan siswa ke kelas pada suatu TahunAjaran tertentu
- **Legacy_Period**: TahunAjaran default yang dibuat otomatis saat migrasi untuk menampung data existing yang belum memiliki referensi periode

## Requirements

### Requirement 1: Pembuatan Entitas TahunAjaran

**User Story:** Sebagai Admin_Operator, saya ingin membuat data tahun ajaran baru, sehingga saya dapat mengelola tagihan dan penempatan kelas berdasarkan periode.

#### Acceptance Criteria

1. THE Backend_API SHALL store TahunAjaran with the following attributes: id (auto-increment), nama (string max 9 characters, format "YYYY/YYYY" where the second year equals the first year plus one), tanggal_mulai (date), tanggal_selesai (date), status (enum: "Aktif" or "Non-Aktif"), and branch_id (foreign key to branches)
2. WHEN a new TahunAjaran is created, THE Backend_API SHALL validate that nama follows the format "YYYY/YYYY" where the second year is exactly one greater than the first year, and return HTTP 422 with a validation error message if the format is invalid
3. WHEN a new TahunAjaran is created, THE Backend_API SHALL validate that tanggal_mulai is earlier than tanggal_selesai, and return HTTP 422 with a validation error message if the dates are invalid
4. WHEN a new TahunAjaran is created, THE Backend_API SHALL validate that no other TahunAjaran with the same nama exists within the same branch_id (case-insensitive), and return HTTP 422 with a validation error message if a duplicate exists
5. WHEN a new TahunAjaran is created, THE Backend_API SHALL set the status to "Non-Aktif" by default
6. THE Backend_API SHALL scope all TahunAjaran queries to the authenticated user's branch_id

### Requirement 2: Aktivasi dan Deaktivasi TahunAjaran

**User Story:** Sebagai Admin_Operator, saya ingin mengaktifkan satu tahun ajaran sebagai periode berjalan, sehingga semua data baru (tagihan, penempatan kelas) otomatis terikat ke periode tersebut.

#### Acceptance Criteria

1. WHEN a TahunAjaran is activated, THE Backend_API SHALL set the status of the target TahunAjaran to "Aktif", set the status of all other TahunAjaran within the same branch_id to "Non-Aktif", and return the updated TahunAjaran record within 2 seconds
2. THE Backend_API SHALL ensure that at most one TahunAjaran has status "Aktif" per branch_id at any given time
3. WHEN a TahunAjaran is deactivated, THE Backend_API SHALL set the status of the target TahunAjaran to "Non-Aktif" and return the updated TahunAjaran record
4. WHEN no TahunAjaran is active for a branch, THE Frontend_App SHALL display a warning banner indicating that no active period is set and prompting the Admin_Operator to activate one
5. IF an Admin_Operator attempts to delete a TahunAjaran that has associated Tagihan or SiswaKelas records, THEN THE Backend_API SHALL reject the deletion and return an error message indicating that the period cannot be deleted because it has associated data
6. IF an Admin_Operator attempts to activate or deactivate a TahunAjaran that does not exist or does not belong to the operator's branch_id, THEN THE Backend_API SHALL reject the request and return an error message indicating the resource was not found
7. IF an Admin_Operator attempts to activate a TahunAjaran that is already "Aktif", THEN THE Backend_API SHALL return the current TahunAjaran record without modifying any data

### Requirement 3: CRUD TahunAjaran via Frontend

**User Story:** Sebagai Admin_Operator, saya ingin mengelola data tahun ajaran melalui antarmuka web, sehingga saya dapat membuat, melihat, mengedit, dan menghapus periode tahun ajaran.

#### Acceptance Criteria

1. THE Frontend_App SHALL provide a TahunAjaran management page accessible to Admin_Operator with permission "manage-tahun-ajaran"
2. THE Frontend_App SHALL display a table listing all TahunAjaran for the current branch, showing nama, tanggal_mulai, tanggal_selesai, and status with a visual badge (green for "Aktif", gray for "Non-Aktif"), with default pagination of 10 records per page
3. WHEN the Admin_Operator submits the create form with fields nama (text input, format "YYYY/YYYY"), tanggal_mulai (date picker), and tanggal_selesai (date picker), THE Frontend_App SHALL send a request to the Backend_API, display a success notification upon successful creation, and refresh the table data
4. IF the Backend_API returns a validation error (HTTP 422) on create or edit, THEN THE Frontend_App SHALL display a danger notification showing the first validation error message returned by the API
5. THE Frontend_App SHALL provide an edit form pre-filled with existing TahunAjaran data, allowing modification of nama, tanggal_mulai, and tanggal_selesai, and upon successful update SHALL display a success notification and refresh the table data
6. THE Frontend_App SHALL provide an "Aktifkan" button on each TahunAjaran row with status "Non-Aktif" that triggers the activation process with a confirmation dialog, and upon successful activation SHALL display a success notification and refresh the table data
7. THE Frontend_App SHALL provide a delete action on each TahunAjaran row that requires a confirmation dialog before executing, and upon successful deletion SHALL display a success notification and refresh the table data
8. IF the Backend_API returns an error when deleting a TahunAjaran that has associated data, THEN THE Frontend_App SHALL display a danger notification with an error message indicating the period cannot be deleted
9. IF the Backend_API returns a server error (non-success response other than 422) on any TahunAjaran operation, THEN THE Frontend_App SHALL display a danger notification indicating a server error occurred
10. IF the user does not have the "manage-tahun-ajaran" permission, THEN THE Frontend_App SHALL hide the TahunAjaran management page from navigation

### Requirement 4: Pengaitan Tagihan dengan TahunAjaran

**User Story:** Sebagai Admin_Operator, saya ingin setiap tagihan terikat ke tahun ajaran tertentu, sehingga saya dapat melacak tagihan per periode dan menghasilkan laporan yang akurat.

#### Acceptance Criteria

1. THE Backend_API SHALL add a tahun_ajaran_id foreign key column (nullable) to the tagihans table that references the tahun_ajarans table, with a database index on the column
2. WHEN a new Tagihan is created and no tahun_ajaran_id is present in the request body (absent or null), THE Backend_API SHALL automatically assign the Periode_Aktif of the authenticated user's branch as the tahun_ajaran_id
3. IF no Periode_Aktif exists for the authenticated user's branch when creating a Tagihan, THEN THE Backend_API SHALL reject the creation with HTTP 422 and return an error message indicating that an active period must be set first
4. IF a tahun_ajaran_id is explicitly provided in the request, THEN THE Backend_API SHALL validate that the referenced TahunAjaran record belongs to the same branch_id as the authenticated user, and reject the request with HTTP 422 and an error message indicating branch mismatch if validation fails
5. WHEN querying Tagihan, THE Backend_API SHALL support filtering by tahun_ajaran_id as an optional query parameter, and return only Tagihan records matching the specified tahun_ajaran_id
6. WHEN no tahun_ajaran_id filter is provided on Tagihan queries and a Periode_Aktif exists for the authenticated user's branch, THE Backend_API SHALL default to returning Tagihan belonging to the Periode_Aktif
7. IF no tahun_ajaran_id filter is provided on Tagihan queries and no Periode_Aktif exists for the authenticated user's branch, THEN THE Backend_API SHALL return an empty collection with HTTP 200

### Requirement 5: Pengaitan JenisTagihan dengan TahunAjaran

**User Story:** Sebagai Admin_Operator, saya ingin jenis tagihan terikat ke tahun ajaran, sehingga saya dapat mendefinisikan template tagihan yang berbeda untuk setiap periode (misalnya kenaikan biaya).

#### Acceptance Criteria

1. THE Backend_API SHALL add a tahun_ajaran_id NOT NULL foreign key column to the jenis_tagihans table that references the tahun_ajarans table, with the constraint preventing deletion of a TahunAjaran that has associated JenisTagihan records
2. WHEN a new JenisTagihan is created and no tahun_ajaran_id is explicitly provided, THE Backend_API SHALL automatically assign the Periode_Aktif of the authenticated user's branch as the tahun_ajaran_id
3. IF no Periode_Aktif exists for the authenticated user's branch when creating a JenisTagihan without an explicit tahun_ajaran_id, THEN THE Backend_API SHALL reject the creation and return an error message indicating that an active period must be set first
4. THE Backend_API SHALL validate that the provided or assigned tahun_ajaran_id belongs to the same branch_id as the authenticated user, rejecting the request with an error message if the branch does not match
5. WHEN querying JenisTagihan, THE Backend_API SHALL support filtering by tahun_ajaran_id as an optional query parameter and validate that the provided tahun_ajaran_id belongs to the authenticated user's branch
6. WHEN no tahun_ajaran_id filter is provided on JenisTagihan queries, THE Backend_API SHALL default to returning JenisTagihan belonging to the Periode_Aktif of the authenticated user's branch
7. IF no tahun_ajaran_id filter is provided on JenisTagihan queries and no Periode_Aktif exists for the authenticated user's branch, THEN THE Backend_API SHALL return an empty collection
8. THE Frontend_App SHALL display the TahunAjaran nama as a column in the JenisTagihan management table view

### Requirement 6: Penempatan Kelas Siswa Per Periode (SiswaKelas)

**User Story:** Sebagai Admin_Operator, saya ingin mencatat kelas siswa per tahun ajaran, sehingga saya dapat melacak riwayat penempatan kelas dan mempersiapkan proses kenaikan kelas.

#### Acceptance Criteria

1. THE Backend_API SHALL create a siswa_kelas pivot table with columns: id (auto-increment), siswa_id (foreign key to siswas.id), kelas_id (foreign key to kelas.id), tahun_ajaran_id (foreign key to tahun_ajarans.id), and timestamps
2. THE Backend_API SHALL enforce a unique constraint on the combination of siswa_id and tahun_ajaran_id in the siswa_kelas table, ensuring one student has exactly one class assignment per period
3. WHEN querying a Siswa's current class, THE Backend_API SHALL resolve the class from the siswa_kelas record matching the Periode_Aktif of the authenticated user's branch
4. THE Backend_API SHALL retain the existing kelas_id column on the siswas table as a denormalized reference, ensuring all existing API endpoints that read siswas.kelas_id continue to return the same value without modification
5. WHEN a SiswaKelas record is created or updated where the tahun_ajaran_id references the Periode_Aktif for the siswa's branch_id, THE Backend_API SHALL synchronize the siswas.kelas_id column to match the new kelas_id within the same database transaction
6. WHEN querying siswa with their class information and the tahun_ajaran_id parameter is provided, THE Backend_API SHALL return the class assignment from the siswa_kelas record matching that tahun_ajaran_id
7. WHEN querying siswa with their class information and the tahun_ajaran_id parameter is omitted, THE Backend_API SHALL default to the Periode_Aktif for the authenticated user's branch
8. IF a SiswaKelas record is created with a siswa_id and tahun_ajaran_id combination that already exists, THEN THE Backend_API SHALL reject the request with HTTP 422 and return a validation error message indicating the student already has a class assignment for that period
9. IF no siswa_kelas record exists for the queried siswa and tahun_ajaran combination, THEN THE Backend_API SHALL return null for the class assignment field in the response

### Requirement 7: Filter Data Berdasarkan Periode

**User Story:** Sebagai Admin_Operator, saya ingin memfilter tampilan data berdasarkan tahun ajaran, sehingga saya dapat melihat data historis atau data periode tertentu.

#### Acceptance Criteria

1. THE Frontend_App SHALL display a TahunAjaran selector (dropdown) on the Tagihan and JenisTagihan pages, populated with all available TahunAjaran records sorted by descending year, defaulting to the Periode_Aktif
2. WHEN the Admin_Operator selects a different TahunAjaran from the selector, THE Frontend_App SHALL re-fetch and display the page data filtered by the selected tahun_ajaran_id without a full page reload, within 2 seconds of selection
3. THE Frontend_App SHALL persist the selected TahunAjaran filter in the user's server-side session so that navigating between pages maintains the selected period until the session expires or the user logs out
4. WHEN the Admin_Operator navigates to a page with the TahunAjaran selector and the Periode_Aktif on the backend differs from the session-stored selection due to a new TahunAjaran being activated, THE Frontend_App SHALL reset the session filter to the newly activated period and display the data for the new Periode_Aktif
5. THE Frontend_App SHALL display the name of the currently selected TahunAjaran in the selector, and SHALL display a distinguishing label (e.g., a badge or text suffix) next to the selected period indicating "Aktif" when the selected period is the Periode_Aktif, or "Historis" when the selected period is not the Periode_Aktif
6. IF the session contains a previously selected tahun_ajaran_id that no longer exists in the available TahunAjaran list, THEN THE Frontend_App SHALL fall back to the current Periode_Aktif and clear the invalid session value
7. IF no TahunAjaran records are available from the backend, THEN THE Frontend_App SHALL hide the TahunAjaran selector and display the page data without period filtering

### Requirement 8: Migrasi Data Existing ke Struktur Baru

**User Story:** Sebagai Admin_Operator, saya ingin data tagihan dan jenis tagihan yang sudah ada tetap dapat diakses setelah fitur tahun ajaran diterapkan, sehingga tidak ada data yang hilang.

#### Acceptance Criteria

1. WHEN the migration runs, THE Backend_API SHALL create exactly one Legacy_Period TahunAjaran record per branch with nama derived from the server date at migration time (e.g., "2024/2025" if migration runs between July 2024 and June 2025), status "Aktif", tanggal_mulai set to July 1 of the academic year start, and tanggal_selesai set to June 30 of the academic year end
2. WHEN the migration runs, THE Backend_API SHALL assign the Legacy_Period tahun_ajaran_id to all existing Tagihan records that have a NULL tahun_ajaran_id within each respective branch
3. WHEN the migration runs, THE Backend_API SHALL assign the Legacy_Period tahun_ajaran_id to all existing JenisTagihan records that have a NULL tahun_ajaran_id within each respective branch
4. WHEN the migration runs, THE Backend_API SHALL create SiswaKelas records for all Siswa with status "Aktif" and a non-NULL kelas_id, using their current kelas_id and the Legacy_Period tahun_ajaran_id of their branch
5. IF the migration encounters a Siswa with status "Aktif" and a NULL kelas_id, THEN THE Backend_API SHALL skip creating a SiswaKelas record for that Siswa and log a warning message to the Laravel application log including the Siswa NIS and branch_id
6. THE migration SHALL execute all data operations within a single database transaction per branch, so that if any operation fails for a branch, all changes for that branch are rolled back and no partial data is persisted
7. THE migration rollback SHALL remove the tahun_ajaran_id foreign key columns from the tagihans and jenis_tagihans tables, drop the siswa_kelas table, and drop the tahun_ajarans table
8. IF the migration runs and a Legacy_Period TahunAjaran record already exists for a branch, THEN THE Backend_API SHALL skip creating a duplicate record for that branch and use the existing Legacy_Period tahun_ajaran_id for data assignment

### Requirement 9: Kompatibilitas dengan Fitur Existing

**User Story:** Sebagai Admin_Operator, saya ingin fitur tagihan card view dan pembayaran tetap berfungsi normal setelah penambahan tahun ajaran, sehingga tidak ada gangguan pada operasional harian.

#### Acceptance Criteria

1. WHEN the tagihan grouped-by-siswa endpoint is called without a tahun_ajaran_id parameter, THE Backend_API SHALL default to filtering tagihan by the Periode_Aktif of the authenticated user's branch, returning the same response structure (siswa with nested tagihan array) as before the tahun_ajaran feature was added
2. THE Backend_API SHALL allow the batch payment endpoint to accept and process payments for tagihan belonging to any tahun_ajaran_id (not limited to the Periode_Aktif), provided all selected tagihan belong to the authenticated user's branch_id and do not already have status "Lunas"
3. THE Backend_API SHALL ensure that the existing single payment endpoints (POST /pembayaran/lunas/{kode_tagihan} and POST /pembayaran/bayar/{kode_tagihan}) continue to accept the same request parameters (metode, pembayar, and jumlah where applicable) and return the same response structure without requiring tahun_ajaran_id in the request body
4. WHEN the Tagihan_Card_View displays siswa information, THE Frontend_App SHALL resolve the siswa's kelas from the SiswaKelas record matching the currently selected TahunAjaran filter
5. IF no SiswaKelas record exists for a siswa in the currently selected TahunAjaran, THEN THE Frontend_App SHALL display the siswa's kelas field as empty (no class label) rather than showing stale data from another period
6. THE Backend_API SHALL ensure that existing Siswa CRUD endpoints continue to accept kelas_id directly for backward compatibility, and SHALL create or update the corresponding SiswaKelas record for the Periode_Aktif of the authenticated user's branch
7. IF no Periode_Aktif exists for the authenticated user's branch when a Siswa CRUD endpoint receives a kelas_id, THEN THE Backend_API SHALL reject the request and return an error message indicating that an active period must be set before assigning a class

### Requirement 10: Permission dan Keamanan

**User Story:** Sebagai Admin_Operator, saya ingin akses ke manajemen tahun ajaran dibatasi berdasarkan permission, sehingga hanya pengguna yang berwenang yang dapat mengubah konfigurasi periode.

#### Acceptance Criteria

1. THE Backend_API SHALL register a new permission "manage-tahun-ajaran" in the spatie/laravel-permission system during the database seeder execution
2. WHEN a request to create, update, activate, deactivate, or delete a TahunAjaran is received, THE Backend_API SHALL verify that the authenticated user has the "manage-tahun-ajaran" permission before processing the request
3. IF the authenticated user does not have the "manage-tahun-ajaran" permission, THEN THE Backend_API SHALL return an HTTP 403 response with a JSON body containing an error message indicating insufficient permission
4. THE Backend_API SHALL allow all authenticated users to perform read operations (list and view) on TahunAjaran data without requiring the "manage-tahun-ajaran" permission
5. WHEN a TahunAjaran mutation request (create, update, delete, or activate) is received, THE Backend_API SHALL scope the operation to records matching the authenticated user's branch_id
6. IF a TahunAjaran mutation request targets a record whose branch_id does not match the authenticated user's branch_id, THEN THE Backend_API SHALL return an HTTP 403 response and SHALL NOT modify the record
7. WHEN a read request (list or view) for TahunAjaran is received, THE Backend_API SHALL return only records matching the authenticated user's branch_id
