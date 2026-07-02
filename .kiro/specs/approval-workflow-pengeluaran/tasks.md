# Implementation Plan: Approval Workflow Pengeluaran

## Overview

Implementasi workflow approval multi-tahap untuk pengeluaran kas sekolah. Fitur ini menambahkan state machine (draft → submitted → approved/rejected → disbursed), audit trail, auto-approval, notifikasi in-app, dan isolasi data per branch. Implementasi menggunakan Laravel (PHP) dengan React frontend, mengikuti pola arsitektur yang sudah ada di codebase.

## Tasks

- [ ] 1. Database migrations dan model setup
  - [ ] 1.1 Create migration for `pengeluaran_requests` table
    - Create migration file with columns: id, uraian, jumlah (decimal 13,2), tanggal_kebutuhan, kategori_pengeluaran (nullable), lampiran (nullable), status (enum: draft/submitted/approved/rejected/disbursed, default draft), requester_id (FK users), branch_id (FK branches), timestamps
    - Add composite index on [branch_id, status] and index on [requester_id]
    - _Requirements: 1.1, 1.2, 1.3, 10.1_

  - [ ] 1.2 Create migration for `approval_logs` table
    - Create migration file with columns: id, pengeluaran_request_id (FK, cascade delete), previous_status, new_status, user_id (FK users), note (text nullable), created_at (timestamp)
    - Add composite index on [pengeluaran_request_id, created_at]
    - _Requirements: 6.1, 6.3_

  - [ ] 1.3 Create migration for `branch_approval_settings` table
    - Create migration file with columns: id, branch_id (FK branches, unique), auto_approval_enabled (boolean default false), auto_approval_threshold (decimal 13,2 default 0), timestamps
    - _Requirements: 8.3, 8.4_

  - [ ] 1.4 Create migration for `notifications` table
    - Create migration file with columns: id, user_id (FK users, cascade delete), type (string), title (string), message (text), data (json nullable), is_read (boolean default false), created_at (timestamp)
    - Add composite index on [user_id, is_read, created_at]
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

  - [ ] 1.5 Create migration to add `pengeluaran_request_id` column to `pengeluarans` table
    - Add nullable foreign key column `pengeluaran_request_id` referencing `pengeluaran_requests`, nullOnDelete
    - _Requirements: 4.4, 5.1_

  - [ ] 1.6 Create Eloquent models: PengeluaranRequest, ApprovalLog, Notification, BranchApprovalSetting
    - Define relationships: PengeluaranRequest belongsTo User (requester), belongsTo Branch, hasMany ApprovalLog; ApprovalLog belongsTo PengeluaranRequest, belongsTo User; Notification belongsTo User; BranchApprovalSetting belongsTo Branch
    - Add `pengeluaran_request_id` relationship on existing Pengeluaran model (belongsTo PengeluaranRequest, nullable)
    - Define fillable attributes and casts (jumlah as decimal, status as enum, data as array)
    - _Requirements: 1.1, 1.2, 1.3, 6.1, 8.3_

- [ ] 2. Permission seeder dan konfigurasi akses
  - [ ] 2.1 Create permission seeder for new workflow permissions
    - Add permissions: `create-pengeluaran-request`, `approve-pengeluaran`, `disburse-pengeluaran` to the existing permission seeder or create a new seeder
    - Follow the existing pattern in `Constant/Permissions.php` and `Enum/Permission.php`
    - _Requirements: 9.4_

  - [ ] 2.2 Update Permission constants and enum files
    - Add the 3 new permissions to `app/Constant/Permissions.php` and `app/Enum/Permission.php`
    - Update `PermissionBinding.php` if applicable
    - _Requirements: 9.1, 9.2, 9.3, 9.5_

- [ ] 3. Checkpoint - Ensure migrations run and permissions are seeded
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 4. Backend service layer
  - [ ] 4.1 Implement WorkflowService
    - Create `app/Services/WorkflowService.php` with methods: create, update, submit, approve, reject, disburse
    - Each method validates the current status before transitioning (throw validation exception on invalid transition)
    - Wrap all transitions in DB::transaction (status change + approval log + notification + pengeluaran creation on disburse)
    - On `submit`: validate mandatory fields, change status to `submitted`, create ApprovalLog, trigger auto-approval check, send notifications to approvers
    - On `approve`: change status to `approved`, create ApprovalLog with optional note, notify requester
    - On `reject`: require non-empty reason, change status to `rejected`, create ApprovalLog with reason, notify requester
    - On `disburse`: create Pengeluaran record (uraian, jumlah, tanggal=today, branch_id, pengeluaran_request_id), change status to `disbursed`, create ApprovalLog, notify requester
    - _Requirements: 1.4, 1.5, 1.6, 1.7, 2.3, 2.4, 2.5, 2.6, 3.1, 3.2, 4.1, 4.2, 4.3, 4.4, 5.3, 6.1_

  - [ ] 4.2 Implement AutoApprovalService
    - Create `app/Services/AutoApprovalService.php` with methods: shouldAutoApprove, processAutoApproval
    - `shouldAutoApprove`: check if branch has auto_approval_enabled=true AND threshold > 0 AND request jumlah < threshold
    - `processAutoApproval`: change status to `approved`, create ApprovalLog with note "auto-approved" and system user_id
    - _Requirements: 8.1, 8.2, 8.5_

  - [ ] 4.3 Implement NotificationService
    - Create `app/Services/NotificationService.php` with methods: notifyApprovers, notifyRequester
    - `notifyApprovers`: find all users with `approve-pengeluaran` permission in the same branch, create Notification records
    - `notifyRequester`: create Notification for the request's requester with appropriate title/message based on event type
    - Include rejection reason in notification message when status is `rejected`
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

  - [ ]* 4.4 Write property tests for WorkflowService state transitions
    - **Property 1: Creation Invariant** — verify created requests always have status `draft` and correct branch_id
    - **Property 4: Submit Transition** — verify submit changes status to `submitted` and creates ApprovalLog
    - **Property 6: Approve Transition with Metadata** — verify approve creates correct ApprovalLog entry
    - **Property 7: Reject Requires Reason** — verify reject fails without reason, succeeds with reason
    - **Property 13: Audit Log Completeness** — verify every transition creates exactly one ApprovalLog
    - **Validates: Requirements 1.1, 1.6, 2.3, 2.5, 2.6, 6.1, 10.1**

  - [ ]* 4.5 Write property tests for AutoApprovalService
    - **Property 14: Auto-Approval Logic** — verify auto-approval triggers correctly based on threshold and enabled flag
    - **Validates: Requirements 8.1, 8.2, 8.5**

- [ ] 5. Checkpoint - Ensure service layer tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Backend controllers dan request validation
  - [ ] 6.1 Create PengeluaranRequestController
    - Create `app/Http/Controllers/PengeluaranRequestController.php` with methods: index, show, store, update, destroy, submit, approve, reject, disburse
    - `index`: list requests filtered by authenticated user's branch_id, support status filter query param
    - `show`: return request detail with approval_logs relationship loaded, enforce branch isolation (403 if different branch)
    - `store`: call WorkflowService::create with validated data
    - `update`: validate status is draft/rejected and user is requester, call WorkflowService::update
    - `destroy`: validate status is draft, delete request
    - `submit`, `approve`, `reject`, `disburse`: delegate to WorkflowService methods
    - _Requirements: 1.1, 1.4, 1.5, 1.6, 2.1, 2.2, 2.3, 2.5, 3.1, 3.2, 4.1, 6.2, 10.2, 10.3_

  - [ ] 6.2 Create PengeluaranRequestRequest (Form Request validation)
    - Create `app/Http/Requests/PengeluaranRequestRequest.php` for store/update validation
    - Rules: uraian required string max:255, jumlah required numeric min:1, tanggal_kebutuhan required date, kategori_pengeluaran nullable string, lampiran nullable file max:2048 mimes:pdf,jpg,png
    - _Requirements: 1.2, 1.3, 1.7_

  - [ ] 6.3 Create NotificationController
    - Create `app/Http/Controllers/NotificationController.php` with methods: index, markAsRead, markAllAsRead, unreadCount
    - All endpoints scoped to authenticated user's notifications only
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

  - [ ] 6.4 Create BranchApprovalSettingController
    - Create `app/Http/Controllers/BranchApprovalSettingController.php` with methods: show, update
    - Scoped to authenticated user's branch
    - Validate: auto_approval_enabled boolean, auto_approval_threshold numeric min:0
    - _Requirements: 8.3, 8.4_

  - [ ] 6.5 Register API routes with permission middleware
    - Add routes in `routes/api.php` following existing pattern
    - Apply permission middleware: `create-pengeluaran-request` for create/update/delete/submit, `approve-pengeluaran` for approve/reject, `disburse-pengeluaran` for disburse
    - List endpoint accessible to any of the 3 permissions
    - Add notification routes (authenticated only)
    - Add branch-approval-settings routes (admin permission)
    - _Requirements: 9.1, 9.2, 9.3, 2.7, 4.5_

  - [ ]* 6.6 Write property tests for permission enforcement and branch isolation
    - **Property 5: Branch Data Isolation** — verify users can only see/access requests from their own branch
    - **Property 8: Permission Enforcement** — verify 403 responses for unauthorized actions
    - **Validates: Requirements 2.2, 2.7, 4.5, 9.1, 9.2, 9.3, 10.2, 10.3**

- [ ] 7. Checkpoint - Ensure all backend API tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 8. Backend integration: editability, resubmission, and disbursement correctness
  - [ ] 8.1 Implement editability validation logic in controller
    - Ensure update/delete only allowed when status is `draft` or `rejected` (for update) / `draft` (for delete)
    - Return 422 with clear error message for invalid status
    - _Requirements: 1.4, 1.5, 3.1_

  - [ ] 8.2 Implement file upload handling for lampiran
    - Store uploaded files in `storage/app/public/lampiran`
    - Validate file type (pdf, jpg, png) and size (max 2MB)
    - Return file URL in API response
    - _Requirements: 1.3_

  - [ ]* 8.3 Write property tests for editability and disbursement
    - **Property 3: Editability by Status** — verify edits allowed only in draft/rejected, rejected in other statuses
    - **Property 9: Resubmit Preserves History** — verify resubmit preserves all previous ApprovalLog entries
    - **Property 10: Disbursement Creates Correct Pengeluaran Record** — verify Pengeluaran record matches request data
    - **Property 12: Disbursement Date Used in Kas** — verify disbursement date (not tanggal_kebutuhan) is used
    - **Validates: Requirements 1.4, 3.1, 3.2, 3.3, 4.2, 4.3, 4.4, 5.3**

  - [ ]* 8.4 Write property test for kas report backward compatibility
    - **Property 11: Kas Reports Exclude Non-Disbursed Requests** — verify kas calculations only include pengeluarans table records
    - **Validates: Requirements 5.1, 5.2**

  - [ ]* 8.5 Write property test for notification targeting
    - **Property 2: Mandatory Field Validation** — verify missing mandatory fields are rejected
    - **Property 15: Notification Targeting on Submit** — verify notifications sent to correct users (same branch + approve permission)
    - **Validates: Requirements 1.2, 1.7, 7.1**

- [ ] 9. Checkpoint - Ensure all backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 10. Frontend: Request list and form pages
  - [ ] 10.1 Create PengeluaranRequest list page component
    - Create `frontend/src/pages/PengeluaranRequest.jsx` with tabs for each status (draft, submitted, approved, rejected, disbursed)
    - Display table with columns: uraian, jumlah (formatted currency), tanggal_kebutuhan, status badge, actions
    - Add create button, edit/delete actions for draft items, submit action
    - Fetch data from `GET /api/pengeluaran-request` with status filter
    - _Requirements: 1.1, 2.1, 10.2_

  - [ ] 10.2 Create PengeluaranRequestForm page component
    - Create `frontend/src/pages/PengeluaranRequestForm.jsx` for create and edit
    - Form fields: uraian (text input), jumlah (number input), tanggal_kebutuhan (date picker), kategori_pengeluaran (optional text/select), lampiran (file upload)
    - Validation: required fields marked, client-side validation before submit
    - Handle both create (POST) and update (PUT) modes
    - _Requirements: 1.2, 1.3, 1.4, 3.1_

  - [ ] 10.3 Create PengeluaranRequestDetail page component
    - Create `frontend/src/pages/PengeluaranRequestDetail.jsx` showing full request info
    - Display ApprovalTimeline component showing all approval log entries
    - Show action buttons based on current status and user permissions (submit, approve, reject, disburse)
    - Approve modal with optional note field, reject modal with required reason field
    - _Requirements: 2.3, 2.4, 2.5, 3.3, 6.2_

- [ ] 11. Frontend: Shared components and notifications
  - [ ] 11.1 Create StatusBadge and ApprovalTimeline components
    - `StatusBadge.jsx`: color-coded badge (draft=gray, submitted=blue, approved=green, rejected=red, disbursed=purple)
    - `ApprovalTimeline.jsx`: vertical timeline showing each log entry with user name, action, timestamp, and note/reason
    - _Requirements: 6.2_

  - [ ] 11.2 Create NotificationBell and NotificationDropdown components
    - `NotificationBell.jsx`: bell icon in header with unread count badge, fetches from `GET /api/notifications/unread-count`
    - `NotificationDropdown.jsx`: dropdown showing recent notifications, mark as read on click, "mark all as read" button
    - Integrate into existing app header/layout
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

  - [ ] 11.3 Create ApprovalSettings page component
    - Create `frontend/src/pages/ApprovalSettings.jsx` for branch auto-approval configuration
    - Toggle for enable/disable auto-approval, number input for threshold amount
    - Fetch from `GET /api/branch-approval-settings`, save with `PUT /api/branch-approval-settings`
    - _Requirements: 8.3, 8.4_

- [ ] 12. Frontend: Routing and navigation integration
  - [ ] 12.1 Register new routes and navigation menu items
    - Add routes for: /pengeluaran-request, /pengeluaran-request/create, /pengeluaran-request/:id, /pengeluaran-request/:id/edit, /approval-settings
    - Add navigation menu items with permission-based visibility
    - Integrate NotificationBell into app layout header
    - _Requirements: 9.1, 9.2, 9.3_

  - [ ]* 12.2 Write integration tests for full workflow cycle
    - Test complete flow: create → submit → approve → disburse → verify pengeluaran record created
    - Test rejection flow: create → submit → reject → edit → resubmit → approve → disburse
    - Test auto-approval flow: configure threshold → submit below threshold → verify auto-approved
    - _Requirements: 1.1, 1.6, 2.3, 2.5, 3.2, 4.2, 8.1_

- [ ] 13. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The implementation follows existing codebase patterns (Laravel + React, Spatie permissions, Sanctum auth)
- All API responses follow the existing error format: `{ "errors": { "field": ["message"] } }`
- File uploads stored in `storage/app/public/lampiran` following Laravel conventions

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "1.4"] },
    { "id": 1, "tasks": ["1.5", "1.6", "2.1", "2.2"] },
    { "id": 2, "tasks": ["4.1", "4.2", "4.3"] },
    { "id": 3, "tasks": ["4.4", "4.5"] },
    { "id": 4, "tasks": ["6.1", "6.2", "6.3", "6.4"] },
    { "id": 5, "tasks": ["6.5", "6.6"] },
    { "id": 6, "tasks": ["8.1", "8.2"] },
    { "id": 7, "tasks": ["8.3", "8.4", "8.5"] },
    { "id": 8, "tasks": ["10.1", "10.2", "10.3"] },
    { "id": 9, "tasks": ["11.1", "11.2", "11.3"] },
    { "id": 10, "tasks": ["12.1"] },
    { "id": 11, "tasks": ["12.2"] }
  ]
}
```
