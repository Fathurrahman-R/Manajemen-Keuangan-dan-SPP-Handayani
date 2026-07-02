# Implementation Plan: Kenaikan Kelas & Kelulusan

## Overview

Implementasi fitur Kenaikan Kelas (promosi siswa) dan Kelulusan pada akhir periode tahun ajaran. Mencakup backend API (Laravel) untuk operasi bulk promotion, individual promotion, graduation, retention, cross-level transfer, dan undo, serta frontend (Livewire + Filament) untuk antarmuka pengguna. Semua operasi di-scope per branch dan dicatat dalam batch untuk traceability.

## Tasks

- [x] 1. Database schema dan model setup
  - [x] 1.1 Buat migration untuk menambahkan kolom `level` pada tabel `kelas`
    - Tambahkan kolom `level` (integer unsigned, nullable, default NULL)
    - Tambahkan unique index pada (jenjang, branch_id, level) WHERE level IS NOT NULL
    - _Requirements: 1.1, 1.6_

  - [x] 1.2 Buat migration untuk tabel `batch_promosis`
    - Buat tabel dengan kolom: id (UUID, PK), batch_type (enum), source_tahun_ajaran_id (FK), target_tahun_ajaran_id (FK), kelas_id (FK nullable), processed_by (FK), processed_at (timestamp), status (enum default 'completed'), branch_id (FK), timestamps
    - Tambahkan index pada (branch_id, status) dan (branch_id, processed_at)
    - _Requirements: 7.1_

  - [x] 1.3 Buat migration untuk tabel `batch_promosi_details`
    - Buat tabel dengan kolom: id (auto-increment), batch_id (FK char(36)), siswa_id (FK), action (enum), source_kelas_id (FK), target_kelas_id (FK nullable), previous_status (varchar(20)), previous_jenjang (varchar(5) nullable), timestamps
    - Tambahkan index pada (batch_id) dan (siswa_id)
    - _Requirements: 7.2_

  - [x] 1.4 Buat model `BatchPromosi` dan `BatchPromosiDetail`
    - BatchPromosi: UUID primary key, fillable fields, relationships (details, sourceTahunAjaran, targetTahunAjaran, kelas, processedBy, branch)
    - BatchPromosiDetail: fillable fields, relationships (batch, siswa, sourceKelas, targetKelas)
    - Tambahkan cast untuk enum fields
    - _Requirements: 7.1, 7.2_

  - [x] 1.5 Update model `Kelas` untuk menambahkan field `level`
    - Tambahkan `level` ke fillable array
    - Tambahkan cast integer untuk `level`
    - _Requirements: 1.1_

- [x] 2. Konfigurasi hierarki kelas dan validasi level
  - [x] 2.1 Update `KelasController` dan `KelasRequest` untuk validasi level
    - Tambahkan rule `level` (nullable, integer, min:1) pada KelasRequest
    - Tambahkan custom validation: level unik dalam jenjang + branch_id (exclude self on update)
    - Return HTTP 422 jika duplicate level terdeteksi
    - _Requirements: 1.2, 1.6_

  - [x] 2.2 Update `KelasResource` untuk menyertakan field `level` dalam response
    - Tambahkan `level` ke array resource
    - _Requirements: 1.2_

  - [x]* 2.3 Write unit tests untuk validasi level pada Kelas
    - Test duplicate level dalam jenjang+branch ditolak (422)
    - Test level NULL diperbolehkan
    - Test level unik antar jenjang berbeda diperbolehkan
    - _Requirements: 1.6_

- [x] 3. Implementasi `KenaikanKelasService` — core logic
  - [x] 3.1 Buat `KenaikanKelasService` dengan helper methods
    - Implementasi `getNextKelas(Kelas $currentKelas): ?Kelas` — query kelas dengan level > current, same jenjang+branch, ORDER BY level ASC LIMIT 1
    - Implementasi `isKelasTertinggi(Kelas $kelas): bool` — cek apakah kelas memiliki level tertinggi dalam jenjang+branch
    - Implementasi `getEligibleStudents(int $kelasId, int $sourceTahunAjaranId): Collection` — siswa aktif dengan SiswaKelas di source period
    - Implementasi `validateAllowedTransition(string $fromJenjang, string $toJenjang): bool` — hanya KB→TK dan TK→MI
    - _Requirements: 1.3, 1.4, 1.7, 6.2_

  - [x]* 3.2 Write property test untuk Property 1: Next Class Resolution Correctness
    - **Property 1: Next Class Resolution Correctness**
    - **Validates: Requirements 1.3**

  - [x]* 3.3 Write property test untuk Property 2: Highest Class Identification
    - **Property 2: Highest Class Identification**
    - **Validates: Requirements 1.4**

  - [x]* 3.4 Write property test untuk Property 4: Level Uniqueness Within Jenjang and Branch
    - **Property 4: Level Uniqueness Within Jenjang and Branch**
    - **Validates: Requirements 1.6**

  - [x]* 3.5 Write property test untuk Property 13: Pindah Jenjang Allowed Transitions Only
    - **Property 13: Pindah Jenjang Allowed Transitions Only**
    - **Validates: Requirements 6.2, 6.5**

- [x] 4. Implementasi Bulk Promotion
  - [x] 4.1 Buat `BulkPromotionRequest` form request
    - Validasi: kelas_id (required, integer, exists:kelas,id), tahun_ajaran_id (required, integer, exists:tahun_ajarans,id)
    - _Requirements: 2.1, 8.1_

  - [x] 4.2 Implementasi `KenaikanKelasService::processBulkPromotion()`
    - Validasi periode tujuan ≠ periode aktif (source)
    - Validasi kelas memiliki eligible students
    - Validasi target kelas exists (next in hierarchy)
    - Wrap dalam DB::transaction()
    - Buat BatchPromosi record
    - Loop eligible students: skip jika sudah ada SiswaKelas di target period, otherwise create SiswaKelas + BatchPromosiDetail
    - Sync siswas.kelas_id jika target = active period
    - Return summary array (total_processed, total_success, total_skipped, skipped list, batch_id)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 8.2, 8.3, 8.4, 8.5_

  - [x]* 4.3 Write property test untuk Property 5: Bulk Promotion Creates Correct SiswaKelas Records
    - **Property 5: Bulk Promotion Creates Correct SiswaKelas Records**
    - **Validates: Requirements 2.1, 2.5**

  - [x]* 4.4 Write property test untuk Property 6: Kelas ID Synchronization on Active Period Operations
    - **Property 6: Kelas ID Synchronization on Active Period Operations**
    - **Validates: Requirements 2.2, 3.5, 4.4, 5.1, 6.6**

  - [x]* 4.5 Write property test untuk Property 8: Skip Students With Existing Target Period Placement
    - **Property 8: Skip Students With Existing Target Period Placement**
    - **Validates: Requirements 2.5**

- [x] 5. Checkpoint - Pastikan semua tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Implementasi Individual Promotion
  - [x] 6.1 Buat `IndividualPromotionRequest` form request
    - Validasi: siswa_id (required, integer, exists:siswas,id), target_kelas_id (required, integer, exists:kelas,id), tahun_ajaran_id (required, integer, exists:tahun_ajarans,id), is_pindah_jenjang (sometimes, boolean)
    - _Requirements: 3.1_

  - [x] 6.2 Implementasi `KenaikanKelasService::processIndividualPromotion()`
    - Validasi target kelas same branch_id as siswa
    - Validasi jenjang match (kecuali is_pindah_jenjang = true)
    - Validasi periode tujuan ≠ source
    - Jika siswa sudah punya SiswaKelas di target period → update kelas_id (upsert)
    - Jika belum → create SiswaKelas baru
    - Sync siswas.kelas_id jika target = active period
    - Catat dalam BatchPromosi + BatchPromosiDetail
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_

  - [x]* 6.3 Write property test untuk Property 9: Individual Promotion Upsert Behavior
    - **Property 9: Individual Promotion Upsert Behavior**
    - **Validates: Requirements 3.4, 5.3**

- [x] 7. Implementasi Kelulusan (Graduation)
  - [x] 7.1 Buat `GraduationRequest` form request
    - Validasi: siswa_ids (required, array, min:1), siswa_ids.* (integer, exists:siswas,id), tahun_ajaran_id (required, integer, exists:tahun_ajarans,id)
    - _Requirements: 4.1, 4.5_

  - [x] 7.2 Implementasi `KenaikanKelasService::processGraduation()`
    - Validasi periode tujuan ≠ source
    - Wrap dalam DB::transaction()
    - Buat BatchPromosi record (batch_type = 'kelulusan')
    - Loop siswa_ids: skip jika status ≠ "Aktif" (reason: status), skip jika bukan Kelas_Tertinggi (reason: bukan kelas tertinggi)
    - Untuk eligible: set status = "Lulus", set kelas_id = NULL, TIDAK buat SiswaKelas di target period
    - Catat dalam BatchPromosiDetail (target_kelas_id = NULL)
    - Return summary (total_graduated, total_skipped, skipped list, batch_id)
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8_

  - [x]* 7.3 Write property test untuk Property 10: Graduation State Invariant
    - **Property 10: Graduation State Invariant**
    - **Validates: Requirements 4.1, 4.3, 4.4**

  - [x]* 7.4 Write property test untuk Property 11: Graduation Eligibility Validation
    - **Property 11: Graduation Eligibility Validation**
    - **Validates: Requirements 4.2, 4.7, 4.8**

- [x] 8. Implementasi Tinggal Kelas (Retention)
  - [x] 8.1 Buat `RetentionRequest` form request
    - Validasi: siswa_ids (required, array, min:1), siswa_ids.* (integer, exists:siswas,id), tahun_ajaran_id (required, integer, exists:tahun_ajarans,id)
    - _Requirements: 5.1_

  - [x] 8.2 Implementasi `KenaikanKelasService::processRetention()`
    - Validasi setiap siswa memiliki SiswaKelas di source period
    - Wrap dalam DB::transaction()
    - Buat BatchPromosi record (batch_type = 'tinggal_kelas')
    - Loop siswa_ids: jika sudah ada SiswaKelas di target period → update kelas_id ke same class, jika belum → create SiswaKelas dengan kelas_id sama dari source period
    - Catat dalam BatchPromosiDetail (source_kelas_id = target_kelas_id)
    - Return summary
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

  - [x]* 8.3 Write property test untuk Property 12: Retention Preserves Same Class
    - **Property 12: Retention Preserves Same Class**
    - **Validates: Requirements 5.1**

- [x] 9. Implementasi Pindah Jenjang (Cross-Level Transfer)
  - [x] 9.1 Buat `CrossLevelTransferRequest` form request
    - Validasi: siswa_id (required, integer, exists:siswas,id), target_kelas_id (required, integer, exists:kelas,id), tahun_ajaran_id (required, integer, exists:tahun_ajarans,id)
    - _Requirements: 6.1_

  - [x] 9.2 Implementasi `KenaikanKelasService::processCrossLevelTransfer()`
    - Validasi siswa status = "Lulus"
    - Validasi allowed transition (KB→TK, TK→MI)
    - Validasi target kelas same branch_id
    - Wrap dalam DB::transaction()
    - Update siswa: jenjang = target kelas jenjang, status = "Aktif", kelas_id = target kelas (jika target = active period)
    - Create SiswaKelas untuk target period
    - Catat dalam BatchPromosi + BatchPromosiDetail (previous_jenjang filled)
    - Return summary
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_

  - [x]* 9.3 Write property test untuk Property 14: Pindah Jenjang Requires Lulus Status
    - **Property 14: Pindah Jenjang Requires Lulus Status**
    - **Validates: Requirements 6.3**

- [x] 10. Checkpoint - Pastikan semua service methods dan tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Implementasi Undo (Pembatalan Proses)
  - [x] 11.1 Implementasi `KenaikanKelasService::undoBatch()`
    - Validasi batch exists, belongs to user's branch, status = "completed"
    - Reject jika status = "undone" (return error)
    - Wrap dalam DB::transaction()
    - Load batch details
    - Loop details: cek apakah SiswaKelas di target period sudah dimodifikasi (kelas_id ≠ target_kelas_id) → skip jika ya
    - Untuk non-modified: delete SiswaKelas di target period, restore siswas.status = previous_status, restore siswas.jenjang = previous_jenjang (jika applicable), restore siswas.kelas_id = source_kelas_id
    - Update batch status = "undone"
    - Return summary (total_restored, total_skipped, skipped list)
    - _Requirements: 7.3, 7.4, 7.5, 7.6, 7.7, 7.8_

  - [x]* 11.2 Write property test untuk Property 15: Undo Restores Original State (Round-Trip)
    - **Property 15: Undo Restores Original State (Round-Trip)**
    - **Validates: Requirements 7.3**

  - [x]* 11.3 Write property test untuk Property 16: Undo Idempotence Guard
    - **Property 16: Undo Idempotence Guard**
    - **Validates: Requirements 7.6**

  - [x]* 11.4 Write property test untuk Property 17: Undo Conflict Detection
    - **Property 17: Undo Conflict Detection**
    - **Validates: Requirements 7.7**

- [x] 12. Implementasi Controller dan Routes
  - [x] 12.1 Buat `KenaikanKelasController` dengan semua endpoint methods
    - bulkPromotion, individualPromotion, graduation, retention, crossLevelTransfer, undo, listBatches, showBatch, eligibleStudents, classHierarchy
    - Setiap method: validate request, extract branch_id dari auth user, delegate ke service, return JSON response
    - _Requirements: 2.1, 3.1, 4.1, 5.1, 6.1, 7.3, 9.1_

  - [x] 12.2 Daftarkan routes di `routes/api.php`
    - Semua routes dengan prefix `/kenaikan-kelas`
    - Middleware: auth:sanctum, permission:manage-kenaikan-kelas
    - _Requirements: 11.1, 11.2_

  - [x]* 12.3 Write property test untuk Property 18: Source and Target Period Must Differ
    - **Property 18: Source and Target Period Must Differ**
    - **Validates: Requirements 8.2, 8.3**

  - [x]* 12.4 Write property test untuk Property 7: Branch Data Isolation
    - **Property 7: Branch Data Isolation**
    - **Validates: Requirements 2.6, 3.2, 6.4, 7.8, 8.1, 8.6, 11.5, 11.6**

  - [x]* 12.5 Write property test untuk Property 19: Permission Enforcement
    - **Property 19: Permission Enforcement**
    - **Validates: Requirements 11.2, 11.3**

- [x] 13. Permission dan Seeder
  - [x] 13.1 Daftarkan permission "manage-kenaikan-kelas" di database seeder
    - Tambahkan ke PermissionSeeder atau equivalent seeder
    - Tambahkan ke Permissions constant/enum jika ada
    - _Requirements: 11.1_

  - [x]* 13.2 Write integration test untuk permission enforcement
    - Test request tanpa permission → 403
    - Test request dengan permission → success
    - _Requirements: 11.2, 11.3_

- [x] 14. Checkpoint - Pastikan semua backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 15. Implementasi Frontend — Livewire Component
  - [x] 15.1 Buat Livewire component `KenaikanKelas`
    - Properties: selectedSourcePeriodId, selectedTargetPeriodId, selectedKelasId, activeJenjangTab, studentActions, kelasList, tahunAjaranOptions
    - Method mount(): load tahun ajaran options, set defaults (source = Periode_Aktif)
    - Method loadKelasList(): fetch kelas grouped by jenjang dengan student count
    - Method loadStudents(): fetch siswa untuk selected kelas dengan default actions
    - _Requirements: 9.1, 9.2, 9.3, 9.4_

  - [x] 15.2 Implementasi student action table dan summary panel
    - Method updateStudentAction($siswaId, $action, $targetKelasId): update individual action
    - Computed property getSummary(): hitung jumlah per action type
    - Aksi dropdown: Naik Kelas (default next class), Tinggal Kelas, Lulus (hanya Kelas_Tertinggi), Pindah Jenjang (hanya Kelas_Tertinggi + target selector)
    - Summary panel: tampilkan count per action type sebelum konfirmasi
    - _Requirements: 9.4, 9.5, 9.6, 9.7_

  - [x] 15.3 Implementasi process dan API integration
    - Method processAll(): kirim batch request ke API, handle response
    - Loading indicator selama processing
    - Success notification dengan summary
    - Error notification dengan pesan error, preserve form state
    - _Requirements: 9.8, 9.9_

  - [x] 15.4 Implementasi history panel dan undo
    - Method loadHistory(): fetch batch history sorted by processed_at desc
    - Tampilkan tabel riwayat: tanggal, tipe, kelas asal, periode, jumlah siswa, status, user
    - Detail view: list siswa affected per batch
    - Undo button (hanya status "completed") dengan confirmation dialog
    - Handle undo response: success notification, warning jika ada skipped
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8_

- [x] 16. Implementasi Frontend — Blade View dan Routing
  - [x] 16.1 Buat Blade view untuk KenaikanKelas page
    - Layout: period selector, class list panel (grouped by jenjang tabs), student action table, summary panel, process button, history table
    - Gunakan Filament components untuk form elements dan notifications
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.7_

  - [x] 16.2 Daftarkan route dan navigation
    - Route: GET /kenaikan-kelas → KenaikanKelas::class, middleware auth + permission:manage-kenaikan-kelas
    - Tambahkan menu item di sidebar, visible hanya jika user has permission
    - Hide page dari navigation jika user tidak punya permission
    - _Requirements: 9.1, 9.10, 10.1_

- [x] 17. Implementasi Frontend — Kelas level field
  - [x] 17.1 Tambahkan field level pada form create/edit Kelas di frontend
    - Tambahkan input field numeric untuk level pada halaman manajemen Kelas
    - Tampilkan validation error jika duplicate level
    - _Requirements: 1.2_

- [x] 18. Final checkpoint - Pastikan semua tests pass dan fitur terintegrasi
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- Semua operasi backend menggunakan DB::transaction() untuk atomicity
- Frontend menggunakan Livewire + Filament sesuai arsitektur existing project
- Permission "manage-kenaikan-kelas" harus di-seed sebelum fitur dapat digunakan

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3"] },
    { "id": 1, "tasks": ["1.4", "1.5"] },
    { "id": 2, "tasks": ["2.1", "2.2", "3.1"] },
    { "id": 3, "tasks": ["2.3", "3.2", "3.3", "3.4", "3.5"] },
    { "id": 4, "tasks": ["4.1", "6.1", "7.1", "8.1", "9.1"] },
    { "id": 5, "tasks": ["4.2", "6.2", "7.2", "8.2", "9.2"] },
    { "id": 6, "tasks": ["4.3", "4.4", "4.5", "6.3", "7.3", "7.4", "8.3", "9.3"] },
    { "id": 7, "tasks": ["11.1"] },
    { "id": 8, "tasks": ["11.2", "11.3", "11.4"] },
    { "id": 9, "tasks": ["12.1", "13.1"] },
    { "id": 10, "tasks": ["12.2"] },
    { "id": 11, "tasks": ["12.3", "12.4", "12.5", "13.2"] },
    { "id": 12, "tasks": ["15.1", "17.1"] },
    { "id": 13, "tasks": ["15.2"] },
    { "id": 14, "tasks": ["15.3", "15.4"] },
    { "id": 15, "tasks": ["16.1"] },
    { "id": 16, "tasks": ["16.2"] }
  ]
}
```
