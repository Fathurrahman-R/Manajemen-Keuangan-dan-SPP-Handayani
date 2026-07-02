# Requirements Document

## Introduction

Fitur ini memigrasikan identifier login untuk user admin/operator dari username ke email. Saat ini semua user (admin, operator, siswa) login menggunakan username. Setelah migrasi, admin dan operator akan login menggunakan email, sementara akun siswa tetap menggunakan NIS sebagai username. Form login akan menerima kedua format (email untuk admin/operator, NIS untuk siswa) dan melakukan routing autentikasi secara otomatis berdasarkan format input. Fitur ini juga mencakup alur forgot password via email untuk admin/operator, serta tool untuk memastikan semua akun admin/operator memiliki email sebelum migrasi diaktifkan.

## Glossary

- **Sistem_Auth**: Modul autentikasi yang mengelola login, logout, dan validasi kredensial user
- **Admin_Operator**: User dengan role "admin" atau "operator" yang mengelola sistem billing sekolah
- **Akun_Siswa**: User dengan role "siswa" yang login menggunakan NIS (dari spec auto-create-akun-siswa)
- **Login_Form**: Halaman login pada frontend-v2 (Filament) yang menerima input kredensial
- **Backend_Auth**: Endpoint autentikasi pada backend API yang memvalidasi kredensial dan mengeluarkan token Sanctum
- **Email_Identifier**: Alamat email yang digunakan sebagai identifier login untuk Admin_Operator
- **NIS**: Nomor Induk Siswa yang digunakan sebagai username login untuk Akun_Siswa
- **Branch**: Cabang sekolah; email harus unik dalam satu Branch
- **Email_Population_Tool**: Halaman admin untuk mengisi email pada akun Admin_Operator yang belum memiliki email
- **Password_Reset**: Alur reset password melalui pengiriman link ke email

## Requirements

### Requirement 1: Email Wajib untuk Akun Admin/Operator Baru

**User Story:** Sebagai pengelola sistem, saya ingin email menjadi field wajib saat membuat akun admin/operator baru, sehingga semua akun baru sudah siap untuk login berbasis email.

#### Acceptance Criteria

1. WHEN an Admin_Operator creates a new User account with role "admin" or "operator", THE Sistem_Auth SHALL require the email field to be filled with a valid email address
2. WHEN an Admin_Operator submits a new User account with role "admin" or "operator", THE Sistem_Auth SHALL validate that the email is unique within the same Branch
3. WHEN an Admin_Operator submits a new User account with an email that already exists in the same Branch, THE Sistem_Auth SHALL reject the submission and display the message "Email sudah digunakan di cabang ini"
4. WHEN an Admin_Operator creates a new User account with role "siswa", THE Sistem_Auth SHALL not require the email field

### Requirement 2: Tool Populasi Email untuk Akun Existing

**User Story:** Sebagai Admin_Operator, saya ingin memiliki halaman khusus untuk mengisi email pada akun admin/operator yang belum memiliki email, sehingga semua akun siap untuk migrasi login.

#### Acceptance Criteria

1. WHEN an Admin_Operator opens the Email_Population_Tool page, THE Sistem_Auth SHALL display a list of all Admin_Operator accounts in the active Branch that have a null or empty email field
2. WHEN an Admin_Operator enters an email for a listed account and submits, THE Sistem_Auth SHALL validate the email format and uniqueness within the Branch before saving
3. WHEN an Admin_Operator submits an invalid or duplicate email, THE Sistem_Auth SHALL display an inline error message on the corresponding row without clearing other entered values
4. THE Email_Population_Tool SHALL display a progress indicator showing the count of accounts with email populated versus total Admin_Operator accounts in the Branch
5. WHEN all Admin_Operator accounts in the Branch have a valid email populated, THE Email_Population_Tool SHALL display a confirmation message "Semua akun admin/operator sudah memiliki email. Migrasi login siap diaktifkan."

### Requirement 3: Login Menggunakan Email atau NIS

**User Story:** Sebagai user, saya ingin form login menerima email (untuk admin/operator) maupun NIS (untuk siswa), sehingga semua tipe user dapat login dari satu form yang sama.

#### Acceptance Criteria

1. THE Login_Form SHALL display the input label as "Email / NIS" instead of "Username"
2. WHEN a user submits the Login_Form with an input containing the "@" character, THE Sistem_Auth SHALL authenticate by matching the input against the email field in the User table
3. WHEN a user submits the Login_Form with an input that does not contain the "@" character, THE Sistem_Auth SHALL authenticate by matching the input against the username field in the User table
4. WHEN the Backend_Auth receives a login request with an input containing "@", THE Backend_Auth SHALL query the User table by email field and validate the password
5. WHEN the Backend_Auth receives a login request with an input that does not contain "@", THE Backend_Auth SHALL query the User table by username field and validate the password
6. IF the provided credentials do not match any User record, THEN THE Sistem_Auth SHALL return the error message "Email/NIS atau password salah"
7. THE Backend_Auth SHALL scope the email lookup to active users only (is_active equals true)
8. THE Backend_Auth SHALL scope the username lookup to active users only (is_active equals true)

### Requirement 4: Password Reset via Email untuk Admin/Operator

**User Story:** Sebagai Admin_Operator, saya ingin dapat mereset password saya sendiri melalui email, sehingga saya tidak perlu menghubungi admin lain jika lupa password.

#### Acceptance Criteria

1. THE Login_Form SHALL display a "Lupa Password?" link that navigates to the password reset request page
2. WHEN an Admin_Operator submits their email on the password reset request page, THE Sistem_Auth SHALL send a password reset link to the submitted email address
3. WHEN the submitted email does not exist in the User table, THE Sistem_Auth SHALL display the same success message as a valid submission to prevent email enumeration
4. WHEN an Admin_Operator clicks the password reset link within the valid period, THE Sistem_Auth SHALL display a form to enter a new password
5. WHEN an Admin_Operator submits a new password via the reset form, THE Sistem_Auth SHALL update the User password and invalidate the reset link
6. THE Sistem_Auth SHALL set the password reset link expiration to 60 minutes after creation
7. IF an Admin_Operator clicks an expired or already-used password reset link, THEN THE Sistem_Auth SHALL display the message "Link reset password sudah kadaluarsa atau sudah digunakan"
8. THE Sistem_Auth SHALL not provide self-service password reset for Akun_Siswa (siswa role)

### Requirement 5: Update Email dari Halaman Profil

**User Story:** Sebagai Admin_Operator, saya ingin dapat mengubah email saya dari halaman profil, sehingga saya dapat memperbarui email login jika diperlukan.

#### Acceptance Criteria

1. WHILE an Admin_Operator is on the profile page, THE Sistem_Auth SHALL display the current email in an editable field
2. WHEN an Admin_Operator submits an email change, THE Sistem_Auth SHALL require the current password to be entered for confirmation
3. IF the current password confirmation is incorrect, THEN THE Sistem_Auth SHALL reject the email change and display the message "Password salah"
4. WHEN an Admin_Operator submits a valid email change with correct password, THE Sistem_Auth SHALL validate the new email is unique within the same Branch
5. WHEN the new email passes validation, THE Sistem_Auth SHALL update the email field on the User record
6. WHEN an Admin_Operator submits an email that already exists in the same Branch, THE Sistem_Auth SHALL reject the change and display the message "Email sudah digunakan di cabang ini"

### Requirement 6: Migrasi Backend API Login Endpoint

**User Story:** Sebagai developer, saya ingin backend API login endpoint mendukung autentikasi berbasis email maupun username, sehingga frontend dapat mengirim satu field identifier yang di-route secara otomatis.

#### Acceptance Criteria

1. THE Backend_Auth login endpoint SHALL accept a field named "identifier" (replacing "username") that contains either an email or a NIS value
2. WHEN the Backend_Auth receives a login request with identifier containing "@", THE Backend_Auth SHALL query the User by email field
3. WHEN the Backend_Auth receives a login request with identifier not containing "@", THE Backend_Auth SHALL query the User by username field
4. THE Backend_Auth login response SHALL include the email field in the response data alongside the existing username field
5. IF the identifier field is empty or missing, THEN THE Backend_Auth SHALL return a 422 validation error with message "Identifier wajib diisi"
6. THE Backend_Auth SHALL maintain backward compatibility by also accepting the "username" field; WHEN "username" field is provided and "identifier" is absent, THE Backend_Auth SHALL treat "username" value as the identifier

### Requirement 7: Validasi dan Constraint Email

**User Story:** Sebagai pengelola sistem, saya ingin email divalidasi dengan benar dan memiliki constraint unik per branch, sehingga tidak ada duplikasi dan data tetap konsisten.

#### Acceptance Criteria

1. THE Sistem_Auth SHALL validate email format using standard email validation rules (RFC 5322 compliant)
2. THE Sistem_Auth SHALL enforce email uniqueness per Branch using a composite unique constraint on (email, branch_id) in the database
3. THE Sistem_Auth SHALL allow the same email to exist in different Branches (email uniqueness is scoped to Branch)
4. THE Sistem_Auth SHALL store email in lowercase format to prevent case-sensitive duplicates
5. WHEN comparing email during login, THE Sistem_Auth SHALL perform case-insensitive matching
6. THE Sistem_Auth SHALL allow email to remain null for User accounts with role "siswa"

### Requirement 8: Migrasi Data dan Backward Compatibility

**User Story:** Sebagai pengelola sistem, saya ingin proses migrasi berjalan tanpa downtime dan tidak merusak login siswa yang existing, sehingga operasional sekolah tidak terganggu.

#### Acceptance Criteria

1. THE Sistem_Auth SHALL retain the username field on the User table for all existing accounts
2. THE Sistem_Auth SHALL not modify or remove existing username values during migration
3. WHILE the email field is null for an Admin_Operator account, THE Sistem_Auth SHALL allow that account to login using the username field as fallback
4. WHEN an Admin_Operator account has a non-null email value, THE Sistem_Auth SHALL authenticate that account by email only (username login disabled for that account)
5. THE Sistem_Auth SHALL add an "email" column to the backend User table via a database migration without dropping or altering existing columns
6. THE Sistem_Auth SHALL add a composite unique index on (email, branch_id) that allows null email values (partial unique index)
