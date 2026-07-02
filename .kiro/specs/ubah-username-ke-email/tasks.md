# Implementation Plan: Ubah Username ke Email

## Overview

Migrasi login admin/operator dari username ke email dengan pendekatan additive migration. Implementasi mencakup: database migration, backend services (IdentifierService, EmailValidationService, PasswordResetService), update AuthController, controller baru (EmailPopulationController, PasswordResetController), update frontend login form, halaman forgot/reset password, email population tool, dan update profil page.

## Tasks

- [x] 1. Database Migration dan Model Update
  - [x] 1.1 Buat migration untuk menambah kolom email dan is_active pada tabel users
    - Tambah kolom `email` VARCHAR(255) NULL setelah `username`
    - Tambah kolom `is_active` BOOLEAN NOT NULL DEFAULT true setelah `branch_id`
    - Buat partial unique index `users_email_branch_unique` pada (email, branch_id) WHERE email IS NOT NULL
    - _Requirements: 7.2, 8.5, 8.6_

  - [x] 1.2 Buat migration untuk tabel password_reset_tokens
    - Buat tabel dengan kolom: id, email, token (unique), used (default false), created_at, expires_at
    - Tambah index pada kolom email
    - _Requirements: 4.2, 4.6_

  - [x] 1.3 Update User model dengan email attribute dan mutator
    - Tambah `email` dan `is_active` ke `$fillable`
    - Tambah cast `is_active` ke boolean
    - Buat mutator `setEmailAttribute` yang menyimpan email dalam lowercase
    - _Requirements: 7.4, 8.1, 8.2_

  - [ ]* 1.4 Write unit tests untuk User model email mutator
    - Test email disimpan lowercase
    - Test null email tetap null
    - Test trim whitespace
    - _Requirements: 7.4_

- [x] 2. Backend Services — EmailValidationService
  - [x] 2.1 Buat EmailValidationService class
    - Implementasi `isValidFormat()` dengan validasi RFC 5322
    - Implementasi `isUniqueInBranch()` dengan query ke users table, exclude user ID tertentu
    - Implementasi `normalize()` yang lowercase dan trim email
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

  - [ ]* 2.2 Write property test untuk EmailValidationService — email uniqueness per branch
    - **Property 2: Email Uniqueness per Branch**
    - Generate random email/branch combinations, verify constraint enforcement
    - **Validates: Requirements 1.2, 7.2, 7.3**

  - [ ]* 2.3 Write property test untuk EmailValidationService — case normalization
    - **Property 5: Case-insensitive Email Storage and Matching**
    - For any email, normalize() stored then matched case-insensitively
    - **Validates: Requirements 7.4, 7.5**

- [x] 3. Backend Services — IdentifierService
  - [x] 3.1 Buat IdentifierService class
    - Implementasi `isEmail()` yang cek keberadaan karakter "@"
    - Implementasi `findUserByIdentifier()` dengan routing otomatis:
      - Jika mengandung "@": query by email (case-insensitive, is_active = true)
      - Jika tidak: query by username (is_active = true)
      - Jika admin/operator sudah punya email, username login disabled untuk akun tersebut
    - _Requirements: 3.2, 3.3, 3.4, 3.5, 3.7, 3.8, 6.2, 6.3, 8.3, 8.4_

  - [ ]* 3.2 Write property test untuk IdentifierService — identifier routing
    - **Property 3: Identifier Routing**
    - Generate random strings with/without "@", verify correct routing
    - **Validates: Requirements 3.2, 3.3, 3.4, 3.5, 6.2, 6.3**

  - [ ]* 3.3 Write property test untuk IdentifierService — active user scoping
    - **Property 4: Active User Scoping**
    - Verify inactive users never authenticated
    - **Validates: Requirements 3.7, 3.8**

  - [ ]* 3.4 Write property test untuk IdentifierService — email-based auth transition
    - **Property 12: Email-based Auth Transition**
    - Generate admin/operator users with/without email, verify correct auth path
    - **Validates: Requirements 8.3, 8.4**

- [x] 4. Checkpoint — Pastikan services dan migration berjalan
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Backend — Update AuthController dan Login Endpoint
  - [x] 5.1 Buat LoginRequest baru yang menerima field "identifier"
    - Validasi: identifier required (atau fallback ke username field jika identifier absent)
    - Validasi: password required
    - _Requirements: 6.1, 6.5, 6.6_

  - [x] 5.2 Update AuthController login method
    - Gunakan IdentifierService untuk resolve user dari identifier
    - Validasi password dengan Hash::check
    - Return response termasuk field email
    - Maintain backward compatibility: jika "username" field ada dan "identifier" absent, treat "username" sebagai identifier
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.6_

  - [ ]* 5.3 Write property test untuk backward compatibility
    - **Property 11: Backward Compatibility — Username Field**
    - Login request dengan "username" field tanpa "identifier" harus tetap bekerja
    - **Validates: Requirements 6.6**

  - [ ]* 5.4 Write property test untuk login response includes email
    - **Property 13: Login Response Includes Email**
    - Verify response data selalu include email field
    - **Validates: Requirements 6.4**

- [x] 6. Backend — PasswordResetService dan Controller
  - [x] 6.1 Buat PasswordResetService class
    - Implementasi `sendResetLink()`: buat token, simpan ke password_reset_tokens, kirim email (anti-enumeration: response sama untuk email valid/invalid)
    - Implementasi `validateToken()`: cek token exists, not used, not expired (60 menit)
    - Implementasi `resetPassword()`: update password user, set token used = true
    - _Requirements: 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [x] 6.2 Buat PasswordResetController
    - `POST /api/forgot-password`: terima email, panggil sendResetLink
    - `GET /api/reset-password/{token}`: validasi token
    - `POST /api/reset-password`: reset password dengan token
    - Siswa tidak bisa menggunakan fitur ini (cek role)
    - _Requirements: 4.1, 4.2, 4.4, 4.5, 4.7, 4.8_

  - [ ]* 6.3 Write property test untuk anti-enumeration response
    - **Property 8: Anti-enumeration Response**
    - Generate existing/non-existing emails, verify identical responses
    - **Validates: Requirements 4.3**

  - [ ]* 6.4 Write property test untuk reset token single-use
    - **Property 9: Reset Token Single-use Invalidation**
    - Generate tokens, use them, verify single-use enforcement
    - **Validates: Requirements 4.5**

- [x] 7. Backend — EmailPopulationController
  - [x] 7.1 Buat EmailPopulationController
    - `GET /api/users/email-population`: list admin/operator tanpa email di branch aktif
    - `GET /api/users/email-population/progress`: return {populated, total, complete, message}
    - `PATCH /api/users/{id}/email`: set email untuk user tertentu dengan validasi
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [ ]* 7.2 Write property test untuk email population filter
    - **Property 6: Email Population Filter**
    - Generate random user sets, verify only admin/operator with null email returned
    - **Validates: Requirements 2.1**

  - [ ]* 7.3 Write property test untuk progress calculation
    - **Property 7: Progress Calculation**
    - Generate random user sets, verify count accuracy
    - **Validates: Requirements 2.4, 2.5**

- [x] 8. Backend — Update UserController untuk Email di Profil
  - [x] 8.1 Update UserController untuk endpoint update email dari profil
    - `PATCH /api/users/current/email`: terima new_email dan current_password
    - Validasi current password sebelum update
    - Validasi email unique dalam branch
    - Update email field
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

  - [ ]* 8.2 Write property test untuk email update with password confirmation
    - **Property 10: Email Update with Password Confirmation**
    - Verify rejection when password wrong, acceptance when correct + unique email
    - **Validates: Requirements 5.2, 5.4, 5.5**

- [x] 9. Backend — Update User Registration/Creation Validation
  - [x] 9.1 Update UserRegisterRequest dan UserRequest untuk email wajib pada admin/operator
    - Email required jika role admin/operator
    - Email optional (nullable) jika role siswa
    - Validasi email unique per branch
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [ ]* 9.2 Write property test untuk role-based email requirement
    - **Property 1: Role-based Email Requirement**
    - Generate user payloads with random roles, verify email requirement logic
    - **Validates: Requirements 1.1, 1.4, 7.6**

- [x] 10. Checkpoint — Pastikan semua backend endpoint berfungsi
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Backend — Register Routes
  - [x] 11.1 Tambahkan routes baru di routes/api.php
    - `POST /api/forgot-password` → PasswordResetController@sendResetLink
    - `GET /api/reset-password/{token}` → PasswordResetController@validateToken
    - `POST /api/reset-password` → PasswordResetController@resetPassword
    - `GET /api/users/email-population` → EmailPopulationController@index (auth required)
    - `GET /api/users/email-population/progress` → EmailPopulationController@progress (auth required)
    - `PATCH /api/users/{id}/email` → EmailPopulationController@update (auth required)
    - `PATCH /api/users/current/email` → UserController@updateEmail (auth required)
    - _Requirements: 4.1, 2.1, 5.1, 6.1_

- [x] 12. Frontend — Update Login Page
  - [x] 12.1 Update login form pada frontend-v2 (Filament)
    - Ubah label input dari "Username" menjadi "Email / NIS"
    - Ubah field name yang dikirim ke backend dari "username" menjadi "identifier"
    - Tambah link "Lupa Password?" yang navigasi ke halaman forgot password
    - Update error message display untuk format baru
    - _Requirements: 3.1, 3.6, 4.1_

- [x] 13. Frontend — Halaman Forgot Password dan Reset Password
  - [x] 13.1 Buat halaman Forgot Password pada frontend-v2
    - Form input email
    - Submit ke `POST /api/forgot-password`
    - Tampilkan pesan sukses yang sama untuk semua kasus (anti-enumeration)
    - _Requirements: 4.1, 4.2, 4.3_

  - [x] 13.2 Buat halaman Reset Password pada frontend-v2
    - Terima token dari URL parameter
    - Validasi token via `GET /api/reset-password/{token}`
    - Form input password baru + konfirmasi password
    - Submit ke `POST /api/reset-password`
    - Tampilkan error jika token expired/used
    - _Requirements: 4.4, 4.5, 4.7_

- [x] 14. Frontend — Email Population Tool Page
  - [x] 14.1 Buat halaman Email Population Tool pada frontend-v2
    - Tabel daftar akun admin/operator tanpa email (dari `GET /api/users/email-population`)
    - Inline edit field untuk mengisi email per baris
    - Submit per baris ke `PATCH /api/users/{id}/email`
    - Tampilkan inline error jika email invalid/duplicate tanpa clear value lain
    - Progress bar dari `GET /api/users/email-population/progress`
    - Tampilkan pesan "Semua akun admin/operator sudah memiliki email. Migrasi login siap diaktifkan." saat complete
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 15. Frontend — Update Profile Page untuk Email Edit
  - [x] 15.1 Update halaman profil untuk menampilkan dan edit email
    - Tampilkan email saat ini dalam editable field
    - Form update email memerlukan input current password
    - Validasi dan tampilkan error messages sesuai response backend
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

- [x] 16. Final Checkpoint — Pastikan semua tests pass dan integrasi berjalan
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- Backend menggunakan PHP/Laravel, frontend menggunakan Filament (Laravel-based admin panel)
- Migration bersifat additive — tidak ada kolom yang dihapus atau diubah
- Backward compatibility dijaga: field "username" tetap diterima di login endpoint

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["1.3"] },
    { "id": 2, "tasks": ["1.4", "2.1"] },
    { "id": 3, "tasks": ["2.2", "2.3", "3.1"] },
    { "id": 4, "tasks": ["3.2", "3.3", "3.4", "5.1", "9.1"] },
    { "id": 5, "tasks": ["5.2", "9.2"] },
    { "id": 6, "tasks": ["5.3", "5.4", "6.1"] },
    { "id": 7, "tasks": ["6.2", "7.1", "8.1"] },
    { "id": 8, "tasks": ["6.3", "6.4", "7.2", "7.3", "8.2", "11.1"] },
    { "id": 9, "tasks": ["12.1", "13.1", "14.1", "15.1"] },
    { "id": 10, "tasks": ["13.2"] }
  ]
}
```
