---
inclusion: always
---

# Project Structure

```
handayani/
├── backend/          Laravel 12 REST API (active)
├── frontend-v2/      Laravel 12 + Filament 4 admin & portal (active — the only frontend)
├── frontend/         (NOT USED — legacy React SPA, ignore)
├── portal-reference/ (NOT USED — reference materials, ignore)
├── .kiro/            Spec and steering files for AI workflows
└── *.md              Top-level docs (config_documentation.md, ISSUE_REPORT.md, TESTING_GUIDE.md)
```

**Important**: When the user mentions "frontend", they mean `frontend-v2/`. Do not read, modify, or reference `frontend/` or `portal-reference/` unless the user explicitly names that folder.

## `backend/` (Laravel API)

```
app/
├── Console/Commands/       Artisan commands
├── Constant/               PermissionBinding.php, Permissions.php — centralized constants
├── DTOs/ImportExport/      Import/export DTOs
├── Enum/                   DefaultRoles, Permission enums
├── Events/                 TagihanCreated, PembayaranRecorded
├── Exports/                Maatwebsite Excel exporters (Siswa, Tagihan, KasHarian, RekapBulanan, ...)
├── Helpers/                NotificationHelper
├── Http/
│   ├── Controllers/        One controller per domain entity (TagihanController, PembayaranController, ...)
│   │   └── Traits/         Shared controller traits
│   ├── Middleware/         Custom middleware (deny_siswa, role checks)
│   ├── Requests/           Form request validators
│   └── Resources/          API resource transformers
├── Imports/                Maatwebsite Excel importers + validators
├── Jobs/                   ProcessImportJob, ProcessExportJob
├── Listeners/              SendKwitansiNotification, SendTagihanBaruNotification
└── Models/                 Eloquent models with Indonesian domain names
routes/
├── api.php                 All API routes (Sanctum-protected, permission-gated)
├── web.php
└── console.php
database/                   migrations, seeders, factories
config/
tests/
├── Feature/
└── Unit/
```

### Naming rules

- **Models, controllers, jobs**: PascalCase using Indonesian domain terms (`Tagihan`, `Pembayaran`, `KenaikanKelasController`).
- **API routes**: kebab-case URLs (`/forgot-password`, `/tagihan/siswa`), grouped by entity prefix (`/users`, `/roles`, `/dashboard`).
- **Permissions**: kebab-case strings registered through `Constant\Permissions` and `Enum\Permission`.

## `frontend-v2/` (Laravel + Filament — the only frontend)

```
app/
├── Config/                 Domain-specific config helpers
├── Filament/
│   ├── Concerns/           Shared traits for pages
│   ├── Pages/              Admin panel pages (DashboardPage, TransaksiTagihan, KenaikanKelasPage, ...)
│   │   └── Auth/           Login, ChangePassword, EditProfile
│   ├── Portal/Pages/       Siswa/wali portal pages (PortalBerandaPage, PortalTagihanPage, ...)
│   └── Widgets/            Dashboard widgets
├── Helpers/
├── Http/
├── Livewire/               Livewire components (modals, custom tables)
├── Models/                 Local Eloquent models (mirroring backend where needed)
├── Providers/
│   ├── AppServiceProvider.php
│   └── Filament/           Panel providers (admin panel, portal panel)
└── Services/               API clients to backend, business logic helpers
config/handayani.php        Central feature-toggle config (see tech.md)
resources/
├── css/
│   ├── app.css
│   └── filament/admin/theme.css
├── js/app.js
└── views/                  Blade views, Livewire templates
routes/
└── web.php
tests/                      Pest tests (Feature/, Unit/)
```

### Two-panel pattern

- **Admin panel** lives under `app/Filament/Pages/` and uses the standard Filament navigation.
- **Portal** lives under `app/Filament/Portal/Pages/`, served at the path defined by `HANDAYANI_PORTAL_PATH`. Always check `HANDAYANI_PORTAL_ENABLED` when adding portal routes.
- Place new admin features in `Filament/Pages/`; new siswa/wali features in `Filament/Portal/Pages/`.

### Authentication

`frontend-v2` uses `kristiansnts/filament-api-login` to authenticate against `backend/`. Tokens are issued by `backend/` via Sanctum. Do not duplicate user storage in `frontend-v2`.

## `.kiro/` (Spec workflow)

```
.kiro/
├── specs/                  Per-feature specs (kebab-case folders): requirements.md, design.md, tasks.md, .config.kiro
└── steering/               Always-included guidance for AI assistants (this folder)
```

Existing specs cover the active feature work: `approval-workflow-pengeluaran`, `auto-create-akun-siswa`, `dashboard`, `email-notifications`, `frontend-polish-phase3-4`, `frontend-redesign`, `import-export-data`, `kenaikan-kelas-kelulusan`, `periode-tahun-ajaran`, `rbac-improvement`, `tagihan-card-view`, `ubah-username-ke-email`. Reuse the same kebab-case naming for new specs.

## Cross-cutting rules

- **Two-workspace rule.** All work happens in `backend/` (API) or `frontend-v2/` (UI). Ignore `frontend/` and `portal-reference/`.
- **Indonesian domain names** are intentional. Do not anglicize entity names like Tagihan, Pembayaran, Kelas, Wali.
- **Backend is the source of truth** for data and permissions. `frontend-v2` consumes the API and must not duplicate business rules.
- **Permission-gate everything.** Any new admin endpoint or Filament page should check the corresponding permission/role.
- **Feature flags first.** Significant rollouts go through `config/handayani.php` so they can be toggled without redeploys.
- **Tests live next to their workspace.** PHPUnit in `backend/tests`, Pest in `frontend-v2/tests`. Run them before finishing a change.
