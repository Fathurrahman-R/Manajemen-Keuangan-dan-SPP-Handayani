# Implementation Plan: Tagihan Card View

## Overview

This plan transforms the tagihan (billing) page from a table view to a card-based layout grouped by student. The implementation progresses from backend API endpoints (grouped tagihan + batch payment), through a new Livewire component with Alpine.js interactivity, to role-based views and error handling. Each step builds incrementally, ensuring the existing single-payment endpoints remain untouched for backward compatibility.

## Tasks

- [x] 1. Backend API - Grouped Tagihan Endpoint
  - [x] 1.1 Create TagihanGroupedResource for the grouped response
    - Create `backend/app/Http/Resources/TagihanGroupedResource.php`
    - Return siswa fields: nis, nama, jenjang, kelas (using KelasResource with whenLoaded)
    - Return nested tagihan array using TagihanResource::collection with whenLoaded
    - Ensure each tagihan includes kode_tagihan, jenis_tagihan (nama, jumlah, jatuh_tempo), tmp, status
    - _Requirements: 1.2, 1.3, 2.1, 7.2_

  - [x] 1.2 Implement TagihanController::grouped endpoint
    - Add `grouped()` method to `backend/app/Http/Controllers/TagihanController.php`
    - Query Siswa model with `whereHas('tagihan')` to exclude students without bills
    - Apply search filter (case-insensitive substring match on nama and nis)
    - Apply jenjang filter (exact match on siswa.jenjang)
    - Apply status filter via `whereHas('tagihan', fn($q) => $q->where('status', $status))`
    - For non-admin users, filter by `nis = auth()->user()->username`
    - Scope all queries to `branch_id = auth()->user()->branch_id`
    - Paginate at siswa level with configurable per_page (default 10, max 100)
    - Eager load `tagihan.jenisTagihan` and `kelas` for each siswa
    - Sort siswa alphabetically by nama (ascending)
    - Return paginated response using TagihanGroupedResource
    - _Requirements: 1.1, 1.5, 1.6, 1.7, 3.1, 3.2, 3.3, 3.4, 3.5, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_

  - [x] 1.3 Register the grouped endpoint route
    - Add `GET /tagihan/grouped` route in `backend/routes/api.php`
    - Apply `auth:sanctum` and `permission:view-tagihan` middleware
    - Place before existing tagihan resource routes to avoid route conflicts
    - _Requirements: 7.1_

  - [ ]* 1.4 Write property tests for grouped endpoint (Properties 1-4, 7-9)
    - **Property 1: Grouped Response Alphabetical Sorting** — Verify siswa array sorted by nama ascending
    - **Property 2: Grouped Response Completeness** — Verify all required fields present for each siswa and tagihan
    - **Property 3: Only Siswa With Tagihan Included** — Verify every siswa has non-empty tagihan array
    - **Property 4: Branch Data Isolation** — Verify all returned data belongs to authenticated user's branch_id
    - **Property 7: Search Filter Correctness** — Verify search matches nama or nis as case-insensitive substring
    - **Property 8: Jenjang and Status Filter Correctness** — Verify filter results match criteria exactly
    - **Property 9: Pagination Limit** — Verify response contains at most min(per_page, 100) siswa records
    - Use PHPUnit with Laravel model factories, 100 iterations per property
    - **Validates: Requirements 1.1, 1.2, 1.5, 1.6, 3.1, 3.2, 3.3, 7.4, 7.6**

- [x] 2. Backend API - Batch Payment Endpoint
  - [x] 2.1 Create BatchPaymentRequest form request
    - Create `backend/app/Http/Requests/BatchPaymentRequest.php`
    - Validate kode_tagihan: required, array, min:1, max:50
    - Validate kode_tagihan.*: required, string, exists:tagihans,kode_tagihan
    - Validate metode: required, in:Tunai,Non-Tunai
    - Validate pembayar: required, string, max:100
    - Add custom error messages in Indonesian
    - _Requirements: 8.1, 8.7_

  - [x] 2.2 Implement PembayaranController::batchLunas endpoint
    - Add `batchLunas()` method to `backend/app/Http/Controllers/PembayaranController.php`
    - Verify all tagihan belong to authenticated user's branch_id
    - Verify none of the tagihan have status "Lunas" (reject with 400 if any do)
    - Wrap in DB::transaction for atomicity
    - For each kode_tagihan: load tagihan with jenisTagihan, calculate jumlah (jenisTagihan.jumlah - tmp), create Pembayaran record with generated kode_pembayaran, update tagihan status to "Lunas" and tmp to jenisTagihan.jumlah
    - Return created pembayaran records using PembayaranResource with HTTP 200
    - Return HTTP 400 with specific kode_tagihan on validation failure
    - Return HTTP 500 with generic message on transaction failure
    - _Requirements: 5.4, 5.5, 5.6, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_

  - [x] 2.3 Register the batch payment route
    - Add `POST /pembayaran/batch` route in `backend/routes/api.php`
    - Apply `auth:sanctum` and `permission:view-pembayaran` middleware
    - Place before existing pembayaran resource routes
    - _Requirements: 8.1_

  - [ ]* 2.4 Write property tests for batch payment (Properties 11, 12, 15)
    - **Property 11: Batch Payment Record Creation** — Verify N tagihan creates N pembayaran records with correct jumlah, and all tagihan become Lunas
    - **Property 12: Batch Payment Transaction Atomicity** — Verify rollback on failure (no records persisted, no status changes)
    - **Property 15: Batch Validation Rejects Invalid Tagihan** — Verify rejection for non-existent, wrong-branch, or already-Lunas tagihan
    - Use PHPUnit with Laravel model factories, 100 iterations per property
    - **Validates: Requirements 5.4, 5.5, 5.6, 8.2, 8.3, 8.4, 8.5, 8.6**

- [x] 3. Backend - Deletion Prevention and Sisa Calculation
  - [x] 3.1 Add deletion prevention for tagihan with pembayaran
    - Update `TagihanController::destroy()` to check for associated pembayaran records
    - If pembayaran exists, return HTTP 409 with message "tagihan sudah dibayar dan tidak dapat dihapus."
    - If no pembayaran, proceed with existing deletion logic
    - _Requirements: 10.3_

  - [ ]* 3.2 Write property tests for deletion prevention and sisa calculation (Properties 5, 16)
    - **Property 5: Sisa Calculation Invariant** — Verify sisa = jenis_tagihan.jumlah - tmp >= 0 for all tagihan
    - **Property 16: Deletion Prevention For Paid Tagihan** — Verify delete rejected when pembayaran exists, allowed when none
    - Use PHPUnit with Laravel model factories, 100 iterations per property
    - **Validates: Requirements 2.2, 2.5, 6.3, 10.3**

- [ ] 4. Checkpoint - Ensure all backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Frontend - TagihanCardView Livewire Component (Admin View)
  - [x] 5.1 Create TagihanCardView Livewire component class
    - Create `frontend-v2/app/Livewire/TagihanCardView.php`
    - Define public properties: search, filterJenjang, filterStatus, perPage (default 5), page (default 1)
    - Implement `loadData()` method: call GET /tagihan/grouped with current filters via ApiService
    - Implement `updatedSearch()`, `updatedFilterJenjang()`, `updatedFilterStatus()` to reset page to 1
    - Implement `isAdmin()` method: check session permissions for create/delete capabilities
    - Implement `deleteTagihan(string $kodeTagihan)` method: call DELETE /tagihan/{kode} via ApiService
    - Implement `addTagihan(array $data)` method: call POST /tagihan via ApiService
    - Implement `batchPay(array $data)` method: call POST /pembayaran/batch via ApiService
    - Implement `downloadKwitansi(array $kodePembayaran)` method: call GET /pembayaran/kwitansi/{kode}
    - Handle API errors with Filament Notification (danger, persistent)
    - Handle network errors with generic "Server tidak dapat dihubungi" notification
    - _Requirements: 1.1, 1.6, 3.1, 3.2, 3.3, 3.4, 3.5, 5.3, 5.7, 9.1, 9.2, 9.5, 9.6, 10.1, 10.2_

  - [x] 5.2 Create the main Blade view for TagihanCardView
    - Create `frontend-v2/resources/views/livewire/tagihan-card-view.blade.php`
    - Build header section: search input (wire:model.live.debounce.300ms), jenjang select filter, status select filter
    - Add "Tambah Tagihan" button (visible only if user has create-tagihan permission)
    - Build siswa card list section iterating over loaded data
    - Build pagination section with per-page selector (5, 10, 25) and page navigation
    - Display empty state message when no siswa match filters
    - Use responsive layout (single-column stack below 768px)
    - _Requirements: 1.1, 1.7, 3.1, 3.2, 3.3, 3.5, 3.6, 6.6, 10.1, 10.4_

  - [x] 5.3 Build the Siswa Card sub-component within the Blade view
    - Display card header: siswa nama, NIS, jenjang, kelas nama
    - Separate tagihan into two sections: unpaid (Belum Dibayar / Belum Lunas) on top, paid (Lunas) on bottom
    - For each tagihan row: display jenis_tagihan nama, jumlah (Rp. format), jatuh_tempo, tmp, sisa, status badge
    - Status badge colors: Lunas → success (green), Belum Lunas → warning (yellow), Belum Dibayar → danger (red)
    - Display overdue indicator when jatuh_tempo < today and status != Lunas
    - Display total sisa for all unpaid tagihan in the card
    - Add delete button per tagihan (visible only if user has delete-tagihan permission) with confirmation
    - Prevent deletion of tagihan with pembayaran (show error notification from API 409 response)
    - _Requirements: 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 10.2, 10.3, 10.4_

  - [x] 5.4 Implement Alpine.js checkbox selection and rekap calculation
    - Add `x-data` to each siswa card with state: selectedTagihan array, submitting boolean
    - Add checkbox per unpaid tagihan (no checkbox for Lunas items)
    - Implement "Pilih Semua" toggle that selects/deselects all unpaid tagihan
    - Sync "Pilih Semua" state: checked when all individual checkboxes are checked, unchecked otherwise
    - Calculate Rekap_Pembayaran total reactively (sum of sisa for selected tagihan) using Alpine computed
    - Display Rekap_Pembayaran only when at least one tagihan is selected
    - Disable "Bayar" button when no tagihan selected
    - All selection logic is client-side only (no Livewire round-trip)
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [ ]* 5.5 Write property test for Rekap Calculation (Property 10)
    - **Property 10: Rekap Calculation** — Verify displayed total equals sum of (jumlah - tmp) for selected tagihan
    - Test with randomized tagihan amounts and selection combinations
    - **Validates: Requirements 4.2**

- [x] 6. Frontend - Batch Payment Modal
  - [x] 6.1 Implement batch payment modal using Filament Action
    - Create payment modal triggered by "Bayar" button on each siswa card
    - Display list of selected tagihan with jenis_tagihan nama and sisa amount
    - Display total amount to be paid
    - Add form fields: metode (Select: Tunai/Non-Tunai, required), pembayar (TextInput, required, max:100)
    - Disable submit button until both fields are filled (wire:loading.attr="disabled" + Alpine x-bind:disabled)
    - On submit: call batchPay() with selected kode_tagihan, metode, pembayar
    - On success: refresh card data, show success notification, offer kwitansi download option
    - On failure: show error notification with specific kode_tagihan that caused failure
    - Prevent duplicate submissions with loading state
    - _Requirements: 5.1, 5.2, 5.3, 5.6, 5.7, 5.8, 5.9, 9.5, 9.7_

  - [x] 6.2 Implement kwitansi download after batch payment
    - After successful batch payment, provide download button for combined kwitansi PDF
    - Call GET /pembayaran/kwitansi/{kode} for each created pembayaran
    - Stream PDF download to browser
    - _Requirements: 5.8_

- [ ] 7. Checkpoint - Ensure admin view works end-to-end
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Frontend - Student/Wali View
  - [x] 8.1 Implement role-based rendering in TagihanCardView
    - Detect user role from session (check if user has admin/operator permissions)
    - For siswa/wali users: hide add tagihan button, hide delete buttons, hide batch payment controls
    - For siswa/wali users: display only their own tagihan (API already filters by NIS)
    - Sort tagihan by jatuh_tempo ascending (nearest due date first) for student view
    - _Requirements: 6.1, 6.4, 6.5_

  - [x] 8.2 Build student summary section
    - Display summary card showing: total jumlah tagihan (sum of all jumlah), total sudah dibayar (sum of all tmp), total sisa (sum of all remaining balances)
    - Format all amounts in Rupiah (Rp. prefix, no decimals)
    - Display each tagihan as a card with: jenis_tagihan nama, jumlah, jatuh_tempo, tmp, sisa, status badge
    - Display empty state when siswa has no tagihan records
    - _Requirements: 6.2, 6.3, 6.7_

  - [ ]* 8.3 Write property tests for student view (Properties 13, 14)
    - **Property 13: Non-Admin Data Isolation** — Verify non-admin users only see tagihan belonging to their NIS
    - **Property 14: Student View Sorting** — Verify tagihan sorted by jatuh_tempo ascending for student users
    - Use PHPUnit with Laravel model factories, 100 iterations per property
    - **Validates: Requirements 6.1, 6.5**

- [x] 9. Frontend - Add Tagihan Form and Error Handling
  - [x] 9.1 Implement add tagihan form modal
    - Create modal with form fields: jenis_tagihan (select), jenjang (select), kelas (select), kategori (select)
    - All fields required before submission
    - On submit: call POST /tagihan via ApiService
    - On success: show success notification, refresh tagihan list
    - On failure: show error notification from API response
    - Visible only when user has create-tagihan permission
    - _Requirements: 10.1, 10.6, 10.7_

  - [x] 9.2 Implement consistent error handling across all actions
    - Extract error message from API response `errors.message[0]` or first value in `errors` object
    - Display via Filament Notification with danger severity and persistent flag
    - Handle network errors (ConnectionException) with "Server tidak dapat dihubungi" message
    - Handle non-JSON error responses with generic error message
    - Display inline validation errors below form fields using Filament form components
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.6, 9.7_

- [x] 10. Frontend - Route Registration and Navigation
  - [x] 10.1 Register TagihanCardView route and replace existing tagihan page
    - Update route registration to point tagihan page to TagihanCardView component
    - Ensure navigation item uses view-tagihan permission for visibility
    - Implement mount() authorization check (abort 403 if user lacks view-tagihan permission)
    - Verify existing single-payment endpoints remain accessible (backward compatibility)
    - _Requirements: 10.4, 10.5_

  - [ ]* 10.2 Write property test for Overdue Detection (Property 6)
    - **Property 6: Overdue Detection** — Verify tagihan with jatuh_tempo < today and status != Lunas is flagged as overdue
    - Test with randomized dates and statuses
    - **Validates: Requirements 2.6**

- [ ] 11. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- Backend tasks (1-3) should be completed before frontend tasks (5-10) since the frontend depends on backend API endpoints
- The existing single-payment endpoints (POST /pembayaran/lunas/{kode} and POST /pembayaran/bayar/{kode}) remain unchanged
- Alpine.js handles all client-side interactivity (checkbox selection, total calculation) without Livewire round-trips
- The TagihanCardView component uses ApiService (same pattern as existing Pembayaran component) for all backend communication

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "2.1"] },
    { "id": 1, "tasks": ["1.2", "2.2", "3.1"] },
    { "id": 2, "tasks": ["1.3", "2.3", "1.4", "2.4", "3.2"] },
    { "id": 3, "tasks": ["5.1"] },
    { "id": 4, "tasks": ["5.2", "5.3"] },
    { "id": 5, "tasks": ["5.4", "5.5", "6.1"] },
    { "id": 6, "tasks": ["6.2", "8.1"] },
    { "id": 7, "tasks": ["8.2", "8.3", "9.1"] },
    { "id": 8, "tasks": ["9.2", "10.1"] },
    { "id": 9, "tasks": ["10.2"] }
  ]
}
```
