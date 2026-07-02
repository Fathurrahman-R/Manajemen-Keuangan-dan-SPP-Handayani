# Implementation Plan: Periode Tahun Ajaran

## Overview

This plan implements the TahunAjaran (Academic Year/Period) entity as the temporal foundation for the school billing management system. The implementation progresses from database migrations and backend models, through API controllers and business logic, to frontend management pages and period filtering. Each step builds incrementally, ensuring backward compatibility with existing features while introducing period-aware data management.

## Tasks

- [x] 1. Database Migrations and Models
  - [x] 1.1 Create TahunAjaran migration and model
    - Create migration `create_tahun_ajarans_table` with columns: id, nama (varchar 9), tanggal_mulai (date), tanggal_selesai (date), status (enum: Aktif, Non-Aktif, default Non-Aktif), branch_id (FK to branches), timestamps
    - Add UNIQUE index on (nama, branch_id)
    - Add INDEX on (branch_id, status) for fast active period lookup
    - Create `backend/app/Models/TahunAjaran.php` with fillable, casts, relationships (branch, tagihans, jenisTagihans, siswaKelas)
    - Add static helper `getAktif(int $branchId): ?self`
    - Create `TahunAjaranFactory` for testing
    - _Requirements: 1.1, 1.5, 1.6_

  - [x] 1.2 Create migration to add tahun_ajaran_id to tagihans and jenis_tagihans
    - Create migration `add_tahun_ajaran_id_to_tagihans_and_jenis_tagihans`
    - Add nullable `tahun_ajaran_id` (bigint unsigned, FK to tahun_ajarans.id) to `tagihans` table with INDEX
    - Add nullable `tahun_ajaran_id` (bigint unsigned, FK to tahun_ajarans.id) to `jenis_tagihans` table with INDEX
    - _Requirements: 4.1, 5.1_

  - [x] 1.3 Create siswa_kelas migration and model
    - Create migration `create_siswa_kelas_table` with columns: id, siswa_id (FK to siswas.id), kelas_id (FK to kelas.id), tahun_ajaran_id (FK to tahun_ajarans.id), timestamps
    - Add UNIQUE constraint on (siswa_id, tahun_ajaran_id)
    - Create `backend/app/Models/SiswaKelas.php` with fillable, relationships (siswa, kelas, tahunAjaran)
    - Create `SiswaKelasFactory` for testing
    - _Requirements: 6.1, 6.2_

  - [x] 1.4 Create data migration for existing records
    - Create migration `migrate_existing_data_to_tahun_ajaran`
    - Per branch in a DB transaction: create Legacy_Period TahunAjaran (nama from server date, status Aktif, tanggal_mulai July 1, tanggal_selesai June 30)
    - Assign Legacy_Period to all existing tagihans with NULL tahun_ajaran_id per branch
    - Assign Legacy_Period to all existing jenis_tagihans with NULL tahun_ajaran_id per branch
    - Create SiswaKelas records for active students with non-null kelas_id
    - Skip students with null kelas_id and log warning with NIS and branch_id
    - Use `firstOrCreate` to avoid duplicate Legacy_Period if migration re-runs
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.8_

  - [x] 1.5 Create migration to make jenis_tagihans.tahun_ajaran_id NOT NULL
    - Create migration `make_jenis_tagihans_tahun_ajaran_id_not_null`
    - Change `tahun_ajaran_id` column on `jenis_tagihans` from nullable to NOT NULL
    - _Requirements: 5.1_

  - [ ]* 1.6 Write unit tests for TahunAjaran model
    - Test fillable fields, casts, and relationships
    - Test `getAktif()` returns correct active period per branch
    - Test `getAktif()` returns null when no active period exists
    - Test factory creates valid records
    - _Requirements: 1.1, 1.5, 1.6_

- [ ] 2. Checkpoint - Ensure migrations run cleanly
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. TahunAjaran CRUD API
  - [x] 3.1 Create TahunAjaranRequest form request
    - Create `backend/app/Http/Requests/TahunAjaranRequest.php`
    - Validate: nama (required, string, max:9, regex YYYY/YYYY), tanggal_mulai (required, date), tanggal_selesai (required, date, after:tanggal_mulai)
    - _Requirements: 1.2, 1.3_

  - [x] 3.2 Create TahunAjaranResource
    - Create `backend/app/Http/Resources/TahunAjaranResource.php`
    - Return: id, nama, tanggal_mulai, tanggal_selesai, status, branch_id
    - _Requirements: 1.1_

  - [x] 3.3 Create TahunAjaranController with CRUD + activate/deactivate
    - Create `backend/app/Http/Controllers/TahunAjaranController.php`
    - `index()`: query all TahunAjaran for auth user's branch, order by tanggal_mulai desc, return collection
    - `store()`: validate nama format (second year = first + 1), validate uniqueness per branch (case-insensitive), set status Non-Aktif, set branch_id from auth user, return 201
    - `show($id)`: find by id, verify branch ownership, return resource or 404
    - `update($id)`: find by id, verify branch ownership, validate same rules excluding self for uniqueness, return 200
    - `destroy($id)`: find by id, verify branch ownership, check associations (Tagihan, JenisTagihan, SiswaKelas), return 409 if associations exist, otherwise delete and return `{"data": true}`
    - `activate($id)`: find by id, verify branch ownership, if already Aktif return current, within DB::transaction deactivate others and activate target, return 200
    - `deactivate($id)`: find by id, verify branch ownership, set Non-Aktif, return 200
    - _Requirements: 1.2, 1.3, 1.4, 1.5, 1.6, 2.1, 2.2, 2.3, 2.5, 2.6, 2.7_

  - [x] 3.4 Register TahunAjaran routes with permission middleware
    - Add routes in `backend/routes/api.php` within auth:sanctum group
    - GET /tahun-ajaran → index (no extra permission)
    - POST /tahun-ajaran → store (permission:manage-tahun-ajaran)
    - GET /tahun-ajaran/{id} → show (no extra permission)
    - PUT /tahun-ajaran/{id} → update (permission:manage-tahun-ajaran)
    - DELETE /tahun-ajaran/{id} → destroy (permission:manage-tahun-ajaran)
    - PATCH /tahun-ajaran/{id}/activate → activate (permission:manage-tahun-ajaran)
    - PATCH /tahun-ajaran/{id}/deactivate → deactivate (permission:manage-tahun-ajaran)
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7_

  - [x] 3.5 Register "manage-tahun-ajaran" permission in seeder
    - Add "manage-tahun-ajaran" to the Permission enum or permission list
    - Update `RoleAndPermissionSeeder` to include the new permission
    - Assign to admin role in `PermissionBinding::ADMIN_PERMISSIONS`
    - _Requirements: 10.1_

  - [ ]* 3.6 Write property test for Nama Format Validation
    - **Property 1: Nama Format Validation**
    - Generate random strings (valid YYYY/YYYY patterns and invalid patterns)
    - Verify API accepts valid format and rejects invalid with 422
    - **Validates: Requirements 1.2**

  - [ ]* 3.7 Write property test for Date Range Validation
    - **Property 2: Date Range Validation**
    - Generate random date pairs (valid: mulai < selesai, invalid: mulai >= selesai)
    - Verify API accepts valid pairs and rejects invalid with 422
    - **Validates: Requirements 1.3**

  - [ ]* 3.8 Write property test for Branch Data Isolation
    - **Property 3: Branch Data Isolation on Reads**
    - Create TahunAjaran across multiple branches
    - Verify each user only sees records from their own branch
    - **Validates: Requirements 1.6, 10.7**

  - [ ]* 3.9 Write property test for At Most One Active Per Branch
    - **Property 4: At Most One Active Per Branch**
    - Create multiple TahunAjaran per branch, perform random activation sequences
    - Verify at most one is Aktif per branch after each operation
    - **Validates: Requirements 2.1, 2.2**

  - [ ]* 3.10 Write property test for Deletion Protection
    - **Property 5: Deletion Protection**
    - Create TahunAjaran with/without associated records
    - Verify deletion rejected (409) when associations exist, succeeds when none
    - **Validates: Requirements 2.5**

  - [ ]* 3.11 Write property test for Permission Enforcement
    - **Property 15: Permission Enforcement on Mutations**
    - Attempt create/update/delete/activate/deactivate without manage-tahun-ajaran permission
    - Verify all return 403
    - **Validates: Requirements 10.2, 10.3**

  - [ ]* 3.12 Write property test for Read Access Without Permission
    - **Property 16: Read Access Without Manage Permission**
    - Attempt list/show without manage-tahun-ajaran permission
    - Verify all return 200 with branch-scoped data
    - **Validates: Requirements 10.4**

- [ ] 4. Checkpoint - Ensure TahunAjaran CRUD tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Tagihan and JenisTagihan Period Integration
  - [x] 5.1 Modify TagihanController for period awareness
    - Update `index()`: add optional `tahun_ajaran_id` query param, validate branch ownership, default to Periode_Aktif if not provided, return empty collection if no Periode_Aktif
    - Update `store()` (or equivalent create method): if `tahun_ajaran_id` not in request, auto-assign Periode_Aktif; if no Periode_Aktif, return 422; if provided, validate branch ownership
    - Update Tagihan model to add `tahunAjaran` relationship
    - _Requirements: 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [x] 5.2 Modify JenisTagihanController for period awareness
    - Update `index()`: add optional `tahun_ajaran_id` query param, validate branch ownership, default to Periode_Aktif if not provided, return empty collection if no Periode_Aktif
    - Update `store()` (or equivalent create method): if `tahun_ajaran_id` not in request, auto-assign Periode_Aktif; if no Periode_Aktif, return 422; if provided, validate branch ownership
    - Update JenisTagihan model to add `tahunAjaran` relationship
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_

  - [ ]* 5.3 Write property test for Auto-Assign Periode Aktif
    - **Property 6: Auto-Assign Periode Aktif on Creation**
    - Create Tagihan/JenisTagihan without tahun_ajaran_id when Periode_Aktif exists
    - Verify the created record has tahun_ajaran_id = Periode_Aktif id
    - **Validates: Requirements 4.2, 5.2**

  - [ ]* 5.4 Write property test for Reject Without Periode Aktif
    - **Property 7: Reject Creation Without Periode Aktif**
    - Attempt Tagihan/JenisTagihan creation without tahun_ajaran_id when no Periode_Aktif
    - Verify 422 rejection and no record persisted
    - **Validates: Requirements 4.3, 5.3, 9.7**

  - [ ]* 5.5 Write property test for Branch Validation on Provided ID
    - **Property 8: Branch Validation on Provided TahunAjaran ID**
    - Attempt creation with tahun_ajaran_id from different branch
    - Verify 422 rejection
    - **Validates: Requirements 4.4, 5.4**

  - [ ]* 5.6 Write property test for Filter Correctness
    - **Property 9: Filter Correctness by TahunAjaran**
    - Create records across multiple periods, filter by each
    - Verify all returned records match the filter value
    - **Validates: Requirements 4.5, 5.5**

- [ ] 6. Checkpoint - Ensure Tagihan/JenisTagihan integration tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. SiswaKelas Period-Aware Class Placement
  - [x] 7.1 Modify SiswaController for period-aware class assignment
    - Update create/update logic: when `kelas_id` provided, get Periode_Aktif (reject 422 if none), create/update SiswaKelas record, sync `siswas.kelas_id`
    - Add `tahun_ajaran_id` query parameter support on index/show for class resolution from SiswaKelas
    - Default to Periode_Aktif when `tahun_ajaran_id` not provided
    - Return null for class when no SiswaKelas record exists for queried period
    - _Requirements: 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 6.9, 9.6, 9.7_

  - [ ]* 7.2 Write property test for Unique Class Per Student Per Period
    - **Property 10: Unique Class Per Student Per Period**
    - Attempt duplicate siswa_id + tahun_ajaran_id combination
    - Verify 422 rejection
    - **Validates: Requirements 6.2, 6.8**

  - [ ]* 7.3 Write property test for Class Resolution by Period
    - **Property 11: Class Resolution by Period**
    - Create siswa with multiple period assignments
    - Query with specific tahun_ajaran_id, verify correct class returned
    - Query with non-existent period assignment, verify null returned
    - **Validates: Requirements 6.3, 6.6, 6.9**

  - [ ]* 7.4 Write property test for Kelas ID Synchronization
    - **Property 12: Kelas ID Synchronization**
    - Create/update SiswaKelas for Periode_Aktif
    - Verify siswas.kelas_id matches within same transaction
    - **Validates: Requirements 6.5**

  - [ ]* 7.5 Write property test for Siswa CRUD Backward Compatibility
    - **Property 14: Siswa CRUD Backward Compatibility**
    - Update siswa with kelas_id when Periode_Aktif exists
    - Verify SiswaKelas record created/updated and siswas.kelas_id synced
    - **Validates: Requirements 9.6**

- [x] 8. Backward Compatibility with Existing Features
  - [x] 8.1 Ensure tagihan grouped-by-siswa endpoint compatibility
    - Verify/update the grouped-by-siswa endpoint to default to Periode_Aktif when no tahun_ajaran_id provided
    - Ensure same response structure (siswa with nested tagihan array) is maintained
    - Ensure batch payment endpoint accepts tagihan from any tahun_ajaran_id (not limited to Periode_Aktif)
    - Ensure single payment endpoints remain unchanged (no tahun_ajaran_id required in request body)
    - _Requirements: 9.1, 9.2, 9.3_

  - [ ]* 8.2 Write property test for Cross-Period Batch Payment
    - **Property 13: Cross-Period Batch Payment**
    - Create tagihan from different periods, attempt batch payment
    - Verify all processed successfully when valid
    - **Validates: Requirements 9.2**

- [ ] 9. Checkpoint - Ensure all backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Frontend TahunAjaran Management Page
  - [x] 10.1 Create TahunAjaranManagement Livewire/Filament page
    - Create `frontend-v2/app/Livewire/TahunAjaranManagement.php` (or Filament page)
    - Place in navigation group with appropriate sort order
    - Implement `shouldRegisterNavigation()` to check session for `manage-tahun-ajaran` permission
    - Implement `mount()` to abort(403) if user lacks `manage-tahun-ajaran` permission
    - Display table with columns: nama (searchable), tanggal_mulai (date), tanggal_selesai (date), status (badge: green Aktif, gray Non-Aktif)
    - Fetch data from backend GET /tahun-ajaran
    - _Requirements: 3.1, 3.2, 3.10_

  - [x] 10.2 Implement create and edit forms
    - Add "Tambah" header action → modal form (nama text input, tanggal_mulai date picker, tanggal_selesai date picker)
    - Add "Edit" row action → modal form pre-filled with existing data
    - Send POST/PUT to backend API
    - Handle 422 validation errors with danger notification showing first error message
    - Handle server errors with generic danger notification
    - Refresh table on success with success notification
    - _Requirements: 3.3, 3.4, 3.5, 3.9_

  - [x] 10.3 Implement activate and delete actions
    - Add "Aktifkan" row action (visible when status = Non-Aktif) with confirmation dialog
    - Send PATCH to /tahun-ajaran/{id}/activate
    - Add "Hapus" row action with confirmation dialog
    - Send DELETE to /tahun-ajaran/{id}
    - Handle 409 error (associated data) with danger notification
    - Handle server errors with danger notification
    - Refresh table on success with success notification
    - _Requirements: 3.6, 3.7, 3.8, 3.9_

  - [ ]* 10.4 Write unit tests for TahunAjaranManagement page
    - Test table displays TahunAjaran with correct columns
    - Test create form sends correct API request
    - Test edit form sends correct API request
    - Test activate action sends correct API request
    - Test delete action sends correct API request
    - Test error handling for validation errors and server errors
    - Test navigation hidden without manage-tahun-ajaran permission
    - _Requirements: 3.1, 3.2, 3.3, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10_

- [x] 11. Frontend Period Filter Integration
  - [x] 11.1 Create HasPeriodFilter Livewire trait
    - Create `frontend-v2/app/Livewire/Concerns/HasPeriodFilter.php`
    - Implement `mountHasPeriodFilter()`: load TahunAjaran options from API, restore from session, validate session value, fallback to Periode_Aktif
    - Implement `updatedSelectedTahunAjaranId()`: persist to session, refresh table data
    - Handle session invalidation when stored ID no longer exists
    - Display "Aktif" or "Historis" badge next to selected period
    - _Requirements: 7.1, 7.2, 7.3, 7.5, 7.6_

  - [x] 11.2 Integrate period filter into JenisTagihan page
    - Add `use HasPeriodFilter` trait to JenisTagihan Livewire component
    - Pass `tahun_ajaran_id` query parameter to API call
    - Display period selector dropdown above table
    - Show `tahun_ajaran.nama` column in table
    - Hide selector if no TahunAjaran records available
    - _Requirements: 5.8, 7.1, 7.7_

  - [x] 11.3 Integrate period filter into Tagihan/Card View page
    - Add `use HasPeriodFilter` trait to Tagihan card view Livewire component
    - Pass `tahun_ajaran_id` query parameter to grouped endpoint
    - Display period selector dropdown in header area
    - Resolve siswa kelas from SiswaKelas matching selected period
    - Display empty kelas when no SiswaKelas record exists for selected period
    - Hide selector if no TahunAjaran records available
    - _Requirements: 7.1, 7.7, 9.1, 9.4, 9.5_

  - [x] 11.4 Implement warning banner for no active period
    - Display persistent warning banner on Tagihan and JenisTagihan pages when no Periode_Aktif exists
    - Include link to TahunAjaran management page
    - _Requirements: 2.4_

  - [ ]* 11.5 Write unit tests for HasPeriodFilter trait
    - Test dropdown populates with TahunAjaran options
    - Test session persistence on selection change
    - Test fallback to Periode_Aktif when session value invalid
    - Test selector hidden when no TahunAjaran available
    - Test badge display (Aktif vs Historis)
    - _Requirements: 7.1, 7.2, 7.3, 7.5, 7.6, 7.7_

- [ ] 12. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- Backend tasks (1-9) should be completed before frontend tasks (10-11) since the frontend depends on backend API endpoints
- Migration 1.5 (make NOT NULL) must run AFTER migration 1.4 (data migration) to avoid constraint violations
- The data migration (1.4) uses transactions per branch for atomicity — if one branch fails, others are unaffected
- Existing Siswa CRUD endpoints maintain backward compatibility by auto-creating SiswaKelas records

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3"] },
    { "id": 2, "tasks": ["1.4", "1.6"] },
    { "id": 3, "tasks": ["1.5", "3.1", "3.2", "3.5"] },
    { "id": 4, "tasks": ["3.3"] },
    { "id": 5, "tasks": ["3.4", "3.6", "3.7", "3.8", "3.9", "3.10", "3.11", "3.12"] },
    { "id": 6, "tasks": ["5.1", "5.2"] },
    { "id": 7, "tasks": ["5.3", "5.4", "5.5", "5.6", "7.1"] },
    { "id": 8, "tasks": ["7.2", "7.3", "7.4", "7.5", "8.1"] },
    { "id": 9, "tasks": ["8.2"] },
    { "id": 10, "tasks": ["10.1", "11.1"] },
    { "id": 11, "tasks": ["10.2", "10.3", "11.2", "11.3", "11.4"] },
    { "id": 12, "tasks": ["10.4", "11.5"] }
  ]
}
```
