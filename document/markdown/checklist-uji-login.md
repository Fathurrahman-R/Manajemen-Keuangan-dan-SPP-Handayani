# Checklist Eksekusi Uji Login & Autentikasi

> **Kenapa berkas ini ada.** Laporan TA **sudah menuliskan** keenam kasus uji di bawah berstatus PASS (Tabel 3.40 baris TC-01..TC-06 dan Tabel 4.4), dan angka total sudah dinaikkan dari 102 menjadi **108 kasus uji**. Keenamnya **belum benar-benar dieksekusi**. Berkas ini dipakai untuk membuktikannya. Kalau ada yang FAIL, laporan wajib dikoreksi sebelum diserahkan.
>
> Rincian teknis tiap kasus (precondition, langkah, data uji) ada di `test-case-blackbox.md` bagian **Kelompok 9 — Autentikasi & Sesi Login**, kode TC-AUTH-001..006.

---

## Sebelum mulai

- [ ] Backend jalan di port **8080** — `php artisan serve --port=8080` (bukan 8000; `frontend-v2/.env` menunjuk ke `http://127.0.0.1:8080/api`)
- [ ] Frontend-v2 jalan — `php artisan serve`
- [ ] Basis data sudah ter-*seed* (`php artisan migrate --seed`) sehingga akun `developer`, `superadmin`, `admin_desa_kapur`, dan siswa NIS `000001` tersedia
- [ ] Siapkan satu akun siswa **baru** yang `must_change_password` masih `true` untuk TC-05 — jangan pakai akun yang kata sandinya sudah pernah diganti

Kata sandi bawaan admin: `!handayani123`. Kata sandi bawaan siswa: tanggal lahir format `DDMMYYYY`.

---

## Enam kasus uji

### TC-01 — Staf masuk dengan surel dan kata sandi benar

Masuk sebagai `developer@handayani.com` / `!handayani123`.

- [ ] Berhasil masuk dan mendarat di dashboard
- [ ] Menu yang tampil sesuai peran (bukan menu kosong, bukan menu penuh milik peran lain)

> Kalau ditanya penguji: token yang terbit membawa seluruh izin peran sebagai *abilities* dan berlaku 480 menit.

### TC-02 — Siswa masuk dengan nomor induk

Masuk ke `/portal/login` sebagai NIS `000001`.

- [ ] Berhasil masuk ke portal
- [ ] Daftar tagihan yang tampil **hanya** milik siswa tersebut

### TC-03 — Kata sandi salah

Identifier benar, kata sandi sengaja salah.

- [ ] Ditolak, tidak masuk ke sistem
- [ ] Pesan yang muncul: `Username/email atau kata sandi salah.`

### TC-04 — Akun tidak terdaftar

Coba identifier yang pasti tidak ada, misal `tidakada@handayani.com`.

- [ ] Ditolak
- [ ] Pesannya **sama persis** dengan TC-03 — ini yang diuji, bukan sekadar penolakannya

> Ini nilai jual saat sidang: pesan sengaja diseragamkan supaya penyerang tidak bisa menebak akun mana yang benar-benar ada. Bandingkan dengan akun **nonaktif**, yang pesannya justru berbeda (`Akun tidak aktif. Hubungi admin sekolah.`) karena kasusnya bukan tebakan penyerang, melainkan pengguna sah yang perlu tahu harus menghubungi siapa.

### TC-05 — Masuk pertama kali dengan kata sandi bawaan

Pakai akun siswa baru yang belum pernah ganti kata sandi.

- [ ] Setelah masuk, langsung diarahkan ke halaman ganti kata sandi
- [ ] Coba buka halaman portal lain sebelum mengganti — harus dilempar balik ke halaman ganti kata sandi
- [ ] Setelah kata sandi diganti, halaman portal bisa dibuka normal

### TC-06 — Sesi dicabut atau kedaluwarsa

Cara termudah memicu: masuk ke akun yang sama dari peramban kedua — sesi pertama akan tercabut.

- [ ] Sesi lama tidak bisa dipakai lagi
- [ ] Pengguna dikembalikan ke halaman masuk, bukan menampilkan halaman rusak atau data kosong

> Satu akun hanya boleh punya satu sesi aktif. Login baru mencabut seluruh token lama.

---

## Setelah selesai

- [ ] Semua enam PASS → tidak ada yang perlu diubah di Laporan TA
- [ ] Ada yang FAIL → catat mana dan gejalanya, lalu perbaiki di:
  - Tabel 4.4 (kolom Hasil Aktual & Status baris yang bersangkutan)
  - Tabel 4.3 baris **1. Autentikasi & Sesi Login** dan baris **Total**
  - Empat kalimat yang menyebut angka: Abstrak (Indonesia), Abstrak (Inggris), penutup 4.3, dan simpulan BAB V
- [ ] Perbarui rekap di `test-case-blackbox.md`: baris Kelompok 9 dan Total menjadi **109 TC / 108 Pass**
