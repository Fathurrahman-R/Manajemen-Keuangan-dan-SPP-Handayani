# Requirements Document

## Introduction

Fitur ini menambahkan workflow approval untuk pengeluaran kas sekolah. Saat ini, operator dapat langsung membuat record pengeluaran tanpa proses persetujuan. Dengan fitur ini, pengeluaran harus melalui tahapan: pembuatan request (draft) → pengajuan (submitted) → persetujuan/penolakan (approved/rejected) → pencairan (disbursed). Workflow ini memastikan kontrol keuangan yang lebih baik dengan tetap menjaga kompatibilitas dengan laporan kas harian dan rekap bulanan yang sudah ada.

## Glossary

- **Pengeluaran_Request**: Entitas baru yang merepresentasikan pengajuan pengeluaran kas, terpisah dari tabel pengeluaran yang sudah ada
- **Requester**: User dengan permission `create-pengeluaran-request` yang membuat dan mengajukan pengajuan pengeluaran
- **Approver**: User dengan permission `approve-pengeluaran` yang mereview dan menyetujui atau menolak pengajuan
- **Disburser**: User dengan permission `disburse-pengeluaran` yang mencairkan pengeluaran yang sudah disetujui
- **Workflow_Status**: Status pengajuan pengeluaran: `draft`, `submitted`, `approved`, `rejected`, `disbursed`
- **Approval_Log**: Catatan audit trail setiap perubahan status pada pengajuan pengeluaran
- **Auto_Approval_Threshold**: Batas nominal yang jika pengajuan di bawah nilai tersebut, akan otomatis disetujui tanpa review manual
- **Branch**: Cabang sekolah, setiap pengajuan terisolasi per branch
- **Notification_System**: Sistem notifikasi in-app yang memberitahu user terkait perubahan status pengajuan

## Requirements

### Requirement 1: Pembuatan Pengeluaran Request

**User Story:** As a Requester, I want to create a pengeluaran request with detailed information, so that I can formally request fund disbursement through the approval process.

#### Acceptance Criteria

1. WHEN a Requester creates a new Pengeluaran_Request, THE System SHALL save the request with status `draft` and associate it with the Requester's Branch
2. THE Pengeluaran_Request SHALL contain the following mandatory fields: uraian (description), jumlah (amount), and tanggal_kebutuhan (date needed)
3. THE Pengeluaran_Request SHALL support the following optional fields: kategori_pengeluaran (expense category) and lampiran (file attachment)
4. WHILE a Pengeluaran_Request has status `draft`, THE System SHALL allow the Requester to edit all fields of the request
5. WHILE a Pengeluaran_Request has status `draft`, THE System SHALL allow the Requester to delete the request
6. WHEN a Requester submits a Pengeluaran_Request, THE System SHALL validate that all mandatory fields are filled and change the status from `draft` to `submitted`
7. IF a Requester attempts to submit a Pengeluaran_Request with missing mandatory fields, THEN THE System SHALL reject the submission and return specific validation error messages

### Requirement 2: Approval Process

**User Story:** As an Approver, I want to review and approve or reject pengeluaran requests, so that I can control fund disbursement with proper oversight.

#### Acceptance Criteria

1. WHEN a Pengeluaran_Request status changes to `submitted`, THE System SHALL make the request visible in the Approver's pending approval list
2. THE System SHALL display pending Pengeluaran_Requests filtered by the Approver's Branch
3. WHEN an Approver approves a Pengeluaran_Request, THE System SHALL change the status to `approved` and record the Approver's identity and timestamp
4. WHEN an Approver approves a Pengeluaran_Request, THE System SHALL accept an optional approval note
5. WHEN an Approver rejects a Pengeluaran_Request, THE System SHALL require a rejection reason and change the status to `rejected`
6. WHEN an Approver rejects a Pengeluaran_Request, THE System SHALL record the Approver's identity, timestamp, and rejection reason
7. IF a user without `approve-pengeluaran` permission attempts to approve or reject a request, THEN THE System SHALL deny the action and return a 403 Forbidden response

### Requirement 3: Rejection and Resubmission

**User Story:** As a Requester, I want to revise and resubmit a rejected pengeluaran request, so that I can address the Approver's feedback without creating a new request.

#### Acceptance Criteria

1. WHILE a Pengeluaran_Request has status `rejected`, THE System SHALL allow the Requester to edit all fields of the request
2. WHEN a Requester resubmits a previously rejected Pengeluaran_Request, THE System SHALL change the status from `rejected` to `submitted`
3. THE System SHALL preserve the full history of previous rejection reasons and approval attempts on the Pengeluaran_Request

### Requirement 4: Disbursement

**User Story:** As a Disburser, I want to record the actual disbursement of approved pengeluaran requests, so that the expense is formally recorded in the kas system.

#### Acceptance Criteria

1. WHILE a Pengeluaran_Request has status `approved`, THE System SHALL allow a Disburser to record the disbursement
2. WHEN a Disburser records a disbursement, THE System SHALL create a corresponding Pengeluaran record in the existing pengeluarans table with the uraian, jumlah, tanggal (disbursement date), and branch_id from the request
3. WHEN a Disburser records a disbursement, THE System SHALL change the Pengeluaran_Request status to `disbursed` and record the Disburser's identity and timestamp
4. THE System SHALL store a reference (foreign key) from the created Pengeluaran record back to the originating Pengeluaran_Request
5. IF a user without `disburse-pengeluaran` permission attempts to disburse a request, THEN THE System SHALL deny the action and return a 403 Forbidden response

### Requirement 5: Backward Compatibility with Kas Reporting

**User Story:** As an operator, I want the existing kas harian and rekap bulanan reports to continue working correctly, so that financial reporting is not disrupted by the new workflow.

#### Acceptance Criteria

1. THE System SHALL only include Pengeluaran records (from the pengeluarans table) in kas harian and rekap bulanan calculations
2. THE System SHALL not include Pengeluaran_Request records that have not been disbursed in any kas report
3. WHEN a Pengeluaran_Request is disbursed, THE System SHALL use the disbursement date as the tanggal in the Pengeluaran record for kas reporting purposes

### Requirement 6: Audit Trail

**User Story:** As an administrator, I want to see the complete history of status changes on each pengeluaran request, so that I can audit the approval process.

#### Acceptance Criteria

1. WHEN a Pengeluaran_Request status changes, THE Approval_Log SHALL record the previous status, new status, user who performed the action, timestamp, and optional note or reason
2. THE System SHALL display the complete Approval_Log history when viewing a Pengeluaran_Request detail
3. THE Approval_Log SHALL be immutable once created (append-only, no edits or deletions)

### Requirement 7: In-App Notifications

**User Story:** As a user involved in the approval workflow, I want to receive in-app notifications about status changes, so that I can act on requests promptly.

#### Acceptance Criteria

1. WHEN a Pengeluaran_Request status changes to `submitted`, THE Notification_System SHALL notify all users with `approve-pengeluaran` permission in the same Branch
2. WHEN a Pengeluaran_Request status changes to `approved`, THE Notification_System SHALL notify the Requester who created the request
3. WHEN a Pengeluaran_Request status changes to `rejected`, THE Notification_System SHALL notify the Requester who created the request and include the rejection reason
4. WHEN a Pengeluaran_Request status changes to `disbursed`, THE Notification_System SHALL notify the Requester who created the request

### Requirement 8: Auto-Approval Threshold

**User Story:** As an administrator, I want to configure an auto-approval threshold per branch, so that small expenses can be processed quickly without manual approval.

#### Acceptance Criteria

1. WHERE the auto-approval feature is enabled for a Branch, THE System SHALL automatically approve Pengeluaran_Requests with jumlah below the configured threshold
2. WHEN a Pengeluaran_Request is auto-approved, THE System SHALL change the status directly from `submitted` to `approved` and record "auto-approved" in the Approval_Log
3. THE System SHALL allow administrators to configure the auto-approval threshold amount per Branch
4. THE System SHALL allow administrators to enable or disable the auto-approval feature per Branch
5. IF the auto-approval threshold is set to zero or the feature is disabled, THEN THE System SHALL require manual approval for all Pengeluaran_Requests in that Branch

### Requirement 9: Permission-Based Access Control

**User Story:** As an administrator, I want the approval workflow to be controlled by specific permissions, so that only authorized users can perform each action in the workflow.

#### Acceptance Criteria

1. THE System SHALL enforce `create-pengeluaran-request` permission for creating and submitting Pengeluaran_Requests
2. THE System SHALL enforce `approve-pengeluaran` permission for approving and rejecting Pengeluaran_Requests
3. THE System SHALL enforce `disburse-pengeluaran` permission for recording disbursements
4. THE System SHALL register the new permissions (`create-pengeluaran-request`, `approve-pengeluaran`, `disburse-pengeluaran`) via a database seeder or migration
5. THE System SHALL allow a single user to hold multiple workflow permissions (a user can be both Requester and Approver if assigned both permissions)

### Requirement 10: Multi-Branch Data Isolation

**User Story:** As a branch administrator, I want pengeluaran requests to be isolated per branch, so that each branch manages its own approval workflow independently.

#### Acceptance Criteria

1. THE System SHALL associate every Pengeluaran_Request with the Requester's branch_id at creation time
2. THE System SHALL only display Pengeluaran_Requests belonging to the authenticated user's Branch in all list views
3. IF a user attempts to access a Pengeluaran_Request from a different Branch, THEN THE System SHALL deny access and return a 403 Forbidden response
4. THE System SHALL scope auto-approval threshold configuration independently per Branch
