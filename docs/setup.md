# Setup & Menjalankan

Setup manual, tanpa Docker. Cara cepatnya ada di [Menjalankan dengan Docker](docker.md).

## Prasyarat

- PHP `^8.4`
- Composer
- Node.js & npm, buat build asset frontend-v2
- MariaDB/MySQL (default `.env.example`: `DB_CONNECTION=mariadb`)

Dua aplikasi ini nunjuk database yang sama. Cuma `backend` yang punya migrasi, jangan bikin migrasi di `frontend-v2`.

## 1. Clone repository

```bash
git clone <url-repo> handayani
cd handayani
```

## 2. Backend (API)

```bash
cd backend
composer install
copy .env.example .env        # atau `cp .env.example .env` di bash
php artisan key:generate
```

Sesuaikan kredensial database di `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), lalu:

```bash
php artisan migrate --seed
php artisan serve --port=8080
```

Port 8080-nya wajib, bukan default 8000. `frontend-v2/.env.example` sudah nunjuk `http://127.0.0.1:8080/api`.

### Akun hasil seeder

`migrate --seed` bikin akun berikut. Password semuanya sama: `!handayani123`.

| Login pakai | Role | Cabang |
|---|---|---|
| `superadmin@handayani.com` | superadmin | Selat Panjang |
| `developer@handayani.com` | developer | Selat Panjang |
| `yayasan@handayani.com` | kepala-yayasan | Selat Panjang |
| `admin_selat_panjang` | admin | Selat Panjang |
| `admin_desa_kapur` | admin | Desa Kapur |
| `admin_darma_putra` | admin | Darma Putra |

Tiga akun pertama **harus login pakai email, bukan username**. `IdentifierService` sengaja matiin login-by-username buat user non-siswa yang punya email, jadi `superadmin` bakal ditolak 401 padahal passwordnya benar. Admin cabang tidak diseed dengan email, jadi mereka justru pakai username.

Seeder tidak bikin akun siswa. Akun portal siswa dibuat manual lewat UI admin.

Kredensial ini cuma buat development. Jangan jalanin `UserSeeder` di production, passwordnya ada di repo dan kebaca siapa saja.

Buat nyalain semua service dev sekaligus (serve, queue listener, Vite):

```bash
composer run dev
```

Config opsional di `backend/.env`:

- Midtrans sandbox: `HANDAYANI_MIDTRANS_ENABLED`, `MIDTRANS_ENVIRONMENT`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_MERCHANT_ID`. Detailnya di [Setup Midtrans](midtrans.md).
- Mail: `MAIL_MAILER` dan variabel SMTP, dipakai notifikasi email workflow approval.
- `FRONTEND_URL`: dipakai link reset password dan redirect habis bayar. Wajib diganti waktu deploy.

## 3. frontend-v2 (Admin Panel & Portal)

```bash
cd frontend-v2
composer install
copy .env.example .env
php artisan key:generate
```

Pastikan `DB_DATABASE` sama dengan punya `backend`, dan API sudah jalan di `http://127.0.0.1:8080/api`.

```bash
npm install
npm run build
php artisan serve
```

Config opsional (public-safe): `HANDAYANI_MIDTRANS_ENABLED`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_SNAP_URL`, `HANDAYANI_MIDTRANS_FEE_FLAT`.

## Konten halaman publik

Semua teks landing page ada di `frontend-v2/config/handayani-public.php`, bukan hardcode di Blade. Section yang ada: `hero`, `about` (title/visi/misi/nilai_institusional), `jenjang`, `ekstrakurikuler`, `fasilitas`, `nav_links`, `spp_cta`, `branches`, `map_settings`.

Cuma field identitas yang bisa di-override lewat env `HANDAYANI_PUBLIC_*`: `name`, `short_name`, `tagline`, `address`, `phone`, `email`, `whatsapp_number`, `spp_portal_url`, `logo`, `colors.*`. Konten section tidak lewat env, edit langsung di file confignya.

Beberapa key harus array karena dirender pakai `@foreach`: `about.misi`, `nav_links`, `ekstrakurikuler.kegiatan`, `fasilitas.sarana`, `fasilitas.ruang_penunjang`, `jenjang.levels`, `hero.stats`. Diisi string tunggal = halaman error.
