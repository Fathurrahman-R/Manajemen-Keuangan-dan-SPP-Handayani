# Panduan Penggunaan Aplikasi Handayani

Aplikasi Handayani adalah sistem administrasi keuangan sekolah untuk jenjang **KB, TK, dan MI**. Aplikasi menangani data siswa, penerbitan tagihan, pencatatan pembayaran, pengeluaran kas, dan laporan keuangan.

Aplikasi terdiri dari dua bagian:

| Bagian | Untuk siapa | Alamat |
|---|---|---|
| **Panel Admin** | Staf sekolah dan pengurus yayasan | alamat utama aplikasi |
| **Portal Siswa** | Siswa dan wali murid | alamat utama + `/portal` |

Anda tidak perlu memilih sendiri — setelah login, sistem otomatis mengarahkan Anda ke bagian yang sesuai dengan akun Anda.

---

## Anda harus baca panduan yang mana?

| Anda adalah | Baca panduan ini |
|---|---|
| Operator / admin sekolah — mengurus data siswa, tagihan, pembayaran, laporan harian | [Panduan Admin & Operator Sekolah](panduan-admin-operator.md) |
| Kepala Yayasan — mengawasi keuangan, menyetujui pengeluaran, mengelola akun staf dan cabang | [Panduan Kepala Yayasan](panduan-kepala-yayasan.md) |
| Siswa atau wali murid — melihat tagihan dan membayar | [Panduan Siswa & Wali Murid](panduan-siswa-wali.md) |
| Superadmin | Baca kedua panduan Admin dan Kepala Yayasan — akun Anda memiliki seluruh akses |

---

## Cara masuk (login)

Semua pengguna masuk lewat **halaman login yang sama**.

![Halaman login aplikasi](screenshot/01-login.png)

1. Buka alamat aplikasi di browser (Chrome, Edge, atau Firefox).
2. Isi kolom **Email / NIS**:
   - **Staf sekolah dan yayasan** → isi dengan **alamat email** Anda.
   - **Siswa dan wali murid** → isi dengan **NIS siswa**.
3. Isi kolom **Kata sandi**.
4. Centang **Ingat saya** bila Anda memakai perangkat pribadi (jangan dicentang di komputer bersama).
5. Klik tombol **Masuk**.

> **Password awal siswa adalah tanggal lahir siswa** dengan format hari-bulan-tahun tanpa tanda pemisah.
> Contoh: siswa lahir 7 Agustus 2015 → password awalnya `07082015`.

Anda hanya boleh salah memasukkan password **5 kali**. Setelah itu Anda harus menunggu beberapa saat sebelum bisa mencoba lagi.

---

## Wajib ganti password saat pertama kali masuk

Akun yang baru dibuat akan langsung diarahkan ke halaman **Ubah Password**. Anda tidak bisa memakai aplikasi sebelum langkah ini selesai.

Prosesnya dua tahap:

**Tahap 1 — Mendaftarkan dan memverifikasi email**

![Tahap verifikasi email pada halaman Ubah Password](screenshot/02-ubah-password-tahap-email.png)

1. Isi **Alamat Email** dengan email aktif Anda.
2. Klik **Kirim Kode OTP**. Sistem mengirim kode 6 angka ke email tersebut.
3. Buka email Anda, salin kodenya, lalu isikan ke kolom **Kode OTP Verifikasi**.
4. Klik **Verifikasi Email**.

Kalau kode tidak kunjung masuk, periksa folder **Spam**. Anda juga boleh mengganti alamat email di kolom yang sama lalu mengirim ulang kodenya.

**Tahap 2 — Mengganti password**

![Tahap ubah password](screenshot/03-ubah-password-tahap-password.png)

1. Isi **Password Saat Ini** (untuk siswa: tanggal lahir DDMMYYYY).
2. Isi **Password Baru** — minimal 8 karakter.
3. Ulangi di **Konfirmasi Password Baru**.
4. Klik **Ubah Password**.

> **Penting:** setelah password berhasil diubah, Anda akan **otomatis dikeluarkan** dan kembali ke halaman login. Ini normal, bukan kerusakan. Masuk kembali menggunakan password baru Anda.

---

## Lupa password

Fitur ini hanya bisa dipakai bila email Anda sudah terdaftar di sistem.

1. Di halaman login, klik tautan **Lupa kata sandi?** di sebelah kolom Kata sandi.
2. Isi alamat email Anda, lalu kirim.
3. Sistem selalu menampilkan pesan berhasil — **walaupun email Anda belum terdaftar**. Ini disengaja demi keamanan. Jadi bila dalam beberapa menit tidak ada email masuk, kemungkinan besar email tersebut memang belum terdaftar. Hubungi admin sekolah.
4. Buka email, klik tautan reset. **Tautan hanya berlaku 60 menit.**
5. Isi **Password Baru** (minimal 8 karakter) dan **Konfirmasi Password**, lalu simpan.
6. Masuk kembali dengan password baru.

Siswa yang belum punya email dapat meminta admin sekolah melakukan reset. Password akan dikembalikan ke tanggal lahir dan siswa mengulang proses ganti password di atas.

---

## Hal yang perlu diketahui semua pengguna

**Menu yang Anda lihat menyesuaikan hak akses akun.** Kalau panduan menyebut sebuah menu tetapi Anda tidak menemukannya, berarti akun Anda tidak diberi hak untuk itu. Hubungi Kepala Yayasan atau Superadmin.

**Sesi berakhir otomatis setelah 8 jam.** Anda perlu masuk kembali.

**Satu akun hanya bisa aktif di satu perangkat.** Bila akun yang sama dipakai masuk di perangkat lain, sesi di perangkat sebelumnya akan diputus. Jangan berbagi akun.

**Keluar dari aplikasi** lewat menu di pojok kanan atas (klik nama/foto Anda) → **Logout**. Selalu lakukan ini bila memakai komputer bersama.

---

## Butuh bantuan

Bila menemui masalah, buka bagian **Troubleshooting** di panduan masing-masing. Bagian tersebut memuat pesan-pesan kesalahan yang mungkin muncul beserta cara mengatasinya.
