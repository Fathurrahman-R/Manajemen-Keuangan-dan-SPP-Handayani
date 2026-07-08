# Laporan Blackbox Testing - Handayani Portal

## 1. Ringkasan Pengujian
Pengujian ini mencakup Functional Testing, Validation Testing, Integration Testing, dan Keamanan Akses (MVP Scope). Pengujian dilakukan menggunakan kombinasi analisis keamanan mendalam via API dan Blackbox Testing fungsional UI menggunakan agen otomatis (*browser*). Seluruh modul inti berjalan dengan baik tanpa ada *Internal Server Error (500)* setelah dilakukan perbaikan.

## 2. Tabel Laporan Pengujian

### A. Autentikasi & Otorisasi (RBAC)

| No | Fitur yang di Uji | Skenario Pengujian | Input | Output yang diharapkan | Hasil |
|:---:|---|---|---|---|---|
| 1 | **Login & RBAC Admin** | Login menggunakan akun `superadmin` | Username: `admin123`, Pass: `admin123` | Berhasil masuk ke dashboard, dan dapat mengakses semua menu bypass role. | **PASSED** |
| 2 | **Validasi Form Login** | Mengisi form login dengan data *invalid* dan kosong. | Username asal & password salah / kosong. | Sistem menolak login dan menampilkan pesan "username or password is wrong". | **PASSED** |
| 3 | **Login Siswa & Reset Password** | Login sebagai siswa baru (NIS 000001) dan cek pemaksaan ganti password | NIS `000001` / pass: `13062015` | Berhasil login, sistem langsung memaksa ganti password pada login pertama. | **PASSED** |
| 4 | **Akses Admin Memaksa Masuk Portal Siswa** | Beralih ke akun Admin dan mengakses URL `/portal`. | Mengunjungi URL portal siswa sebagai Admin. | Sistem mencegat (intercept) menggunakan middleware permission dan me-redirect kembali ke `/dashboard-page`. | **PASSED (Diperbaiki kembali via CustomAuthentication)** |
| 5 | **Akses Siswa Memaksa Masuk Admin (Exhaustive)** | Beralih ke akun Siswa dan mengetik URL panel Admin di *address bar*. | Akses `/dashboard-page`, `/data-master-siswa`, `/transaksi-tagihan`, `/laporan-kas-harian`, `/setting`, `/role-management`, dll. | Seluruh endpoint API dan URL mengembalikan `403 Forbidden` secara penuh (karena terbentur pengecekan permissions spesifik di setiap page class). | **PASSED (Aman Total)** |
| 6 | **Manajemen Role & User (View)** | Membuka halaman manajemen user & role sebagai Superadmin. | Buka menu Manajemen Role / User / Akun Siswa. | Halaman dan daftar akun (tabel) termuat dengan sempurna tanpa error. Tombol pembaruan role terestriksi sesuai *permission*. | **PASSED** |
| 7 | **Admin Lupa Password** | Menguji form lupa sandi (`/login`). | Menginput `admin@example.com`. | Reset link terkirim ke Mailpit, dan password berhasil direset menjadi `admin1234`. | **PASSED (Email Received & Reset Success)** |
| 8 | **Siswa Lupa Password** | Pengecekan fitur lupa sandi di `/login`. | Menginput `siswa@example.com`. | Reset link terkirim ke Mailpit, dan password berhasil direset menjadi `siswa1234`. | **PASSED (Email Received & Reset Success)** |

### B. Dashboard & Pelaporan

| No | Fitur yang di Uji | Skenario Pengujian | Input | Output yang diharapkan | Hasil |
|:---:|---|---|---|---|---|
| 7 | **Dashboard Admin & Render Widget** | Verifikasi render widget & statistik di `/dashboard-page` | Akses langsung ke URL dashboard | Menampilkan Ringkasan Kas, Chart Pemasukan, dan Tagihan Jatuh Tempo dengan sempurna. | **PASSED** |
| 8 | **Dependensi Filter Tahun Ajaran** | Mengubah filter Tahun Ajaran di Dashboard untuk melihat efek ke modul lain. | Ganti opsi "Semua Periode" ke periode tertentu. | Angka berubah dinamis. Laporan *Kas Harian* dan *Rekap Bulanan* otomatis memfilter data sesuai periode. | **PASSED** |
| 9 | **Laporan Kas Harian & Rekap Bulanan** | Buka halaman laporan dan periksa pemuatan tabel data. | Klik menu laporan. | Tabel menampilkan rincian data kas dan rekap dengan benar. | **PASSED** |
| 10 | **Export Data PDF** | Export laporan Rekap Bulanan dan Kas Harian via UI | Klik "Export PDF" di halaman laporan | Sukses memulai proses export dan file berhasil di-generate via antrean *job queue*. | **PASSED** |
| 11 | **CRUD Tahun Ajaran** | Tambah, Edit, dan Set Aktif Tahun Ajaran | Input data "2025/2026", edit nama, klik "Aktifkan" | Berhasil dibuat. Kolom nama *readonly* saat edit. Validasi form kosong sukses bekerja. | **PASSED** |

### C. Keuangan & Transaksi (Tagihan, Midtrans, Pengeluaran)

| No | Fitur yang di Uji | Skenario Pengujian | Input | Output yang diharapkan | Hasil |
|:---:|---|---|---|---|---|
| 12 | **CRUD Jenis Tagihan & Tagihan** | Menambah Jenis Tagihan dan meng-assign Tagihan ke siswa. | Form kosong & form terisi. | Validasi input kosong sukses mencegah *submission*. Pembuatan Tagihan berhasil disimpan. | **PASSED** |
| 13 | **Pembayaran Offline** | Memproses pembayaran tunai/offline via panel admin di form tagihan siswa. | Klik "Bayar" pada tagihan siswa, isi metode dan pembayar. | Status tagihan berubah. Sistem memperbarui sisa tagihan dengan benar. | **PASSED** |
| 14 | **Portal Siswa & Checkout Midtrans UI** | Navigasi ke portal siswa dan mencoba klik "Bayar Online" pada tagihan | Klik Bayar -> Konfirmasi Channel Pembayaran | Redirect sukses ke *Midtrans Snap Modal* dan form nominal + biaya admin tampil dengan tepat. | **PASSED** |
| 15 | **Midtrans Sandbox Simulator (QRIS)** | Simulasi pembayaran QRIS online menggunakan simulator Midtrans di portal siswa. | *Checkout* via QRIS, kemudian set status SUCCESS. | Simulasi berhasil (Status PAID). Webhook dicatat. | **PASSED** |
| 16 | **Approval Pengeluaran** | Siklus *Pengeluaran Request* dari pengajuan hingga pencairan via UI | Buat Draft -> Submit -> Approve -> Disburse | Status berubah sesuai alur, UI menangani modal dengan lancar tanpa error. | **PASSED** |
| 17 | **Keamanan Payload (Partial Payment)** | Manipulasi nilai `amount_paid` pada API Checkout `/api/midtrans/transactions` | Mengirim JSON dengan `amount_paid` = `10000` | Backend menerima pembayaran cicilan secara proporsional. (Dikonfirmasi bahwa cicilan diperbolehkan secara bisnis). | **PASSED** |
| 18 | **Keamanan Payload (Batch/Keranjang)** | Manipulasi `amount` saat checkout banyak tagihan sekaligus di `/api/midtrans/transactions/batch` | Payload frontend diubah untuk memanipulasi total bayar | Backend menghitung ulang murni dari database (`sisa`), manipulasi diabaikan mutlak. | **PASSED** |

### D. Manajemen Data Master & Siswa

| No | Fitur yang di Uji | Skenario Pengujian | Input | Output yang diharapkan | Hasil |
|:---:|---|---|---|---|---|
| 19 | **Validasi Form Manajemen Siswa (Negative)** | Tes submit form Siswa dalam kondisi kosong total untuk jenjang MI, TK, dan KB. | Submit form tanpa isi data satupun. | Form Validation memunculkan error pesan merah pada semua field wajib tanpa menyebabkan error HTTP 500. | **PASSED (Diperbaiki)** |
| 20 | **CRUD Kelas & Kategori** | Menambah dan mengubah data Kelas dan Kategori Siswa. | Input nama kelas duplikat, form kosong. | Mencegah nama duplikat per jenjang dengan pesan error yang tepat. | **PASSED** |
| 21 | **Generate Akun Siswa (Otomatis)** | Database Seeder otomatis men-generate akun user untuk setiap siswa. | Menjalankan `db:seed`. | Akun Siswa (nis + tgl_lahir) otomatis terbentuk tanpa perlu trigger manual bulk. | **PASSED (Diperbaiki)** |

### E. Komunikasi & Notifikasi (Email)

| No | Fitur yang di Uji | Skenario Pengujian | Input | Output yang diharapkan | Hasil |
|:---:|---|---|---|---|---|
| 22 | **Email Pembuatan Tagihan Baru** | Pengecekan *Mailpit* setelah tagihan dibuat untuk siswa. | Sistem atau Admin membuat tagihan baru. | Email "Pemberitahuan Tagihan Baru" diterima di Mailpit. | **PASSED** |
| 23 | **Email Pengingat & Jatuh Tempo (Scheduler)** | Pengecekan *Mailpit* setelah menjalankan command scheduler harian. | Menjalankan `notifications:send-reminders`. | Email *Reminder* (Pengingat Tagihan) & *Overdue* (Tagihan Terlambat) sukses terkirim dan diterima (56 emails verified). | **PASSED** |
| 24 | **Email Kwitansi Pembayaran** | Pengecekan pengiriman Kwitansi otomatis pasca pembayaran Offline / Midtrans Lunas. | Pembayaran sukses di-record. | Email Kwitansi PDF seharusnya dikirim ke inbox. | **PASSED** (Telah divalidasi via queue:listen) |

## 3. Daftar Bug / Issue yang Telah Diperbaiki

Selama siklus pengujian, beberapa isu kritis telah ditemukan dan **semuanya telah diselesaikan**:

1. **Bug Seeder Akun Siswa:** Akun login `Siswa` (`000001` dkk) secara *default* sebelumnya tidak terbuat otomatis oleh `DatabaseSeeder`. **Status: FIXED** dengan mengintegrasikan `AkunSiswaService` ke dalam routine seeder.
2. **Pembatasan Portal Siswa untuk Admin:** Akun ber- *role* Admin awalnya dapat membuka `/portal`. **Status: FIXED** melalui proteksi otorisasi yang mengecek `PermissionHelper::has('view-dashboard')`.
3. **Behavior Halaman `/setting` untuk Siswa:** Terdapat celah *layout loading* sebelum ditolak. **Status: FIXED** dengan mengganti middleware *role-based* (anti-pattern) menjadi pengecekan *permission* fallback secara API, sehingga error HTTP 403 dikembalikan secara konsisten tanpa merender *layout*.
4. 🛑 **CRITICAL BUG (500 Error pada Form Siswa):** Terjadi ketika melakukan *negative testing* (submit kosong) karena ketiadaan `getOptionLabelUsing()` pada form relasi Ayah/Ibu/Wali. **Status: FIXED**. Form kini beroperasi lancar dan memunculkan *validation errors* selayaknya standar Filament.
5. **Pembaruan Granular Permissions:** Mengubah permission gabungan seperti `manage-tahun-ajaran` menjadi aksi spesifik (`create`, `update`, `delete`, `view`). **Status: FIXED & TESTED**. UI dan API berhasil diuji memisahkan masing-masing akses tanpa kendala `403` bagi Superadmin.

## 4. Evaluasi Arsitektural (Spatie Laravel-Permission)
- Sistem kini sepenuhnya bersih dari penggunaan *hardcoded role checking* (`role:admin`, `role:siswa`) pada *middleware* routing. 
- Pembatasan rute dan otorisasi kini di- *drive* murni menggunakan **Permissions** (`permission:view-tagihan-siswa`, `permission:view-dashboard`, dsb) yang sejalan dengan *best practices* fleksibilitas dan keamanan jangka panjang sesuai dokumentasi Spatie.

## 5. Pengujian Lanjutan (Email OTP & Bug Resolusi)
Pengujian lanjutan yang dilakukan secara sekuensial telah mengonfirmasi bahwa alur *Siswa First Login* (Verifikasi Email OTP) dan *Forgot Password* kini berfungsi normal:
1. **Verifikasi Email OTP Siswa**: 
   - Siswa pertama kali login (dengan parameter `must_change_password`) berhasil diarahkan secara otomatis ke halaman `/portal/change-password`.
   - Kode OTP sukses digenerate menggunakan algoritma aman (`random_int`), memiliki *Rate Limiter* (maksimal 3 kali request / 10 menit), dan tersimpan sementara di Cache.
   - Email Verifikasi yang memuat kode OTP dengan layout HTML *responsive* berhasil diterima di *Mailpit*.
   - Saat submit penggantian password, bug kritis di `ApiService` (yang sebelumnya menyebabkan *false-positive success* (200 OK via redirect login HTML) karena ketiadaan header `Accept: application/json`) telah diperbaiki. API kini merespons kesalahan *validation* secara langsung.
   - **Bug Strict-Type OTP**: Ditemukan bug di mana input OTP Filament mengirim integer, sedangkan Cache OTP adalah string, sehingga validasi strict (`!==`) gagal memvalidasi OTP. Telah diperbaiki dengan melakukan *casting string* secara eksplisit.
   - **Bug Swallowed Error**: Halaman penggantian sandi sebelumnya gagal memunculkan error dari API (seperti OTP kedaluwarsa) dan hanya menampilkan pesan *default*. Telah diperbaiki agar menampilkan error validasi yang tepat kepada user.
   - Tes berhasil: Aplikasi sukses menyimpan *password* & email ke database dengan label diverifikasi, dan siswa berhasil login kembali menggunakan password baru.
2. **Uji Lupa Password Admin & Siswa**: 
   - Notifikasi email dengan tautan *reset password* HTML lengkap berfungsi ganda baik untuk portal Admin dan Siswa.
   - Admin dan Siswa berhasil menggunakan tautan token tersebut untuk menyetel ulang *password*.
   - *Logic Routing Identifier*: Telah dikonfirmasi bahwa setelah admin/operator mendaftarkan emailnya, mekanisme login otomatis mewajibkan penggunaan *email* (username tak lagi aktif), sebuah perlindungan yang diimplementasikan di `IdentifierService`.
