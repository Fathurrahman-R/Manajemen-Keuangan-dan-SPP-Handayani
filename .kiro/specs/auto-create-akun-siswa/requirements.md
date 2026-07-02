# Requirements Document

## Introduction

Fitur ini secara otomatis membuat akun user (login) untuk setiap siswa ketika siswa didaftarkan ke dalam sistem. Akun ini memungkinkan siswa maupun wali (orang tua) untuk login ke aplikasi dan melihat tagihan melalui Tagihan_Card_View (student view). Satu akun per siswa bersifat shared — bisa digunakan oleh siswa sendiri maupun wali-nya. Wali yang memiliki lebih dari satu anak dapat melihat tagihan semua anaknya dari satu akun login (sistem mendeteksi sibling berdasarkan kesamaan ayah_id/ibu_id/wali_id).

Fitur ini juga memperbaiki proses input siswa agar admin dapat memilih data orang tua (ayah/ibu/wali) yang sudah ada di sistem, sehingga anak-anak yang bersaudara terhubung ke record orang tua yang sama. Selain itu, fitur mencakup pembuatan akun secara bulk untuk siswa yang sudah terdaftar, manajemen kredensial, serta deaktivasi akun otomatis ketika siswa tidak lagi aktif.

## Glossary

- **Sistem_Akun**: Modul yang mengelola pembuatan, pengaitan, dan deaktivasi akun user untuk siswa
- **Akun_Siswa**: Akun user dengan role "siswa" yang terhubung ke satu record Siswa, dapat digunakan oleh siswa maupun wali-nya
- **User**: Entitas akun login pada tabel users di frontend-v2, memiliki username, name, password, branch_id, siswa_id, is_active, dan must_change_password
- **Siswa**: Entitas murid yang memiliki NIS, nama, tanggal_lahir, dan relasi ke Ayah, Ibu, dan Wali
- **Sibling**: Siswa lain yang memiliki ayah_id, ibu_id, atau wali_id yang sama dengan siswa pemilik akun
- **Wali_Entity**: Record orang tua/wali pada tabel ayah, ibu, atau walis yang dapat di-share antar Siswa yang bersaudara
- **Kredensial_Default**: Username (NIS) dan password (tanggal lahir format DDMMYYYY) yang digenerate otomatis saat akun dibuat
- **Admin_Operator**: Pengguna dengan role admin atau operator yang mengelola data siswa dan akun
- **Branch**: Cabang sekolah yang memisahkan data antar lokasi
- **Tagihan_Card_View**: Tampilan tagihan berbasis card yang dapat diakses oleh siswa/wali (dari spec tagihan-card-view)
- **Form_Siswa**: Form pendaftaran siswa yang mencakup data siswa dan data orang tua

## Requirements

### Requirement 1: Select atau Buat Baru Data Orang Tua di Form Siswa

**User Story:** Sebagai Admin_Operator, saya ingin dapat memilih data orang tua yang sudah ada di sistem saat mendaftarkan siswa baru, sehingga anak-anak yang bersaudara terhubung ke record orang tua yang sama.

#### Acceptance Criteria

1. WHEN an Admin_Operator is filling the Form_Siswa for jenjang MI, THE Form_Siswa SHALL provide a search field for Ayah that allows searching existing Ayah records by nama
2. WHEN an Admin_Operator is filling the Form_Siswa for jenjang MI, THE Form_Siswa SHALL provide a search field for Ibu that allows searching existing Ibu records by nama
3. WHEN an Admin_Operator is filling the Form_Siswa for jenjang TK or KB, THE Form_Siswa SHALL provide a search field for Wali_Entity that allows searching existing Wali records by nama
4. WHEN an Admin_Operator selects an existing Wali_Entity from the search results, THE Form_Siswa SHALL populate the parent fields with the selected record data and use the existing record id as the foreign key
5. WHEN an Admin_Operator does not select an existing record and fills in the parent fields manually, THE Form_Siswa SHALL create a new Wali_Entity record as the current behavior
6. THE Form_Siswa SHALL scope the search results to Wali_Entity records within the same branch as the Admin_Operator active branch

### Requirement 2: Pembuatan Akun Siswa Otomatis

**User Story:** Sebagai Admin_Operator, saya ingin akun siswa dibuat secara otomatis saat siswa didaftarkan, sehingga siswa dan wali langsung memiliki akses login tanpa proses manual tambahan.

#### Acceptance Criteria

1. WHEN a new Siswa record is created, THE Sistem_Akun SHALL create an Akun_Siswa with username equal to the Siswa NIS value
2. WHEN a new Siswa record is created, THE Sistem_Akun SHALL set the Akun_Siswa password to the Siswa tanggal_lahir formatted as DDMMYYYY
3. WHEN a new Siswa record is created, THE Sistem_Akun SHALL assign the "siswa" role to the Akun_Siswa using spatie/laravel-permission
4. WHEN a new Siswa record is created, THE Sistem_Akun SHALL set the Akun_Siswa branch_id to match the Siswa branch_id
5. WHEN a new Siswa record is created, THE Sistem_Akun SHALL set the Akun_Siswa name to the Siswa nama value
6. WHEN a new Siswa record is created, THE Sistem_Akun SHALL store the siswa_id on the Akun_Siswa record to link the account to the Siswa
7. IF the Siswa NIS already exists as a username in the User table within the same branch, THEN THE Sistem_Akun SHALL skip account creation and log a warning

### Requirement 3: Tampilan Tagihan Sibling (Multi-Anak)

**User Story:** Sebagai wali yang memiliki lebih dari satu anak, saya ingin dapat melihat tagihan semua anak saya dari satu akun login, sehingga saya tidak perlu login ke banyak akun berbeda.

#### Acceptance Criteria

1. WHILE a User with role "siswa" is logged in, THE Tagihan_Card_View SHALL identify all Sibling of the logged-in Siswa by finding other Siswa records that share the same non-null ayah_id, ibu_id, or wali_id
2. WHILE a User with role "siswa" is logged in and Sibling exist, THE Tagihan_Card_View SHALL display a selector showing the logged-in Siswa nama and all identified Sibling nama values
3. WHEN the logged-in User selects a Sibling from the selector, THE Tagihan_Card_View SHALL display the tagihan belonging to the selected Sibling
4. THE Tagihan_Card_View SHALL default to showing the tagihan of the Siswa whose account is being used (the account owner)
5. WHILE a User with role "siswa" is logged in and the Siswa has no Sibling, THE Tagihan_Card_View SHALL hide the sibling selector and display only the account owner tagihan

### Requirement 4: Manajemen Kredensial oleh Admin

**User Story:** Sebagai Admin_Operator, saya ingin dapat mengelola kredensial akun siswa, sehingga saya dapat mendistribusikan informasi login dan menangani masalah akses.

#### Acceptance Criteria

1. WHEN an Admin_Operator requests a password reset for an Akun_Siswa, THE Sistem_Akun SHALL reset the password to the Siswa tanggal_lahir formatted as DDMMYYYY
2. WHEN an Admin_Operator requests to view credentials for selected accounts, THE Sistem_Akun SHALL display the username (NIS) and the password pattern description for each selected account
3. WHEN an Admin_Operator requests to print credentials, THE Sistem_Akun SHALL generate a printable PDF document containing username and password information for each selected account, formatted one account per row
4. THE Sistem_Akun SHALL store a boolean field "must_change_password" on each User account, set to true upon account creation
5. WHEN a User with must_change_password equal to true logs in, THE Sistem_Akun SHALL redirect the User to a password change page before allowing access to other features
6. WHEN the User submits a valid new password on the password change page, THE Sistem_Akun SHALL update the password and set must_change_password to false

### Requirement 5: Pembuatan Akun Secara Bulk

**User Story:** Sebagai Admin_Operator, saya ingin dapat membuat akun secara massal untuk siswa yang sudah terdaftar tetapi belum memiliki akun, sehingga saya tidak perlu membuat akun satu per satu.

#### Acceptance Criteria

1. WHEN an Admin_Operator opens the bulk account creation page, THE Sistem_Akun SHALL display a list of all Siswa in the active branch that do not have an associated Akun_Siswa
2. THE Sistem_Akun SHALL provide filter options on the bulk creation page by jenjang and kelas
3. WHEN an Admin_Operator selects one or more Siswa from the list and confirms bulk creation, THE Sistem_Akun SHALL create Akun_Siswa for each selected Siswa following the same rules as Requirement 2
4. WHEN bulk account creation completes, THE Sistem_Akun SHALL display a summary showing the total number of accounts created and any errors encountered
5. IF an error occurs during bulk account creation for a specific Siswa, THEN THE Sistem_Akun SHALL skip that Siswa, log the error, and continue processing the remaining Siswa
6. WHEN an Admin_Operator clicks "Select All", THE Sistem_Akun SHALL select all visible Siswa in the filtered list for bulk creation

### Requirement 6: Deaktivasi Akun Otomatis

**User Story:** Sebagai Admin_Operator, saya ingin akun siswa dinonaktifkan secara otomatis ketika siswa tidak lagi aktif, sehingga mantan siswa tidak dapat mengakses sistem.

#### Acceptance Criteria

1. WHEN a Siswa status changes to "Lulus", "Pindah", or "Keluar", THE Sistem_Akun SHALL set the associated Akun_Siswa is_active field to false
2. WHILE a User account has is_active equal to false, THE Sistem_Akun SHALL reject login attempts for that account and display the message "Akun tidak aktif. Hubungi admin sekolah."
3. WHEN an Admin_Operator manually reactivates a User account, THE Sistem_Akun SHALL set the is_active field to true and allow login
4. WHEN a Siswa status changes back to "Aktif", THE Sistem_Akun SHALL set the associated Akun_Siswa is_active field to true

### Requirement 7: Multi-Branch Isolation

**User Story:** Sebagai Admin_Operator, saya ingin akun siswa terisolasi per cabang, sehingga data antar cabang tidak tercampur.

#### Acceptance Criteria

1. THE Sistem_Akun SHALL set the branch_id of every created Akun_Siswa to match the branch_id of the associated Siswa
2. WHILE an Admin_Operator is managing accounts, THE Sistem_Akun SHALL only display Akun_Siswa belonging to the Admin_Operator active branch
3. WHILE a User with role "siswa" is logged in, THE Sistem_Akun SHALL scope the Sibling detection to Siswa within the same branch_id only

### Requirement 8: Penambahan Role Siswa ke Sistem

**User Story:** Sebagai Admin_Operator, saya ingin role "siswa" tersedia di sistem permission, sehingga akun siswa dapat dibedakan dari akun admin/operator.

#### Acceptance Criteria

1. THE Sistem_Akun SHALL register a "siswa" role in the spatie/laravel-permission system via database seeder or migration
2. THE Sistem_Akun SHALL ensure the "siswa" role has no admin panel permissions, limiting access to the Tagihan_Card_View student view only
3. WHEN a User with role "siswa" attempts to access admin panel routes, THE Sistem_Akun SHALL deny access and return a 403 Forbidden response
