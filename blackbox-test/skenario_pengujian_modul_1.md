# Skenario Pengujian Blackbox — Modul 1: Autentikasi & Keamanan

> **Tanggal:** 8 Juli 2026
> **Peran:** Software QA Engineer (Blackbox Testing)
> **Lingkungan Uji:** `http://127.0.0.1:8000` (Frontend-v2 Filament)
> **Akun:** `admin@handayani.test` / `admin123` (Superadmin) — khusus Portal Siswa Dashboard menggunakan NIS
> **Teknik:** Equivalence Partitioning, Boundary Value Analysis, Error Guessing, State Transition Testing, Exploratory Testing
> **Aspek:** Functional (wajib), Security, UI/UX, Usability

---

## Daftar Sub-Modul

| No | Sub-Modul | Halaman Filament | Backend Controller | API Endpoints |
|---|-----------|-----------------|--------------------|---------------|
| 1 | **Login (AUT)** | `/login` | `AuthController@login` | `POST /login` |
| 2 | **Logout (LGT)** | tombol Logout (sidebar/header) | `AuthController@logout` | `DELETE /logout` |
| 3 | **Lupa & Reset Password (FRP)** | `/forgot-password`, `/reset-password` | `PasswordResetController`, `PasswordResetService` | `POST /forgot-password`, `POST /reset-password` |
| 4 | **First Login & OTP (FLG)** | `/change-password` (forced redirect) | `UserController` (sendVerificationOtp/verifyEmailOtp) | `POST /users/send-verification-otp`, `POST /users/verify-email-otp` |
| 5 | **Ganti Password (CHP)** | `/profile` | `UserController@changePassword` | `POST /users/change-password` |
| 6 | **Unsubscribe Email (UNS)** | halaman publik `/unsubscribe/{token}` | `EmailOptOutController` | `GET /unsubscribe/{token}`, `POST /unsubscribe/{token}` |
| 7 | **Security & RBAC (SEC)** | lintas halaman | `AuthController`, `UserController` | lintas endpoint |

---

## 1. Sub-Modul: Login (AUT)

**Halaman:** `/login`
**Fitur:** Login admin via email, login siswa via NIS, rate limiting, akun nonaktif, identifier routing (`IdentifierService`)

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **AUT-001** | Login admin — email & password valid | Equivalence Partitioning | Functional, Security | Admin `admin@handayani.test` aktif | 1. Buka `/login`<br>2. Isi email `admin@handayani.test`<br>3. Isi password `admin123`<br>4. Klik "Masuk" | Email: `admin@handayani.test`<br>Password: `admin123` | Redirect ke dashboard admin. Session token tersimpan. | High |
| **AUT-002** | Login admin — password salah | Equivalence Partitioning | Functional, Security | Admin `admin@handayani.test` aktif | 1. Buka `/login`<br>2. Isi email valid<br>3. Isi password salah<br>4. Klik "Masuk" | Password: `salah123` | Gagal login, muncul error "username or password is wrong". Tidak redirect. | High |
| **AUT-003** | Login admin — email tidak terdaftar | Equivalence Partitioning | Functional, Security | Email belum ada di sistem | 1. Isi email tak dikenal<br>2. Isi password apapun<br>3. Klik "Masuk" | Email: `tidakada@test.com` | Gagal login, error "username or password is wrong". | High |
| **AUT-004** | Login siswa — NIS valid | Equivalence Partitioning | Functional | Siswa dengan NIS `000001` aktif, password default | 1. Buka `/login`<br>2. Isi NIS `000001`<br>3. Isi password<br>4. Klik "Masuk" | NIS: `000001`<br>Password: default | Redirect ke portal siswa `/portal`. Token session tersimpan. | High |
| **AUT-005** | Login admin via username (tanpa email) — ditolak | Error Guessing | Security | Admin dengan email terisi | 1. Isi username (bukan email)<br>2. Isi password | Username: `admin123` | Gagal: `IdentifierService` mengembalikan null untuk admin yang sudah punya email. | High |
| **AUT-006** | Login — akun nonaktif | Error Guessing | Security | Akun admin dinonaktifkan | 1. Isi email akun nonaktif<br>2. Isi password benar | Akun target nonaktif | Gagal, error "Akun tidak aktif. Hubungi admin sekolah." | High |
| **AUT-007** | Login — kolom kosong (email & password) | Boundary Value Analysis | Functional | Belum login | 1. Biarkan email kosong<br>2. Biarkan password kosong<br>3. Klik "Masuk" | Email: ""<br>Password: "" | Validasi form client-side: field wajib diisi. | Medium |
| **AUT-008** | Login — rate limiting 5x gagal | State Transition | Security | 5x gagal berturut-turut | 1. Login gagal 5x<br>2. Coba login ke-6 kali | Kredensial salah tiap kali | Percobaan ke-6 ditolak: notifikasi "too many attempts". | High |
| **AUT-009** | Login — email format tidak valid | Equivalence Partitioning | UI/UX | Belum login | 1. Isi email tanpa `@`<br>2. Isi password<br>3. Klik "Masuk" | Email: `admin.test`<br>Password: `admin123` | IdentifierService menganggap sebagai username, login gagal (bukan admin) atau validasi form jika ada. | Low |
| **AUT-010** | Login — password minimal length | Error Guessing | Security | Belum login | 1. Isi email valid<br>2. Isi password sangat pendek | Password: `12` | Login gagal (backend Hash::check). Pesan error generik. | Low |

---

## 2. Sub-Modul: Logout (LGT)

**Halaman:** tombol Logout di sidebar/header
**Fitur:** Logout dari sistem, token dihapus

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **LGT-001** | Logout dari dashboard admin | State Transition | Functional, Security | Admin sudah login | 1. Klik tombol Logout (sidebar)<br>2. Konfirmasi (jika ada modal) | N/A | Redirect ke `/login`. Session token dihapus. Tidak bisa akses dashboard setelahnya. | High |
| **LGT-002** | Logout dari portal siswa | State Transition | Functional, Security | Siswa sudah login | 1. Klik tombol Logout (header/sidebar portal)<br>2. Konfirmasi | N/A | Redirect ke `/login`. Session token dihapus. Tidak bisa akses portal setelahnya. | Medium |

---

## 3. Sub-Modul: Lupa & Reset Password (FRP)

**Halaman:** `/forgot-password`, `/reset-password?token=xxx`
**Fitur:** Kirim link reset password via email, validasi token, reset password, anti-enumeration

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **FRP-001** | Lupa password — email terdaftar | Equivalence Partitioning | Functional | Admin `admin@handayani.test` aktif | 1. Buka `/login`<br>2. Klik "Lupa Password"<br>3. Isi email `admin@handayani.test`<br>4. Klik Kirim | Email: `admin@handayani.test` | Notifikasi sukses "Link reset password telah dikirim". Cek Mailpit (http://127.0.0.1:8025) ada email. | High |
| **FRP-002** | Lupa password — email tidak terdaftar | Equivalence Partitioning | Security | Email tidak ada di sistem | 1. Klik "Lupa Password"<br>2. Isi email random<br>3. Klik Kirim | Email: `random@test.com` | Notifikasi sukses SAMA PERSIS (anti-enumeration). Tidak ada email di Mailpit. | High |
| **FRP-003** | Lupa password — email kosong | Error Guessing | UI/UX | Belum login | 1. Klik "Lupa Password"<br>2. Biarkan email kosong<br>3. Klik Kirim | Email: "" | Validasi form: field email wajib diisi. | Medium |
| **FRP-004** | Reset password — token valid, password baru valid | State Transition | Functional | Token reset valid dari email | 1. Buka link dari email (Mailpit)<br>2. Isi password baru min 8 karakter<br>3. Isi konfirmasi password<br>4. Klik Reset | Password: `passwordBaru123!`<br>Konfirmasi: sama | Notifikasi "Password Berhasil Direset". Bisa login dengan password baru. | High |
| **FRP-005** | Reset password — token sudah kadaluarsa | State Transition | Security | Token expired (60 menit) | 1. Buka link dengan token expired | Token expired | Halaman tampil error "Token tidak valid atau sudah kadaluarsa". Form reset disembunyikan. | High |
| **FRP-006** | Reset password — password < 8 karakter | Boundary Value Analysis | Functional | Token valid | 1. Buka link reset <br>2. Isi password 7 karakter | Password: `Abc1234` (7 chars) | Validasi form: password minimal 8 karakter. | Medium |
| **FRP-007** | Reset password — konfirmasi tidak sama | Error Guessing | UI/UX | Token valid | 1. Isi password baru<br>2. Isi konfirmasi berbeda | Password: `passwordBaru123`<br>Konfirmasi: `beda123` | Validasi form: "Konfirmasi password tidak cocok". | Medium |

---

## 4. Sub-Modul: First Login & OTP (FLG)

**Halaman:** forced redirect ke `/change-password`
**Fitur:** User baru dengan `must_change_password=true` dipaksa ganti password + verifikasi OTP email

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **FLG-001** | First login — forced change password | State Transition | Functional, Security | User baru `must_change_password=true` | 1. Login dgn user baru (first time)<br>2. Amati redirect | - | Setelah login sukses, langsung redirect ke `/change-password`. Tidak bisa akses dashboard/portal. | High |
| **FLG-002** | First login — ganti password via OTP — sukses | State Transition | Functional | User di halaman change-password | 1. Klik "Kirim OTP"<br>2. Cek Mailpit (http://127.0.0.1:8025)<br>3. Masukkan OTP 6 digit<br>4. Isi password baru & konfirmasi<br>5. Klik Simpan | Email: user punya<br>OTP: dari Mailpit<br>Password: `passwordBaru123!` | OTP valid, password berubah. `must_change_password=false`. Redirect ke dashboard. | High |
| **FLG-003** | First login — OTP salah | Error Guessing | Security | OTP sudah dikirim | 1. Masukkan OTP asal | OTP: `123456` | Error "Kode OTP tidak valid atau sudah kadaluarsa". | High |
| **FLG-004** | First login — rate limit OTP (3x) | State Transition | Security | User minta OTP berulang | 1. Klik Kirim OTP 4x berturut-turut | - | Percobaan ke-4 ditolak: "Terlalu banyak permintaan OTP". | High |
| **FLG-005** | First login — ganti password via link reset (alternate flow) | Equivalence Partitioning | Functional | User harus ganti password | 1. Login ulang (masih forced change)<br>2. Alternatif: buka `/forgot-password`<br>3. Kirim link reset, set password baru | Email: terdaftar | Link reset juga berhasil. Password berubah. `must_change_password=false`. | Medium |

---

## 5. Sub-Modul: Ganti Password (CHP)

**Halaman:** `/profile`
**Fitur:** Ubah password dengan konfirmasi password lama

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **CHP-001** | Ganti password — sukses | State Transition | Functional | Admin sudah login | 1. Buka `/profile`<br>2. Isi password saat ini<br>3. Isi password baru min 8 karakter<br>4. Isi konfirmasi<br>5. Klik Simpan | Current: `admin123`<br>New: `admin456!`<br>Konfirmasi: sama | Notifikasi "Password berhasil diubah". Bisa login dgn password baru. | High |
| **CHP-002** | Ganti password — password saat ini salah | Error Guessing | Security | Admin sudah login | 1. Isi password saat ini salah<br>2. Isi password baru valid | Current: `salah123` | Error "Password saat ini tidak sesuai". | High |

---

## 6. Sub-Modul: Unsubscribe Email (UNS)

**Halaman:** halaman publik `/unsubscribe/{token}`
**Fitur:** Berhenti berlangganan notifikasi email

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **UNS-001** | Unsubscribe — token valid | State Transition | Functional | Ada email notifikasi terkirim dgn token unsubscribe | 1. Cek email di Mailpit<br>2. Klik link unsubscribe<br>3. Konfirmasi (pilih jenis notifikasi / submit) | Token: dari email | Halaman tampil. Setelah submit, notifikasi berhenti. | Medium |
| **UNS-002** | Unsubscribe — token tidak valid | Error Guessing | Security | Token random/acak | 1. Buka `/unsubscribe/{token}` dengan token palsu | Token: `abc123` | 404 "Link tidak valid atau sudah kadaluarsa." | Medium |

---

## 7. Sub-Modul: Security & RBAC (SEC)

**Halaman:** lintas halaman
**Fitur:** Superadmin bypass, akses tanpa permission, akses halaman admin oleh siswa, sesi expired

### Test Cases

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Prioritas |
|---|---|---|---|---|---|---|---|---|
| **SEC-001** | Akses dashboard admin oleh siswa — ditolak | Equivalence Partitioning | Security | Siswa sudah login di portal | 1. Login sebagai siswa<br>2. Paksa buka `/dashboard-page` | N/A | Redirect ke portal atau 403 Forbidden. | High |
| **SEC-002** | Akses portal siswa oleh admin — ditolak | Equivalence Partitioning | Security | Admin sudah login | 1. Login sebagai admin<br>2. Paksa buka `/portal/beranda` | N/A | Redirect ke dashboard atau 403 Forbidden. | High |
| **SEC-003** | Akses halaman tanpa token — redirect ke login | State Transition | Security | Belum login | 1. Langsung buka `/dashboard-page` (tanpa login) | N/A | Redirect ke `/login`. | High |
| **SEC-004** | Token expired — redirect ke login | Error Guessing | Security | Token expired (sesi habis) | 1. Tunggu sampai token expired (atau apus session manual)<br>2. Coba akses halaman | N/A | Redirect ke `/login`. | High |

---

## Ringkasan Skenario

- **Total test case:** 32
- **Aspek yang dicakup:** Functional, Security, UI/UX, Usability
- **Teknik yang dipakai:** Equivalence Partitioning, Boundary Value Analysis, State Transition Testing, Error Guessing, Exploratory Testing
- **Prioritas:** High (22), Medium (8), Low (2)
- **Sub-modul:** 7 (Login, Logout, Lupa/Reset Password, First Login & OTP, Ganti Password, Unsubscribe, Security)

---

*Dokumen skenario pengujian — siap direview dan dieksekusi setelah disetujui.*
