# AGENTS.md

This file provides guidance to Qoder (qoder.com) when working with code in this repository.

## Repository layout

The workspace is a monorepo with three independent applications:

| Directory | Purpose |
| --- | --- |
| [backend](file:///d:/First%20Project/handayani/backend) | Headless Laravel 12 API. Owns the database schema, migrations, models, business rules, Sanctum authentication, and JSON API consumed by `frontend-v2`. |
| [frontend-v2](file:///d:/First%20Project/handayani/frontend-v2) | Laravel 12 + Filament 4 admin/portal UI. No migrations of its own; it calls `backend` through `ApiService` and stores the Sanctum token in session. |
| [portal-reference/handayani-joyful-portal](file:///d:/First%20Project/handayani/portal-reference/handayani-joyful-portal) | Standalone React + TanStack Start landing/profile reference built with Lovable. See its own [AGENTS.md](file:///d:/First%20Project/handayani/portal-reference/handayani-joyful-portal/AGENTS.md) for Lovable-specific rules. |
| [.kiro](file:///d:/First%20Project/handayani/.kiro) | Spec and steering files for AI workflows. Includes the active public-portal spec at `.kiro/specs/profil-portal-publik`. |
| [ISSUE_REPORT.md](file:///d:/First%20Project/handayani/ISSUE_REPORT.md) | Indonesian-language bug/change tracker. Check this before starting any task; mark items `[x]` only after they are actually fixed. |

## Technology stack

- **Backend:** PHP 8.2+, Laravel 12, Sanctum, Spatie `laravel-permission`, Scramble API docs, Maatwebsite Excel, DOMPDF, Midtrans PHP SDK.
- **Frontend-v2:** PHP 8.2+, Laravel 12, Filament 4, Livewire 3, `rappasoft/laravel-livewire-tables`, Tailwind CSS 4, Vite.
- **Portal-reference:** React 19, TypeScript, TanStack Router/Start, Vite, Tailwind CSS 4, Radix UI, shadcn-style components.

## Common commands

All commands assume you are inside the relevant application directory (`backend`, `frontend-v2`, or `portal-reference/handayani-joyful-portal`).

### backend

```powershell
# Install dependencies
composer install
npm install

# Copy environment and generate key
copy .env.example .env
php artisan key:generate

# Run migrations and seeders (source of truth for the shared database)
php artisan migrate
php artisan db:seed

# Run API server. The frontend-v2 .env expects the API on http://127.0.0.1:8080/api,
# so the backend must be started on port 8080:
php artisan serve --port=8080

# Run queue worker (needed for mail, exports, imports, notifications)
php artisan queue:listen --tries=1

# Run tests
php artisan test
# or a single test
vendor/bin/phpunit tests/Feature/SomeTest.php
# or with filter
php artisan test --filter=SomeTest

# Format code
vendor/bin/pint

# Sync permissions after adding a new App\Enum\Permission case
php artisan permissions:sync
# also prunes stale permissions:
php artisan permissions:sync --prune

# Scheduled reminders (intended for Laravel scheduler)
php artisan notifications:send-reminders

# Prune Midtrans transaction logs
php artisan midtrans:prune-logs --days=180
```

### frontend-v2

```powershell
# Install dependencies
composer install
npm install

# Copy environment and generate key
copy .env.example .env
php artisan key:generate

# Build assets
npm run build

# Development server (Filament UI; default http://127.0.0.1:8000)
composer dev
# or run individually
php artisan serve
npm run dev

# Run tests
php artisan test
# or a single Pest test
vendor/bin/pest tests/Unit/BrandingConfigTest.php

# Format code
vendor/bin/pint
```

### portal-reference/handayani-joyful-portal

```powershell
# Install dependencies (uses bun in the Lovable workflow; npm also works)
bun install
# or
npm install

# Development server
bun run dev
# or
npm run dev

# Build
bun run build
# or
npm run build

# Lint / format
bun run lint
bun run format
# or
npm run lint
npm run format
```

## Local development workflow

1. Start a MariaDB/MySQL server and create a single database (e.g. `handayani`). Both `backend` and `frontend-v2` point at the same database; `backend` owns the migrations.
2. In `backend/.env`, set `DB_DATABASE=handayani` and run `php artisan migrate --seed`.
3. In `frontend-v2/.env`, set `DB_DATABASE=handayani` to the same value and `API_URL=http://127.0.0.1:8080/api`.
4. Start the backend on port `8080` (`php artisan serve --port=8080`) and the queue worker.
5. Start `frontend-v2` on port `8000` (`php artisan serve`).
6. Default admin credentials from [DatabaseSeeder](file:///d:/First%20Project/handayani/backend/database/seeders/DatabaseSeeder.php): `admin123` / `admin123`.

## High-level architecture

### 1. Split of responsibilities

- **backend** is the only app that talks to the database. It exposes a JSON API under `/api` (see [routes/api.php](file:///d:/First%20Project/handayani/backend/routes/api.php)).
- **frontend-v2** is essentially a stateless Filament UI. It authenticates by posting credentials to `backend`, then stores the returned Sanctum token in `session('data.token')` and forwards it on every request through [ApiService::client()](file:///d:/First%20Project/handayani/frontend-v2/app/Services/ApiService.php).
- **portal-reference** is a UI reference only. It is a Lovable-built React/TanStack site and is not wired to the API. The official plan is to convert its design into a public landing page in `frontend-v2` as Blade + Tailwind CSS v4 + Alpine.js, per `.kiro/specs/profil-portal-publik`. Do not modify files inside `portal-reference/`.

### 2. Public landing page (profil portal publik)

- Lives in `frontend-v2` but is **not** a Filament page. Use a standard Laravel controller, `routes/web.php`, and Blade views under `resources/views/public/`.
- Uses its own Vite entry points (`resources/css/public.css`, `resources/js/public.js`) and its own base layout (`resources/views/layouts/public.blade.php`).
- Interactivity is handled by Alpine.js; no Livewire and no Filament components on the public page.
- Configuration is centralized in `config/handayani-public.php` (mirrors `portal-reference/.../src/config/site.ts`).

### 3. Authentication

- Login logic: [backend/app/Http/Controllers/AuthController.php](file:///d:/First%20Project/handayani/backend/app/Http/Controllers/AuthController.php) and [frontend-v2/app/Filament/Pages/Auth/Login.php](file:///d:/First%20Project/handayani/frontend-v2/app/Filament/Pages/Auth/Login.php).
- `IdentifierService` in the backend routes `email` for admins and `username`/NIS for students. Admins with an email can no longer log in with their username.
- Tokens are Sanctum tokens with abilities equal to the user's permissions. The `CustomAuthentication` middleware in frontend-v2 redirects to login when the session token is missing.
- Siswa users are redirected to `/portal` after login; admins land on `/dashboard-page`.

### 4. Authorization

- Permissions are defined as string-backed enum cases in [backend/app/Enum/Permission.php](file:///d:/First%20Project/handayani/backend/app/Enum/Permission.php) and mirrored for grouping in [backend/app/Constant/Permissions.php](file:///d:/First%20Project/handayani/backend/app/Constant/Permissions.php).
- Roles: `superadmin`, `admin`, `user`, `siswa` ([backend/app/Enum/DefaultRoles.php](file:///d:/First%20Project/handayani/backend/app/Enum/DefaultRoles.php)).
- `superadmin` bypasses all gates via `Gate::before` in [backend/app/Providers/AppServiceProvider.php](file:///d:/First%20Project/handayani/backend/app/Providers/AppServiceProvider.php).
- Frontend navigation visibility is driven by [frontend-v2/app/Helpers/PermissionHelper.php](file:///d:/First%20Project/handayani/frontend-v2/app/Helpers/PermissionHelper.php), which reads permissions from the session.
- After adding or renaming a permission, run `php artisan permissions:sync` in `backend`.

### 5. Multi-branch / branding

- Most tables have a `branch_id`. A default branch is seeded in [DatabaseSeeder](file:///d:/First%20Project/handayani/backend/database/seeders/DatabaseSeeder.php).
- `AppSetting` stores per-branch school profile and logo; [BrandingService](file:///d:/First%20Project/handayani/frontend-v2/app/Services/BrandingService.php) loads it into the Filament panel.

### 6. Academic domain

- `TahunAjaran` is the central academic period. Most financial/academic data is scoped to a `tahun_ajaran_id`.
- `SiswaKelas` records class history per period.
- Jenjang options are `KB`, `TK`, `MI` ([frontend-v2/app/Config/NavigationConfig.php](file:///d:/First%20Project/handayani/frontend-v2/app/Config/NavigationConfig.php)).
- Student promotion/graduation is handled in [backend/app/Services/KenaikanKelasService.php](file:///d:/First%20Project/handayani/backend/app/Services/KenaikanKelasService.php) and exposed through [KenaikanKelasController](file:///d:/First%20Project/handayani/backend/app/Http/Controllers/KenaikanKelasController.php).

### 7. Financial domain

- `Tagihan` (bills) are keyed by `kode_tagihan` and linked to students by `nis`, not by `siswa.id`. `Siswa.tagihan()` uses `hasMany(Tagihan::class, 'nis', 'nis')`.
- `Pembayaran` records payments against a `kode_tagihan`.
- `PengeluaranRequest` implements an approval workflow (create → submit → approve/reject → disburse). `Pengeluaran` records disbursed amounts.
- Online payments go through Midtrans Snap. Config lives in [backend/config/midtrans.php](file:///d:/First%20Project/handayani/backend/config/midtrans.php) and [frontend-v2/config/handayani.php](file:///d:/First%20Project/handayani/frontend-v2/config/handayani.php). The webhook endpoint is public: `POST /api/midtrans/notification`.

### 8. frontend-v2 UI structure

- Admin panel provider: [frontend-v2/app/Providers/Filament/AdminPanelProvider.php](file:///d:/First%20Project/handayani/frontend-v2/app/Providers/Filament/AdminPanelProvider.php).
- Portal panel provider: [frontend-v2/app/Providers/Filament/PortalPanelProvider.php](file:///d:/First%20Project/handayani/frontend-v2/app/Providers/Filament/PortalPanelProvider.php).
- Most pages are custom Filament Pages in [frontend-v2/app/Filament/Pages](file:///d:/First%20Project/handayani/frontend-v2/app/Filament/Pages) and [frontend-v2/app/Filament/Portal/Pages](file:///d:/First%20Project/handayani/frontend-v2/app/Filament/Portal/Pages), backed by Livewire components in [frontend-v2/app/Livewire](file:///d:/First%20Project/handayani/frontend-v2/app/Livewire).
- Admin sidebar navigation is built manually in `AdminPanelProvider` with groups: Akademik, Keuangan, Laporan, Pengaturan. Siswa/wali users see a top-navigation portal at `/portal`.

### 9. Reports, imports, exports, PDFs

- `KasHarian` and `RekapBulanan` endpoints live in [backend/app/Http/Controllers/KasController.php](file:///d:/First%20Project/handayani/backend/app/Http/Controllers/KasController.php).
- PDF generation is in [backend/app/Http/Controllers/PdfGeneratorController.php](file:///d:/First%20Project/handayani/backend/app/Http/Controllers/PdfGeneratorController.php).
- Import/export jobs are queued via [backend/app/Jobs](file:///d:/First%20Project/handayani/backend/app/Jobs).

## Important conventions and gotchas

- **Shared database:** Do not create migrations in `frontend-v2`. All schema changes belong in `backend/database/migrations`.
- **Port expectation:** `frontend-v2/.env` points `API_URL` to `http://127.0.0.1:8080/api`. Run the backend on port `8080`, not the default `8000`.
- **Permission language:** Permission names are Indonesian strings (e.g. `view-tagihan`, `create-pengeluaran-request`). The frontend's `PermissionHelper` checks exact names from the session.
- **NIS relations:** `Tagihan` ↔ `Siswa` is joined on `nis`. Changing student NIS can break bill linkage.
- **Superadmin bypass:** `Gate::before` grants `superadmin` everything, but Spatie `permission:foo` middleware still checks the database, so `superadmin` must also have the permissions synced (`permissions:sync` handles this).
- **Feature toggles:** `config/handayani.php` controls the portal, custom navigation, SPA loading, and Midtrans. Keep feature flags in sync between `.env` values and the cached config.
- **Issue tracker:** [ISSUE_REPORT.md](file:///d:/First%20Project/handayani/ISSUE_REPORT.md) is the source of truth for outstanding bugs. Read it before changing financial, navigation, portal, or Midtrans code.
- **Lovable portal:** The `portal-reference` project is connected to Lovable. Do not force-push or rewrite its published git history.
- **portal-reference is read-only:** Use it only as a visual reference for the public landing page. Convert its React/Tailwind design into `frontend-v2` Blade + Tailwind v4 + Alpine.js per `.kiro/specs/profil-portal-publik`; do not edit `portal-reference/` files.
- **Spec vs. code state:** Some tasks in `.kiro/specs/profil-portal-publik/tasks.md` are marked complete but the corresponding files may not exist yet. Treat the spec as the plan and verify actual file state before assuming a task is done.
