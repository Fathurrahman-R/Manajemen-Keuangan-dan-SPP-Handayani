# Implementation Plan: Email Notifications

## Overview

Implementasi sistem notifikasi email untuk aplikasi SPP Handayani menggunakan Laravel 12 Notification/Mail framework. Sistem mengirim notifikasi tagihan baru, reminder jatuh tempo, kwitansi pembayaran, dan tagihan overdue kepada orang tua/wali siswa secara asinkron via queue, dengan konfigurasi per-branch dan opt-out preferences.

## Tasks

- [x] 1. Database migrations dan model setup
  - [x] 1.1 Create migration to add email field to parent tables (ayah, ibu, walis)
    - Add nullable `email` column (string 255) to `ayah`, `ibu`, and `walis` tables
    - _Requirements: 1.1, 1.2, 1.3_

  - [x] 1.2 Create migration for notification_settings table
    - Create table with branch_id (unique FK), boolean flags for each notification type, reminder_days_before (json), overdue_interval_days (integer)
    - _Requirements: 6.5_

  - [x] 1.3 Create migration for notification_logs table
    - Create table with branch_id, recipient_email, notification_type (enum), tagihan_kode, status (enum), reason, error_message, sent_at
    - _Requirements: 8.2_

  - [x] 1.4 Create migration for email_opt_outs table
    - Create table with email, notification_type (enum), token (unique), and unique constraint on (email, notification_type)
    - _Requirements: 7.3_

  - [x] 1.5 Create migration for notification_sent_records table
    - Create table with tagihan_kode, notification_type (enum), sent_date, and unique constraint on (tagihan_kode, notification_type, sent_date)
    - _Requirements: 3.6, 5.5_

  - [x] 1.6 Create Eloquent models: NotificationSetting, NotificationLog, EmailOptOut, NotificationSentRecord
    - Define fillable, casts, relationships as specified in design
    - Add `isOptedOut` static method to EmailOptOut
    - Add `alreadySent` static method to NotificationSentRecord
    - _Requirements: 6.5, 7.3, 8.2, 3.6_

- [x] 2. Recipient resolution dan email validation
  - [x] 2.1 Implement RecipientResolver service
    - Create `App\Services\Notifications\RecipientResolver` class
    - Implement `resolve(Siswa $siswa): ?string` with priority Wali → Ibu → Ayah
    - Return first non-null email or null
    - _Requirements: 2.4, 2.5_

  - [x]* 2.2 Write property test for RecipientResolver (Property 4: Recipient Resolution Priority)
    - **Property 4: Recipient Resolution Priority**
    - **Validates: Requirements 2.4, 2.5**
    - Test with 100+ random combinations of Wali/Ibu/Ayah email values (including nulls)
    - Verify correct priority order is always followed

  - [x] 2.3 Implement email validation helper
    - Create validation logic using Laravel's `filter_var` with `FILTER_VALIDATE_EMAIL`
    - Implement `formatRupiah` helper function
    - _Requirements: 1.5, 9.3_

  - [x]* 2.4 Write property test for email validation (Property 1: Email Validation Consistency)
    - **Property 1: Email Validation Consistency**
    - **Validates: Requirements 1.5, 9.3**
    - Test with 100+ random strings (valid emails, invalid formats, empty, whitespace)

- [x] 3. Update existing forms and requests for email field
  - [x] 3.1 Update WaliRequest, SiswaKBRequest, SiswaTKRequest, SiswaMIRequest form requests
    - Add `email` validation rule: `nullable|email:rfc`
    - Add custom error message "Format email tidak valid"
    - _Requirements: 1.4, 1.5, 1.6_

  - [x] 3.2 Update WaliController, SiswaController to handle email field in store/update
    - Ensure email field is saved on Ayah, Ibu, and Wali records
    - _Requirements: 1.4, 1.6_

- [x] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Notification settings (branch configuration)
  - [x] 5.1 Create NotificationSettingController with show and update methods
    - GET `/api/notification-settings` returns current branch settings
    - PUT `/api/notification-settings` updates branch settings
    - Apply permission middleware for Admin_Operator access
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [x] 5.2 Create NotificationSettingRequest form request
    - Validate boolean fields, reminder_days_before as array of valid values, overdue_interval_days min:1
    - _Requirements: 6.4_

  - [x] 5.3 Register API routes for notification settings
    - Add routes in `routes/api.php` with auth and permission middleware
    - _Requirements: 6.1_

  - [x]* 5.4 Write property test for branch setting isolation (Property 9: Branch Setting Isolation)
    - **Property 9: Branch Setting Isolation**
    - **Validates: Requirements 6.5**
    - Test with multiple branches, verify modifying one does not affect others

- [x] 6. Core NotificationService implementation
  - [x] 6.1 Create NotificationService class with constructor injection
    - Implement `isEnabled(branchId, type)` checking NotificationSetting
    - Implement `isOptedOut(email, type)` checking EmailOptOut
    - Implement `validateEmail(email)` for format validation
    - Implement `logNotification(data)` for writing NotificationLog entries
    - Implement `checkRateLimit(branchId)` using Laravel RateLimiter
    - _Requirements: 6.6, 7.4, 8.2, 8.5, 9.3_

  - [x] 6.2 Implement `sendTagihanBaru(Collection $tagihans, Siswa $siswa)` method
    - Check branch setting enabled, resolve recipient, check opt-out, validate email
    - Dispatch TagihanBaruNotification with consolidated tagihan list
    - Log notification with appropriate status
    - _Requirements: 2.1, 2.4, 2.5, 2.6_

  - [x] 6.3 Implement `sendKwitansiPembayaran(Pembayaran $pembayaran)` method
    - Check branch setting enabled, resolve recipient, check opt-out, validate email
    - Dispatch KwitansiPembayaranNotification
    - Log notification with appropriate status
    - _Requirements: 4.1, 4.5_

  - [x] 6.4 Implement `processReminders()` method
    - Query all "Belum Lunas" tagihan with upcoming jatuh_tempo
    - Check branch settings, reminder schedule, and sent records for deduplication
    - Dispatch ReminderJatuhTempoNotification for eligible tagihan
    - _Requirements: 3.1, 3.2, 3.4, 3.5, 3.6_

  - [x] 6.5 Implement `processOverdue()` method
    - Query all "Belum Lunas" tagihan past jatuh_tempo
    - Check branch settings, overdue interval, and sent records for deduplication
    - Dispatch TagihanOverdueNotification for eligible tagihan
    - _Requirements: 5.1, 5.2, 5.4, 5.5_

  - [x] 6.6 Implement `retryFailed(array $logIds)` method
    - Re-dispatch failed notifications from NotificationLog entries
    - _Requirements: 9.4_

  - [x]* 6.7 Write property test for conditional dispatch (Property 2: Conditional Dispatch Based on Branch Settings)
    - **Property 2: Conditional Dispatch Based on Branch Settings**
    - **Validates: Requirements 2.1, 4.1, 6.6**
    - Test with random branch settings, verify dispatch only when enabled

  - [x]* 6.8 Write property test for opt-out filtering (Property 10: Opt-Out Filtering)
    - **Property 10: Opt-Out Filtering**
    - **Validates: Requirements 7.4**
    - Test with random opt-out preferences, verify correct skip/send decisions

  - [x]* 6.9 Write property test for notification logging (Property 11: Notification Logging Completeness)
    - **Property 11: Notification Logging Completeness**
    - **Validates: Requirements 8.2**
    - Test with random dispatch events, verify all required fields are logged

- [x] 7. Notification classes (Mailable)
  - [x] 7.1 Create TagihanBaruNotification class
    - Implement `ShouldQueue` with queue "notifications", tries 3, exponential backoff
    - Build email with: nama siswa, jenis tagihan, jumlah (Rupiah), jatuh tempo, link tagihan card view
    - Support consolidated list for batch tagihan
    - Include unsubscribe link
    - _Requirements: 2.2, 2.3, 2.6, 7.1, 8.1, 8.3_

  - [x] 7.2 Create ReminderJatuhTempoNotification class
    - Implement `ShouldQueue` with queue "notifications", tries 3, exponential backoff
    - Build email with: nama siswa, jenis tagihan, jumlah, jatuh tempo, hari tersisa, link
    - Include unsubscribe link
    - _Requirements: 3.3, 7.1, 8.1, 8.3_

  - [x] 7.3 Create KwitansiPembayaranNotification class
    - Implement `ShouldQueue` with queue "notifications", tries 3, exponential backoff
    - Build email with: nama siswa, kode pembayaran, tanggal, metode, jumlah, jenis tagihan, sisa tagihan
    - Attach Kwitansi PDF
    - Include unsubscribe link
    - _Requirements: 4.2, 4.3, 7.1, 8.1, 8.3_

  - [x] 7.4 Create TagihanOverdueNotification class
    - Implement `ShouldQueue` with queue "notifications", tries 3, exponential backoff
    - Build email with: nama siswa, jenis tagihan, jumlah, jatuh tempo, hari keterlambatan, link
    - Include unsubscribe link
    - _Requirements: 5.3, 7.1, 8.1, 8.3_

  - [x] 7.5 Create email Blade templates for all notification types
    - Create `resources/views/emails/tagihan-baru.blade.php`
    - Create `resources/views/emails/reminder-jatuh-tempo.blade.php`
    - Create `resources/views/emails/kwitansi-pembayaran.blade.php`
    - Create `resources/views/emails/tagihan-overdue.blade.php`
    - All templates in Bahasa Indonesia with unsubscribe link footer
    - _Requirements: 2.2, 3.3, 4.2, 5.3, 7.1_

  - [x]* 7.6 Write property test for email content completeness (Property 3: Email Content Completeness)
    - **Property 3: Email Content Completeness**
    - **Validates: Requirements 2.2, 3.3, 4.2, 5.3, 7.1**
    - Test with random tagihan/pembayaran data, verify all required fields present

  - [x]* 7.7 Write property test for batch consolidation (Property 5: Batch Consolidation)
    - **Property 5: Batch Consolidation**
    - **Validates: Requirements 2.6**
    - Test with random batch sizes, verify exactly one email per siswa

- [x] 8. Kwitansi PDF generation
  - [x] 8.1 Create KwitansiPdfService for generating payment receipt PDF
    - Generate PDF with: header sekolah (nama branch), kode pembayaran, detail pembayaran, tanda terima digital
    - Use existing PdfGeneratorController pattern or DomPDF/Snappy
    - _Requirements: 4.3, 4.4_

  - [x]* 8.2 Write property test for Kwitansi PDF content (Property 14: Kwitansi PDF Content)
    - **Property 14: Kwitansi PDF Content**
    - **Validates: Requirements 4.4**
    - Test with random pembayaran data, verify all required sections in PDF

- [x] 9. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Events, listeners, dan controller integration
  - [x] 10.1 Create TagihanCreated and PembayaranRecorded events
    - Define event classes with required properties (tagihans/siswa/branchId, pembayaran/branchId)
    - _Requirements: 2.1, 4.1_

  - [x] 10.2 Create SendTagihanBaruNotification and SendKwitansiNotification listeners
    - Implement handle() with try-catch for error isolation
    - Call NotificationService methods
    - Log errors without re-throwing to allow main operation to complete
    - _Requirements: 2.1, 4.1, 9.1_

  - [x] 10.3 Register events and listeners in EventServiceProvider
    - Map TagihanCreated → SendTagihanBaruNotification
    - Map PembayaranRecorded → SendKwitansiNotification
    - _Requirements: 2.1, 4.1_

  - [x] 10.4 Dispatch events from TagihanController and PembayaranController
    - Fire TagihanCreated after tagihan creation (support batch grouping by siswa)
    - Fire PembayaranRecorded after pembayaran recording
    - Wrap in try-catch for error isolation
    - _Requirements: 2.1, 2.6, 4.1, 9.1_

  - [x]* 10.5 Write property test for error isolation (Property 13: Error Isolation)
    - **Property 13: Error Isolation**
    - **Validates: Requirements 9.1**
    - Simulate notification failures, verify main operation completes successfully

- [x] 11. Scheduled command for reminders and overdue
  - [x] 11.1 Create SendNotificationReminders artisan command
    - Implement `notifications:send-reminders` command
    - Call `NotificationService::processReminders()` and `processOverdue()`
    - _Requirements: 3.5, 5.4_

  - [x] 11.2 Register scheduled command in routes/console.php
    - Schedule `notifications:send-reminders` to run daily at 08:00
    - _Requirements: 3.5, 5.4_

  - [x]* 11.3 Write property test for reminder scheduling (Property 6: Reminder Scheduling Eligibility)
    - **Property 6: Reminder Scheduling Eligibility**
    - **Validates: Requirements 3.1, 3.2, 3.4**
    - Test with random dates/statuses, verify correct reminder decisions

  - [x]* 11.4 Write property test for notification idempotency (Property 7: Notification Idempotency)
    - **Property 7: Notification Idempotency**
    - **Validates: Requirements 3.6, 5.5**
    - Run check command twice, verify no duplicate notifications

  - [x]* 11.5 Write property test for overdue detection (Property 8: Overdue Detection and Interval)
    - **Property 8: Overdue Detection and Interval**
    - **Validates: Requirements 5.1, 5.2**
    - Test with random overdue dates, verify correct interval calculation

- [x] 12. Rate limiting dan queue configuration
  - [x] 12.1 Configure rate limiter for email notifications
    - Register `email-notifications` rate limiter in AppServiceProvider (50/min per branch)
    - Apply rate limiting in NotificationService before dispatch
    - _Requirements: 8.5_

  - [x] 12.2 Configure queue retry and failed job handling
    - Ensure `failed()` method on each Notification class updates NotificationLog status to "failed"
    - Store error_message on failure
    - _Requirements: 8.3, 8.4_

  - [x]* 12.3 Write property test for rate limiting (Property 12: Rate Limiting)
    - **Property 12: Rate Limiting**
    - **Validates: Requirements 8.5**
    - Test with large batches, verify max 50/min per branch enforced

- [x] 13. Opt-out (unsubscribe) system
  - [x] 13.1 Create EmailOptOutController with show and update methods
    - GET `/unsubscribe/{token}` displays opt-out page (signed URL, no auth required)
    - POST `/unsubscribe/{token}` toggles notification preferences
    - _Requirements: 7.1, 7.2, 7.5_

  - [x] 13.2 Create unsubscribe Blade view
    - Display all notification types with toggle options
    - Show re-subscribe option for previously disabled types
    - _Requirements: 7.2, 7.5_

  - [x] 13.3 Implement signed URL generation for unsubscribe links
    - Generate unique token per email, use Laravel signed URLs
    - Include unsubscribe URL in all notification emails
    - _Requirements: 7.1_

  - [x] 13.4 Register unsubscribe routes (public, no auth)
    - Add routes in `routes/web.php` with signed URL middleware
    - _Requirements: 7.1_

- [x] 14. Notification log viewer and manual retry
  - [x] 14.1 Create NotificationLogController with index and retry methods
    - GET `/api/notification-logs` with filters: status, notification_type, date range, pagination
    - POST `/api/notification-logs/retry` accepts array of log IDs for re-dispatch
    - Apply permission middleware for Admin_Operator access
    - _Requirements: 8.6, 9.4_

  - [x] 14.2 Register API routes for notification logs
    - Add routes in `routes/api.php` with auth and permission middleware
    - _Requirements: 8.6_

- [x] 15. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties using PHPUnit data providers with Faker (100+ iterations)
- Unit tests validate specific examples and edge cases
- All emails are in Bahasa Indonesia
- The system uses Laravel's anonymous notifiable (`Notification::route('mail', ...)`) since recipients are not User models
- Rate limiting uses Laravel's built-in RateLimiter facade
- Queue driver is database (already configured in project)

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "1.4", "1.5"] },
    { "id": 1, "tasks": ["1.6", "2.1", "2.3"] },
    { "id": 2, "tasks": ["2.2", "2.4", "3.1", "3.2"] },
    { "id": 3, "tasks": ["5.1", "5.2", "5.3"] },
    { "id": 4, "tasks": ["5.4", "6.1"] },
    { "id": 5, "tasks": ["6.2", "6.3", "6.4", "6.5", "6.6"] },
    { "id": 6, "tasks": ["6.7", "6.8", "6.9", "7.1", "7.2", "7.3", "7.4", "7.5"] },
    { "id": 7, "tasks": ["7.6", "7.7", "8.1"] },
    { "id": 8, "tasks": ["8.2", "10.1", "10.2"] },
    { "id": 9, "tasks": ["10.3", "10.4"] },
    { "id": 10, "tasks": ["10.5", "11.1", "11.2"] },
    { "id": 11, "tasks": ["11.3", "11.4", "11.5", "12.1", "12.2"] },
    { "id": 12, "tasks": ["12.3", "13.1", "13.2", "13.3", "13.4"] },
    { "id": 13, "tasks": ["14.1", "14.2"] }
  ]
}
```
