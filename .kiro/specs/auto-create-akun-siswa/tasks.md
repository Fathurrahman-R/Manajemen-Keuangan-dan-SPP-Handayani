# Implementation Plan: Auto-Create Akun Siswa

## Overview

Implementasi fitur auto-create akun siswa untuk sistem manajemen sekolah Handayani. Fitur ini mencakup pembuatan akun otomatis saat siswa didaftarkan, parent search & link, sibling tagihan view, credential management, bulk account creation, auto-deactivation, dan role/permission setup. Implementasi menggunakan Laravel (backend API) dan Filament/Livewire (frontend-v2).

## Tasks

- [x] 1. Database schema dan role setup
  - [x] 1.1 Create migration to add `siswa_id`, `is_active`, and `must_change_password` columns to `users` table
    - Add `siswa_id` (bigint unsigned, nullable, foreign key to siswas.id ON DELETE SET NULL)
    - Add `is_active` (boolean, default true)
    - Add `must_change_password` (boolean, default false)
    - _Requirements: 2.6, 4.4, 6.1_

  - [x] 1.2 Create database seeder to register "siswa" role with spatie/laravel-permission
    - Add "siswa" role to the roles table via seeder
    - Assign only `view-tagihan-siswa` permission to the role
    - Ensure the role has NO admin panel permissions
    - _Requirements: 8.1, 8.2_

  - [x] 1.3 Update User model to add `siswa_id`, `is_active`, `must_change_password` fields and relationship to Siswa
    - Add fillable fields
    - Add `siswa()` belongsTo relationship
    - Add scope `scopeActive()` for filtering active accounts
    - _Requirements: 2.6, 6.1_

- [x] 2. AkunSiswaService — core account creation logic
  - [x] 2.1 Create `App\Services\AkunSiswaService` with `createAccount(Siswa $siswa): ?User` method
    - Generate username from NIS
    - Generate password from tanggal_lahir (DDMMYYYY format) using `Carbon::parse()->format('dmY')`
    - Set name from siswa nama, branch_id from siswa branch_id, siswa_id from siswa id
    - Assign "siswa" role via spatie
    - Set is_active=true, must_change_password=true
    - Check for duplicate NIS in same branch before creation, return null and log warning if exists
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 7.1_

  - [x]* 2.2 Write property test for Account Creation Invariants
    - **Property 1: Account Creation Invariants**
    - Generate random valid Siswa data and verify all account fields match spec
    - **Validates: Requirements 2.1, 2.3, 2.4, 2.5, 2.6, 4.4, 7.1**

  - [x]* 2.3 Write property test for Password Generation Round-Trip
    - **Property 2: Password Generation Round-Trip**
    - Generate random dates, create password, verify Hash::check returns true
    - **Validates: Requirements 2.2, 4.1**

  - [x]* 2.4 Write property test for Duplicate NIS Idempotency
    - **Property 3: Duplicate NIS Idempotency**
    - Create siswa with existing NIS username in same branch, verify no new User created
    - **Validates: Requirements 2.7**

  - [x] 2.5 Add `generateDefaultPassword(string $tanggalLahir): string` method to AkunSiswaService
    - Parse Y-m-d date and return DDMMYYYY string
    - _Requirements: 2.2_

  - [x] 2.6 Add `resetPassword(User $user): void` method to AkunSiswaService
    - Fetch linked siswa tanggal_lahir, hash and set as password
    - Set must_change_password=true
    - _Requirements: 4.1_

  - [x] 2.7 Add `deactivateAccount(Siswa $siswa): void` and `activateAccount(Siswa $siswa): void` methods
    - Find User by siswa_id, set is_active accordingly
    - _Requirements: 6.1, 6.4_

  - [x] 2.8 Add `bulkCreateAccounts(Collection $siswaList): array` method
    - Iterate over collection, call createAccount for each within try-catch
    - Collect results: created count and errors array
    - Do NOT wrap in single transaction (partial success acceptable)
    - _Requirements: 5.3, 5.5_

  - [x]* 2.9 Write property test for Bulk Creation Error Resilience
    - **Property 10: Bulk Creation Error Resilience**
    - Submit batch with some conflicting NIS, verify non-conflicting accounts created
    - **Validates: Requirements 5.5**

- [x] 3. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. SiswaObserver — auto-deactivation/activation
  - [x] 4.1 Create `App\Observers\SiswaObserver` and register it in `AppServiceProvider`
    - Listen to `updated` event on Siswa model
    - When status changes to "Lulus", "Pindah", or "Keluar" → call `AkunSiswaService::deactivateAccount()`
    - When status changes to "Aktif" → call `AkunSiswaService::activateAccount()`
    - _Requirements: 6.1, 6.4_

  - [x]* 4.2 Write property test for Status-Based Account Deactivation
    - **Property 7: Status-Based Account Deactivation**
    - Change siswa status to inactive statuses, verify is_active=false; change to Aktif, verify is_active=true
    - **Validates: Requirements 6.1, 6.4**

- [x] 5. SiblingDetectionService
  - [x] 5.1 Create `App\Services\SiblingDetectionService` with `findSiblings(Siswa $siswa): Collection` method
    - Query siswa records sharing non-null ayah_id, ibu_id, or wali_id with input siswa
    - Exclude the input siswa itself
    - Scope to same branch_id
    - _Requirements: 3.1, 7.3_

  - [x]* 5.2 Write property test for Sibling Detection Correctness
    - **Property 5: Sibling Detection Correctness**
    - Generate siswa with shared parent IDs, verify correct siblings returned
    - **Validates: Requirements 3.1, 7.3**

- [x] 6. Modify SiswaController — integrate account creation and parent linking
  - [x] 6.1 Modify `SiswaController::create` to call `AkunSiswaService::createAccount()` after siswa is saved
    - Inject AkunSiswaService
    - Call createAccount after successful siswa creation
    - _Requirements: 2.1_

  - [x] 6.2 Modify `SiswaController::create` to accept optional `ayah_id`, `ibu_id`, `wali_id` for linking existing parents
    - If ayah_id/ibu_id/wali_id is provided, use existing record instead of creating new
    - If not provided, create new parent record as current behavior
    - Update SiswaRequest/SiswaKBRequest/SiswaMIRequest/SiswaTKRequest validation rules
    - _Requirements: 1.4, 1.5_

  - [x]* 6.3 Write property test for Parent Record Linking
    - **Property 4: Parent Record Linking**
    - Provide existing parent ID, verify siswa references it and no new parent created
    - **Validates: Requirements 1.4, 1.5**

- [x] 7. Parent search endpoints
  - [x] 7.1 Create `AyahController` with `index` method supporting `?search=nama` query parameter
    - Return Ayah records filtered by nama (LIKE search)
    - Scope to admin's active branch (via siswa relationship)
    - _Requirements: 1.1, 1.6_

  - [x] 7.2 Create `IbuController` with `index` method supporting `?search=nama` query parameter
    - Return Ibu records filtered by nama (LIKE search)
    - Scope to admin's active branch (via siswa relationship)
    - _Requirements: 1.2, 1.6_

  - [x] 7.3 Register API routes for `/ayah` and `/ibu` endpoints with appropriate middleware
    - Add routes with authentication and permission middleware
    - _Requirements: 1.1, 1.2_

- [x] 8. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. AkunSiswaController — account management endpoints
  - [x] 9.1 Create `AkunSiswaController` with `index` method (list akun siswa, branch-scoped)
    - Return paginated list of users with role "siswa" in admin's active branch
    - _Requirements: 7.2_

  - [x] 9.2 Add `unregistered` method to list siswa without accounts (with jenjang/kelas filters)
    - Query siswa where no User has matching siswa_id
    - Support `?jenjang=` and `?kelas_id=` query filters
    - Scope to admin's active branch
    - _Requirements: 5.1, 5.2_

  - [x] 9.3 Add `bulkCreate` method accepting array of siswa IDs
    - Validate siswa IDs exist and belong to admin's branch
    - Call `AkunSiswaService::bulkCreateAccounts()`
    - Return summary with created count and errors
    - _Requirements: 5.3, 5.4_

  - [x] 9.4 Add `resetPassword` method for single account password reset
    - Find user by ID, verify branch ownership
    - Call `AkunSiswaService::resetPassword()`
    - _Requirements: 4.1_

  - [x] 9.5 Add `toggleActive` method for manual activate/deactivate
    - Toggle is_active field on the user
    - _Requirements: 6.3_

  - [x] 9.6 Add `credentials` method to return username and password pattern for selected accounts
    - Accept array of user IDs via query param
    - Return username (NIS) and password description for each
    - _Requirements: 4.2_

  - [x] 9.7 Add `credentialsPdf` method to generate printable PDF
    - Use PDF library (e.g., barryvdh/laravel-dompdf) to generate credential cards
    - Format: one account per row with username and password info
    - _Requirements: 4.3_

  - [x] 9.8 Register all `/akun-siswa` API routes with authentication and permission middleware
    - Add `manage-akun-siswa` permission check
    - _Requirements: 7.2_

  - [x]* 9.9 Write property test for Branch Isolation for Account Management
    - **Property 9: Branch Isolation for Account Management**
    - Query akun-siswa endpoints as admin, verify all results match admin's branch_id
    - **Validates: Requirements 7.2**

  - [x]* 9.10 Write property test for Bulk Filter Correctness
    - **Property 12: Bulk Filter Correctness**
    - Apply jenjang/kelas filters, verify all returned siswa match filters and have no account
    - **Validates: Requirements 5.1, 5.2**

- [x] 10. AuthController modifications — login validation
  - [x] 10.1 Modify `AuthController::login` to check `is_active` field before issuing token
    - If is_active=false, return 401 with message "Akun tidak aktif. Hubungi admin sekolah."
    - Include `must_change_password` flag in successful login response
    - _Requirements: 6.2, 4.5_

  - [x]* 10.2 Write property test for Inactive Account Login Rejection
    - **Property 8: Inactive Account Login Rejection**
    - Set user is_active=false, attempt login with correct credentials, verify rejection
    - **Validates: Requirements 6.2**

- [x] 11. Password change endpoint
  - [x] 11.1 Add `changePassword` method to `UserController`
    - Accept current_password and new_password
    - Validate new password (min 8 chars)
    - Update password and set must_change_password=false
    - Register route: POST `/users/change-password`
    - _Requirements: 4.5, 4.6_

  - [x]* 11.2 Write property test for Password Change State Transition
    - **Property 13: Password Change State Transition**
    - User with must_change_password=true submits valid password, verify field becomes false and new password works
    - **Validates: Requirements 4.6**

- [x] 12. Sibling tagihan endpoint
  - [x] 12.1 Add `siswaView` method to `TagihanController`
    - Get logged-in user's siswa_id
    - Use SiblingDetectionService to find siblings
    - Accept optional `?siswa_id=X` param to view sibling tagihan
    - Validate requested siswa_id is either self or a detected sibling
    - Default to account owner's tagihan
    - Return tagihan data with sibling list for selector
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [x] 12.2 Register route: GET `/tagihan/siswa` with role "siswa" middleware
    - _Requirements: 3.1_

  - [x]* 12.3 Write property test for Sibling Tagihan Isolation
    - **Property 6: Sibling Tagihan Isolation**
    - Select sibling from selector, verify all returned tagihan belong to selected sibling only
    - **Validates: Requirements 3.3**

- [x] 13. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 14. Role access control middleware
  - [x] 14.1 Add middleware to deny "siswa" role access to admin panel routes
    - Return 403 Forbidden when user with only "siswa" role accesses admin routes
    - _Requirements: 8.3_

  - [x]* 14.2 Write property test for Siswa Role Access Denial
    - **Property 11: Siswa Role Access Denial**
    - Authenticate as user with "siswa" role, request admin routes, verify 403 response
    - **Validates: Requirements 8.3**

- [x] 15. Frontend — Parent search in DataSiswa form
  - [x] 15.1 Modify DataSiswa Livewire component to add searchable Select for Ayah (jenjang MI)
    - Use Filament Select component with `searchable()` and `getSearchResultsUsing()`
    - Call `/ayah?search=` API endpoint
    - When selected, populate ayah fields and pass ayah_id to backend
    - When not selected, allow manual input (create new)
    - _Requirements: 1.1, 1.4, 1.5_

  - [x] 15.2 Modify DataSiswa Livewire component to add searchable Select for Ibu (jenjang MI)
    - Same pattern as Ayah search
    - Call `/ibu?search=` API endpoint
    - _Requirements: 1.2, 1.4, 1.5_

  - [x] 15.3 Modify DataSiswa Livewire component to add searchable Select for Wali (jenjang TK/KB)
    - Use existing Wali search endpoint
    - _Requirements: 1.3, 1.4, 1.5_

- [x] 16. Frontend — Bulk Account Creation page
  - [x] 16.1 Create `BulkAkunSiswa` Livewire/Filament page
    - Table displaying unregistered siswa (from `/akun-siswa/unregistered` endpoint)
    - Filter by jenjang and kelas
    - Checkbox selection with "Select All" button
    - "Buat Akun" action button calling `/akun-siswa/bulk` endpoint
    - Display summary modal after completion (created count + errors)
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.6_

- [x] 17. Frontend — Account Management page
  - [x] 17.1 Create `ManajemenAkunSiswa` Livewire/Filament page
    - Table listing akun siswa (from `/akun-siswa` endpoint)
    - Actions per row: Reset Password, Toggle Active
    - Bulk action: View Credentials, Print PDF
    - _Requirements: 4.1, 4.2, 4.3, 6.3_

- [x] 18. Frontend — Sibling Tagihan View
  - [x] 18.1 Create or modify `TagihanSiswa` Livewire component for siswa role users
    - Sibling selector dropdown (hidden if no siblings)
    - Card view displaying tagihan for selected siswa
    - Default to account owner's tagihan
    - Integrate with tagihan-card-view spec
    - _Requirements: 3.2, 3.3, 3.4, 3.5_

- [x] 19. Frontend — Force Change Password page
  - [x] 19.1 Create `ChangePassword` Livewire page
    - Form with current_password, new_password, new_password_confirmation
    - Redirect here when must_change_password=true after login
    - Call `/users/change-password` endpoint
    - On success, redirect to main dashboard/tagihan view
    - _Requirements: 4.5, 4.6_

- [x] 20. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The backend uses PHP/Laravel with Pest for testing
- The frontend uses Filament/Livewire components
- All endpoints are branch-scoped per existing codebase patterns
- PDF generation uses barryvdh/laravel-dompdf (or similar library already in the project)

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["1.3", "2.5"] },
    { "id": 2, "tasks": ["2.1", "5.1"] },
    { "id": 3, "tasks": ["2.2", "2.3", "2.4", "2.6", "2.7", "5.2"] },
    { "id": 4, "tasks": ["2.8", "4.1"] },
    { "id": 5, "tasks": ["2.9", "4.2", "6.1", "6.2"] },
    { "id": 6, "tasks": ["6.3", "7.1", "7.2"] },
    { "id": 7, "tasks": ["7.3", "9.1", "9.2"] },
    { "id": 8, "tasks": ["9.3", "9.4", "9.5", "9.6"] },
    { "id": 9, "tasks": ["9.7", "9.8", "9.9", "9.10"] },
    { "id": 10, "tasks": ["10.1", "11.1", "12.1"] },
    { "id": 11, "tasks": ["10.2", "11.2", "12.2", "12.3"] },
    { "id": 12, "tasks": ["14.1"] },
    { "id": 13, "tasks": ["14.2"] },
    { "id": 14, "tasks": ["15.1", "15.2", "15.3"] },
    { "id": 15, "tasks": ["16.1", "17.1", "18.1", "19.1"] }
  ]
}
```
