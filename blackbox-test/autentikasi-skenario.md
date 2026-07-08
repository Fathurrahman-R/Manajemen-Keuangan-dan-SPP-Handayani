# Rencana Pengujian Blackbox: Autentikasi & Otorisasi

## Ringkasan Singkat Skenario
Berdasarkan panduan `SKILL.md` (Tahap 1), berikut adalah ringkasan pengujian yang telah disesuaikan secara ketat:
- **Jumlah Test Case**: 10 Test Case
- **Aspek yang Dicakup**: Functional, UI-UX, Usability, Compatibility, Security, Performance
- **Teknik yang Dipakai**: Equivalence Partitioning, Error Guessing, State Transition Testing, Exploratory Testing
- **Prioritas**: 7 High, 2 Medium, 1 Low

## User Review Required
Mohon tinjau tabel Test Case di bawah ini. Sesuai instruksi `SKILL.md`, **urutan test case telah diprioritaskan**: *Happy Path -> Negative Case -> Edge Case -> Kombinasi/Exploratory*. Penamaan kolom dan isian (terutama Aspek dan Teknik) telah disesuaikan persis dengan panduan `references/templates.md`.

## Proposed Changes / Skenario Pengujian

### Tabel Test Case

| ID | Deskripsi | Teknik | Aspek | Precondition | Steps | Test Data | Expected Result | Priority |
|---|---|---|---|---|---|---|---|---|
| **TC-01** | Login dengan kredensial Admin | Equivalence Partitioning | Functional | User belum login | 1. Buka landing page<br>2. Klik "Masuk Portal SPP"<br>3. Isi kredensial<br>4. Klik Login | Email: `admin@handayani.test`<br>Pass: `admin123` | Login sukses, diarahkan ke `/dashboard-page`. Panel admin terlihat. | High |
| **TC-02** | Alur Admin Lupa Password | State Transition Testing | Functional | Mailpit aktif, user belum login | 1. Klik Lupa Password<br>2. Masukkan email<br>3. Buka link reset di Mailpit<br>4. Submit sandi baru | Email: `admin@handayani.test` | Token diterima, password admin berhasil diperbarui. | High |
| **TC-03** | Siswa Login Pertama (Verifikasi OTP) | State Transition Testing | Functional | Akun siswa baru, email di DB `null` | 1. Login pakai NIS<br>2. Isi pendaftaran email<br>3. Verifikasi OTP dari Mailpit<br>4. Set sandi baru | NIS: `000001`<br>Pass: `13062015` | Sistem meminta OTP. Email berhasil tersimpan ke DB. Sandi terganti. | High |
| **TC-04** | Siswa Lupa Password | State Transition Testing | Functional | TC-03 harus Pass (email siswa tersimpan) | 1. Klik Lupa Password<br>2. Masukkan email<br>3. Buka link di Mailpit<br>4. Submit sandi baru | Email: [Sesuai TC-03] | Token reset diterima siswa, sandi berhasil di-reset ulang. | High |
| **TC-05** | Login dengan sandi salah | Equivalence Partitioning | Functional | User belum login | 1. Buka halaman login<br>2. Isi pass salah<br>3. Klik Login | Email: `admin@handayani.test`<br>Pass: `salah123` | Login gagal, pesan error "username or password is wrong" muncul. | High |
| **TC-06** | Submit form login kosong | Error Guessing | UI-UX | User di halaman login | 1. Biarkan form kosong<br>2. Klik Login | *[Kosong]* | Validasi UI form berjalan, field disorot merah (wajib diisi). Form tidak memicu error server 500. | Medium |
| **TC-07** | Akses paksa URL Admin | Error Guessing | Security | User dalam status logout | 1. Ketik URL `/dashboard-page`<br>2. Tekan Enter | URL: `/dashboard-page` | Akses dicegat, pengguna di-redirect otomatis ke `/login`. | High |
| **TC-08** | Tampilan Login di Layar Mobile | Exploratory Testing | Compatibility | User di halaman login | 1. Ubah ukuran viewport ke Mobile (375x667)<br>2. Cek layout form | Resolusi: 375x667 | Form responsive, tombol/field tidak bertumpuk atau terpotong. | Medium |
| **TC-09** | Navigasi Form via Keyboard | Exploratory Testing | Usability | User di halaman login | 1. Klik input email<br>2. Tekan Tab ke input pass<br>3. Tekan Enter | `admin@handayani.test` / `admin123` | Kursor berpindah dengan jelas, Enter men-trigger submit Login. | Low |
| **TC-10** | Waktu Respons Submit Login | Exploratory Testing | Performance | User siap klik Login | 1. Klik Login dengan data valid<br>2. Observasi waktu tunggu | `admin@handayani.test` / `admin123` | Respons masuk ke dashboard wajar (< 3 detik), browser tidak freeze. | Low |

## Verification Plan
Jika Anda menekan tombol Approve (atau memberikan persetujuan di chat), *subagent browser* akan ditugaskan masuk ke **Tahap 2** untuk mengeksekusi ke-10 Test Case ini secara berurutan dan saya akan menyusun laporan akhirnya.
