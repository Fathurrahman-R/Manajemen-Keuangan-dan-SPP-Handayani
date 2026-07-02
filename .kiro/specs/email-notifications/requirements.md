# Requirements Document

## Introduction

Fitur ini menambahkan sistem notifikasi email untuk menginformasikan orang tua/wali siswa tentang tagihan, reminder jatuh tempo, kwitansi pembayaran, dan tagihan yang sudah melewati jatuh tempo. Sistem menggunakan Laravel Mail/Notification dengan queue untuk pengiriman asinkron. Setiap cabang (branch) memiliki pengaturan notifikasi sendiri, dan orang tua/wali dapat memilih untuk tidak menerima email (opt-out). Email dikirim dalam Bahasa Indonesia.

Fitur ini bergantung pada `auto-create-akun-siswa` (siswa/wali memiliki akun dengan email) dan `tagihan-card-view` (link ke tampilan tagihan dalam email).

## Glossary

- **Notification_System**: Modul yang mengelola pembuatan, pengiriman, penjadwalan, dan pelacakan notifikasi email
- **Email_Recipient**: Orang tua/wali siswa yang menerima notifikasi email, diidentifikasi melalui field email pada tabel Ayah, Ibu, atau Wali
- **Tagihan**: Record tagihan siswa yang memiliki kode_tagihan, jenis_tagihan_id, nis, tmp (total), status, dan branch_id
- **Pembayaran**: Record pembayaran yang terkait dengan Tagihan, memiliki kode_pembayaran, tanggal, metode, jumlah, dan pembayar
- **JenisTagihan**: Tipe tagihan yang memiliki nama, jatuh_tempo, jumlah, dan branch_id
- **Siswa**: Entitas murid yang memiliki relasi ke Ayah, Ibu, dan Wali
- **Branch**: Cabang sekolah yang memisahkan data dan pengaturan notifikasi
- **Notification_Setting**: Konfigurasi per branch yang menentukan jenis notifikasi yang aktif dan jadwal pengiriman
- **Notification_Log**: Record yang mencatat setiap pengiriman email beserta statusnya (sent, failed, skipped)
- **Admin_Operator**: Pengguna dengan role admin atau operator yang mengelola pengaturan notifikasi
- **Kwitansi_PDF**: Dokumen PDF bukti pembayaran yang dilampirkan pada email kwitansi
- **Reminder_Schedule**: Konfigurasi waktu pengiriman reminder sebelum jatuh tempo (7 hari, 3 hari, hari-H)
- **Opt_Out_Preference**: Preferensi Email_Recipient untuk tidak menerima jenis notifikasi tertentu

## Requirements

### Requirement 1: Penambahan Field Email pada Data Orang Tua/Wali

**User Story:** Sebagai Admin_Operator, saya ingin dapat menyimpan alamat email orang tua/wali siswa, sehingga sistem dapat mengirimkan notifikasi email terkait tagihan.

#### Acceptance Criteria

1. THE Notification_System SHALL add a nullable email field to the Ayah database table
2. THE Notification_System SHALL add a nullable email field to the Ibu database table
3. THE Notification_System SHALL add a nullable email field to the Wali database table
4. WHEN an Admin_Operator edits an Ayah, Ibu, or Wali record, THE Form SHALL display an email input field with email format validation
5. IF an Admin_Operator submits an email value that does not conform to RFC 5322 format, THEN THE Form SHALL reject the submission and display the message "Format email tidak valid"
6. THE Notification_System SHALL allow the email field to remain empty without blocking record creation or update

### Requirement 2: Notifikasi Tagihan Baru

**User Story:** Sebagai orang tua/wali, saya ingin menerima email ketika tagihan baru dibuat untuk anak saya, sehingga saya segera mengetahui kewajiban pembayaran.

#### Acceptance Criteria

1. WHEN a new Tagihan is created and the notification type "tagihan_baru" is enabled for the Branch, THE Notification_System SHALL dispatch an email notification to the Email_Recipient of the associated Siswa
2. THE Notification_System SHALL include the following information in the tagihan baru email: nama siswa, jenis tagihan, jumlah tagihan (formatted as Rupiah), tanggal jatuh tempo, and a link to the Tagihan_Card_View
3. THE Notification_System SHALL dispatch the email via Laravel queue for asynchronous processing
4. THE Notification_System SHALL resolve the Email_Recipient by checking the Siswa associated Wali email first, then Ibu email, then Ayah email, and use the first non-null email found
5. IF the Siswa has no associated Email_Recipient with a non-null email, THEN THE Notification_System SHALL skip the notification and log the event with reason "no_email_available" in the Notification_Log
6. WHEN multiple Tagihan are created in a single batch operation for the same Siswa, THE Notification_System SHALL send one consolidated email listing all new tagihan instead of individual emails per tagihan

### Requirement 3: Notifikasi Reminder Jatuh Tempo

**User Story:** Sebagai orang tua/wali, saya ingin menerima reminder sebelum tagihan jatuh tempo, sehingga saya dapat mempersiapkan pembayaran tepat waktu.

#### Acceptance Criteria

1. WHILE a Tagihan has status "Belum Lunas" and the notification type "reminder_jatuh_tempo" is enabled for the Branch, THE Notification_System SHALL send reminder emails according to the configured Reminder_Schedule
2. THE Notification_System SHALL support configurable reminder intervals: 7 days before jatuh tempo, 3 days before jatuh tempo, and on the jatuh tempo date
3. WHEN a scheduled reminder time is reached for a Tagihan, THE Notification_System SHALL send a reminder email to the Email_Recipient containing: nama siswa, jenis tagihan, jumlah tagihan, tanggal jatuh tempo, jumlah hari tersisa, and a link to the Tagihan_Card_View
4. IF a Tagihan status changes to "Lunas" before a scheduled reminder, THEN THE Notification_System SHALL cancel the pending reminder and not send the email
5. THE Notification_System SHALL execute reminder checks via a Laravel scheduled command that runs daily
6. THE Notification_System SHALL not send duplicate reminders for the same Tagihan and the same reminder interval

### Requirement 4: Notifikasi Kwitansi Pembayaran

**User Story:** Sebagai orang tua/wali, saya ingin menerima email kwitansi setelah melakukan pembayaran, sehingga saya memiliki bukti pembayaran digital.

#### Acceptance Criteria

1. WHEN a Pembayaran is recorded and the notification type "kwitansi_pembayaran" is enabled for the Branch, THE Notification_System SHALL dispatch an email notification to the Email_Recipient of the associated Siswa
2. THE Notification_System SHALL include the following information in the kwitansi email: nama siswa, kode pembayaran, tanggal pembayaran, metode pembayaran, jumlah dibayar (formatted as Rupiah), jenis tagihan, and sisa tagihan if partially paid
3. THE Notification_System SHALL attach a Kwitansi_PDF document to the kwitansi email
4. THE Notification_System SHALL generate the Kwitansi_PDF containing: header sekolah (nama branch), kode pembayaran, detail pembayaran, and tanda terima digital
5. THE Notification_System SHALL dispatch the kwitansi email via Laravel queue for asynchronous processing

### Requirement 5: Notifikasi Tagihan Overdue

**User Story:** Sebagai orang tua/wali, saya ingin menerima pengingat berkala untuk tagihan yang sudah melewati jatuh tempo, sehingga saya tidak lupa menyelesaikan kewajiban pembayaran.

#### Acceptance Criteria

1. WHILE a Tagihan has status "Belum Lunas" and the jatuh tempo date has passed and the notification type "tagihan_overdue" is enabled for the Branch, THE Notification_System SHALL send periodic overdue reminder emails to the Email_Recipient
2. THE Notification_System SHALL send overdue reminders at a configurable interval (default: every 7 days after jatuh tempo)
3. THE Notification_System SHALL include the following information in the overdue email: nama siswa, jenis tagihan, jumlah tagihan, tanggal jatuh tempo, jumlah hari keterlambatan, and a link to the Tagihan_Card_View
4. THE Notification_System SHALL execute overdue checks via the same daily scheduled command as the reminder checks
5. THE Notification_System SHALL not send duplicate overdue notifications for the same Tagihan within the configured interval period

### Requirement 6: Pengaturan Notifikasi per Branch

**User Story:** Sebagai Admin_Operator, saya ingin dapat mengatur jenis notifikasi yang aktif dan jadwalnya per cabang, sehingga setiap cabang dapat menyesuaikan komunikasi dengan kebutuhan masing-masing.

#### Acceptance Criteria

1. THE Notification_System SHALL provide a Notification_Setting configuration page accessible to Admin_Operator
2. THE Notification_Setting page SHALL allow Admin_Operator to enable or disable each notification type independently: tagihan_baru, reminder_jatuh_tempo, kwitansi_pembayaran, and tagihan_overdue
3. THE Notification_Setting page SHALL allow Admin_Operator to configure the Reminder_Schedule by selecting which intervals are active (7 days, 3 days, on due date)
4. THE Notification_Setting page SHALL allow Admin_Operator to configure the overdue reminder interval in days (minimum 1 day, default 7 days)
5. THE Notification_System SHALL store Notification_Setting per Branch, isolating configuration between branches
6. WHEN a Branch has no Notification_Setting configured, THE Notification_System SHALL treat all notification types as disabled for that Branch

### Requirement 7: Opt-Out Preferensi Orang Tua/Wali

**User Story:** Sebagai orang tua/wali, saya ingin dapat memilih untuk tidak menerima jenis email tertentu, sehingga saya hanya menerima notifikasi yang saya anggap penting.

#### Acceptance Criteria

1. THE Notification_System SHALL provide an opt-out mechanism accessible via a unique unsubscribe link in every notification email
2. WHEN an Email_Recipient clicks the unsubscribe link, THE Notification_System SHALL display a page showing all notification types with toggle options to enable or disable each type
3. THE Notification_System SHALL store Opt_Out_Preference per Email_Recipient per notification type
4. WHILE an Email_Recipient has opted out of a specific notification type, THE Notification_System SHALL skip sending that notification type to the Email_Recipient and log the event with reason "opted_out"
5. THE Notification_System SHALL include a re-subscribe link on the opt-out page allowing Email_Recipient to re-enable previously disabled notification types

### Requirement 8: Email Queue dan Delivery Tracking

**User Story:** Sebagai Admin_Operator, saya ingin sistem mengirim email secara asinkron dan dapat melacak status pengiriman, sehingga pengiriman email tidak mengganggu performa aplikasi dan saya dapat memantau keberhasilan pengiriman.

#### Acceptance Criteria

1. THE Notification_System SHALL dispatch all notification emails via Laravel queue using a dedicated "notifications" queue name
2. THE Notification_System SHALL record every email dispatch attempt in the Notification_Log with fields: recipient_email, notification_type, tagihan_kode, status (queued, sent, failed, skipped), sent_at, and error_message
3. IF an email dispatch fails, THEN THE Notification_System SHALL retry the dispatch up to 3 times with exponential backoff (1 minute, 5 minutes, 15 minutes)
4. IF all retry attempts fail, THEN THE Notification_System SHALL mark the Notification_Log entry status as "failed" and store the error message
5. THE Notification_System SHALL apply rate limiting of a maximum 50 emails per minute per Branch to prevent mail server overload
6. THE Notification_System SHALL provide an Admin_Operator accessible page to view Notification_Log entries filtered by status, notification_type, and date range

### Requirement 9: Graceful Handling dan Error Recovery

**User Story:** Sebagai Admin_Operator, saya ingin sistem menangani error pengiriman email secara graceful tanpa mengganggu operasi utama, sehingga kegagalan email tidak mempengaruhi proses pembuatan tagihan atau pembayaran.

#### Acceptance Criteria

1. IF an error occurs during email notification dispatch, THEN THE Notification_System SHALL catch the exception, log the error in the Notification_Log, and allow the triggering operation (tagihan creation or pembayaran recording) to complete successfully
2. IF the email queue worker is not running, THEN THE Notification_System SHALL still queue the notification jobs and process them when the worker resumes
3. THE Notification_System SHALL validate the recipient email format before attempting dispatch and skip invalid emails with reason "invalid_email" in the Notification_Log
4. WHEN an Admin_Operator views the Notification_Log, THE Notification_System SHALL allow manual retry of failed notifications by selecting one or more failed entries and triggering re-dispatch
