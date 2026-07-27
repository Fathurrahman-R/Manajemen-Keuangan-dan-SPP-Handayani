# Setup & Menjalankan

Panduan setup manual (tanpa Docker). Untuk cara tercepat, lihat [Menjalankan dengan Docker](docker.md).

## Prasyarat

- PHP `^8.4`
- Composer
- Node.js & npm (untuk build asset frontend-v2, lihat `frontend-v2/package.json`)
- Database **MariaDB/MySQL** (default `.env.example`: `DB_CONNECTION=mariadb`)

> [!NOTE]
> Kedua aplikasi mengarah ke **satu database yang sama**. Hanya `backend` yang memiliki migrasi — jangan pernah menambahkan migrasi di `frontend-v2`.



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

> [!IMPORTANT]
> Backend **harus** dijalankan di port `8080`, bukan default `8000` — `frontend-v2/.env.example` sudah mengarah ke `http://127.0.0.1:8080/api`.

Konfigurasi opsional di `backend/.env` (lihat `backend/.env.example`):
- **Midtrans sandbox**: `HANDAYANI_MIDTRANS_ENABLED`, `MIDTRANS_ENVIRONMENT`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_MERCHANT_ID`, `HANDAYANI_MIDTRANS_FEE_FLAT`.
- **Mail**: `MAIL_MAILER` dan variabel SMTP terkait, dipakai untuk notifikasi email workflow approval pengeluaran.

## 3. frontend-v2 (Admin Panel & Portal)

```bash
cd frontend-v2
composer install
copy .env.example .env
php artisan key:generate
```

Pastikan `DB_DATABASE` di `.env` sama dengan yang dipakai `backend` (satu database bersama), dan API sudah berjalan di `http://127.0.0.1:8080/api`.

```bash
npm install
npm run build
php artisan serve
```

Konfigurasi opsional (public-safe) di `frontend-v2/.env`: `HANDAYANI_MIDTRANS_ENABLED`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_SNAP_URL`, `HANDAYANI_MIDTRANS_FEE_FLAT`.

## Konten halaman publik (landing page)

Seluruh teks dan konten landing page ada di **`frontend-v2/config/handayani-public.php`**, bukan hardcode di Blade. Section yang tersedia: `hero`, `about` (title/visi/misi/nilai_institusional), `jenjang`, `ekstrakurikuler`, `fasilitas`, `nav_links`, `spp_cta`, `branches`, `map_settings`.

Hanya field identitas yang bisa di-override lewat env `HANDAYANI_PUBLIC_*` (`name`, `short_name`, `tagline`, `address`, `phone`, `email`, `whatsapp_number`, `spp_portal_url`, `logo`, `colors.*`). Konten section **tidak** lewat env — edit langsung di config.

> **Gotcha:** beberapa key wajib berupa **array**, bukan string, karena dirender lewat `@foreach` di komponen Blade-nya:
> `about.misi` (list `<ul>` di `components/public/about.blade.php`), `nav_links`, `ekstrakurikuler.kegiatan`, `fasilitas.sarana`, `fasilitas.ruang_penunjang`, `jenjang.levels`, `hero.stats`.
> Mengisinya dengan string tunggal akan bikin halaman error.

