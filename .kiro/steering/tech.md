---
inclusion: always
---

# Tech Stack

The repository is a multi-workspace monorepo. Only two workspaces are in use.

## Workspaces

| Workspace      | Role                                          | Status   |
|----------------|-----------------------------------------------|----------|
| `backend/`     | Laravel 12 REST API (data, auth, business logic) | Active   |
| `frontend-v2/` | Laravel 12 + Filament 4 admin panel + portal  | Active — the only frontend |

All UI work goes into `frontend-v2`. The `frontend/` folder (React SPA) and `portal-reference/` folder are **not used**; do not modify them and do not propose changes there.

## Backend (`backend/`)

- **Language**: PHP `^8.2`
- **Framework**: Laravel `^12.0`
- **Auth**: Laravel Sanctum (`auth:sanctum`), token-based
- **Authorization**: `spatie/laravel-permission` `^7.4` — permission-based middleware on routes (`permission:view-dashboard`, `role:siswa`, custom `deny_siswa`, etc.)
- **API docs**: `dedoc/scramble`
- **Excel / CSV**: `maatwebsite/excel`
- **PDF**: `barryvdh/laravel-dompdf` (kwitansi, reports)
- **Testing**: PHPUnit `^11.5`, Mockery, Faker
- **Lint**: Laravel Pint
- **Frontend assets (admin panel only)**: Vite + Tailwind CSS v4 (used for Laravel's built-in resources)

### Structure highlights

- Controllers in `app/Http/Controllers` map 1:1 to domain entities (e.g., `TagihanController`, `PembayaranController`).
- Eloquent models in `app/Models` use Indonesian names matching the domain (`Siswa`, `Tagihan`, `TahunAjaran`).
- Permissions are centralized in `app/Constant/Permissions.php` and `app/Enum/Permission.php`.
- Events / Listeners / Jobs handle async work: `TagihanCreated`, `PembayaranRecorded`, `ProcessImportJob`, `ProcessExportJob`.
- API routes live in `routes/api.php`; almost every route is permission-gated.

## Frontend (`frontend-v2/`)

- **Framework**: Laravel `^12.0` + **Filament `^4.0`** (admin panel)
- **Livewire components**: `rappasoft/laravel-livewire-tables`, `wire-elements/modal`, `wireui/phosphoricons`
- **API login plugin**: `kristiansnts/filament-api-login` — frontend-v2 authenticates against `backend/` via API
- **Testing**: Pest `^3.8` + `pestphp/pest-plugin-laravel`, plus `giorgiosironi/eris` for property-based testing
- **Lint**: Pint
- **Assets**: Vite + Tailwind v4 (via `@tailwindcss/vite`), with a separate Filament theme entry (`resources/css/filament/admin/theme.css`)

## Halaman publik (landing/profil sekolah)

Ada satu lagi bagian frontend-v2 yang **bukan** Filament panel:
halaman publik/marketing yang tampil sebelum user login (profil sekolah,
landing page). Ini halaman pertama yang dilihat pengunjung, terpisah dari
admin panel maupun portal siswa/wali.

- **Routing**: route biasa di `routes/web.php`, BUKAN didaftarkan sebagai
  Filament Page atau Resource
- **Controller**: controller standar Laravel (mis. `HomeController` atau
  `PublicPageController`), bukan Filament Page class
- **View**: Blade view biasa di `resources/views/public/` (atau folder
  serupa di luar `resources/views/filament/`)
- **Layout**: TIDAK memakai layout Filament (`<x-filament-panels::page>`
  dkk). Pakai layout Blade custom sendiri.
- **Asset**: tetap pakai Vite + Tailwind v4 yang sudah ada, tapi dengan
  entry point terpisah dari theme Filament admin
  (`resources/css/filament/admin/theme.css` khusus Filament, jangan dipakai
  untuk halaman publik ini)
- **Tujuan**: konversi referensi UI dari Lovable (TypeScript + TanStack)
  ke Blade biasa, mempertahankan visual asli

## Sumber referensi UI — konversi dari Lovable

Halaman publik di atas dikonversi dari referensi desain yang dibuat di
Lovable (TypeScript + React + TanStack Query) yang berada di folder `portal-reference/<!--  -->`. Saat mengonversi:

- Pertahankan struktur layout dan visual asli semaksimal mungkin
- Ganti `useState` / `useQuery` (TanStack) → Alpine.js `x-data` / `x-show`
  / `x-on` untuk interaktivitas ringan (tidak perlu Livewire untuk halaman
  publik statis)
- Ganti komponen React (`<Card />`, `<Button />`, dst) → Blade component
  di `resources/views/components/public/` (prefix `public.` agar tidak
  campur dengan komponen Filament jika ada)
- Tailwind class ditulis langsung di template — TIDAK pakai `@apply`,
  konsisten dengan konvensi yang sudah dipakai di Filament theme
- Data yang sebelumnya fetch via TanStack Query (kalau ada konten dinamis
  seperti pengumuman/galeri) → ambil dari Eloquent model lewat controller,
  bukan AJAX, kecuali memang dibutuhkan interaktivitas client-side

## Batasan khusus halaman publik

- Jangan menaruh halaman publik di bawah `app/Filament/` sama sekali
- Jangan menambahkan permission/middleware Sanctum untuk halaman ini
  (ini halaman publik, bukan area yang butuh auth)
- Jangan reuse Filament Form/Table components untuk bagian publik ini —
  styling Filament tidak cocok untuk landing page marketing
- Folder `frontend/` (React SPA) dan `portal-reference/` tetap tidak
  boleh disentuh

### Two Filament panels

- **Admin panel** — pages in `app/Filament/Pages/` (data master, transaksi, laporan, settings, RBAC)
- **Portal (siswa/wali)** — pages in `app/Filament/Portal/Pages/` (Beranda, Tagihan, Riwayat Pembayaran, Profil), routed under `HANDAYANI_PORTAL_PATH` (default `/portal`)

### Feature flags

Central config: `frontend-v2/config/handayani.php`. Toggle features via `.env`:

- `HANDAYANI_PORTAL_ENABLED` — enable/disable the siswa/wali portal
- `HANDAYANI_CUSTOM_NAVIGATION_ENABLED` — toggle the 4-group sidebar
- `HANDAYANI_PROFILE_MIGRATION_ENABLED` — Filament `EditProfile` page
- `HANDAYANI_SPA_LOADING_ENABLED` — SPA transitions / prefetching
- `HANDAYANI_PORTAL_PATH` — portal URL prefix

When introducing a new toggleable feature, follow this pattern.

## Common commands

Run from the workspace root indicated.

### Backend (`backend/`)

```cmd
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000
php artisan queue:listen --queue=notifications,default --tries=1
php artisan schedule:work    :: jalankan scheduler — wajib aktif untuk reminder & overdue email
composer dev          :: runs server + queue + vite concurrently
composer test         :: clears config and runs PHPUnit
.\vendor\bin\pint     :: lint
```

### Frontend (`frontend-v2/`)

```cmd
composer install
npm install
copy .env.example .env
php artisan key:generate
:: Tidak ada migrasi di frontend-v2 — DB di-manage backend. Pastikan
:: .env DB_DATABASE menunjuk ke database backend yang sama supaya
:: Filament dapat membaca tabel `users`, `sessions`, dan `filament_notifications`.
npm run dev           :: vite dev server
npm run build         :: production build
php artisan serve --port=8080
composer dev          :: server + queue + pail + vite concurrently
composer test         :: pest
.\vendor\bin\pest     :: tests directly
```

## Conventions

- Use `php artisan` commands rather than touching files manually for migrations, models, factories, seeders.
- Prefer **dedicated Eloquent relationships and resource classes** (`app/Http/Resources`) over ad-hoc array shaping.
- Permission-gate every new admin API route. Use `role:siswa` / `deny_siswa` for portal vs admin separation.
- New Filament pages should match the existing pattern (single class per page under `app/Filament/Pages/` or `app/Filament/Portal/Pages/`).
- Run `composer test` (or `pest`) before declaring a backend or frontend-v2 change complete.
