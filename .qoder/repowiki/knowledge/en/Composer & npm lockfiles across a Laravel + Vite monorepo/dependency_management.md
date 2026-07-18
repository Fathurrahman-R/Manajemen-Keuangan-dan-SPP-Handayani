This monorepo manages third-party dependencies with two independent package managers — one per sub-project — and pins exact versions via lockfiles. There is no shared workspace tool (no root `composer.json`, no `pnpm`/`yarn` workspaces, no PHP vendoring). Each application declares its own dependency surface and ships a committed lockfile so CI can reproduce builds deterministically.

**PHP / Composer (backend + frontend-v2)**
- Two separate Laravel projects each own their `composer.json`:
  - `backend/composer.json` — API server; requires `laravel/framework ^12`, `spatie/laravel-permission ^7.4`, `midtrans/midtrans-php ^2.5`, `maatwebsite/excel ^3.1`, `barryvdh/laravel-dompdf ^3.1`, `dedoc/scramble ^0.13.5`, plus `laravel/sanctum`, `tinker`, `pail`, `pint`, `phpunit` in dev.
  - `frontend-v2/composer.json` — Filament v4 admin/portal; requires `filament/filament ^4.0`, `rappasoft/laravel-livewire-tables ^3.7`, `wire-elements/modal ^3.0`, `wireui/phosphoricons ^2.4`, `kristiansnts/filament-api-login ^1.0`, plus Pest (`pestphp/pest ^3.8`) and `giorgiosironi/eris *` for property-based tests.
- Both use `minimum-stability: stable` and `prefer-stable: true`, pinning to stable releases only.
- `composer.lock` is committed in both directories, locking every transitive dependency to an exact version hash.
- Composer scripts provide project bootstrap (`setup`, `dev`, `test`) and post-hooks that run framework-specific tasks (`artisan vendor:publish`, `artisan filament:upgrade`, `artisan boost:update`).
- No private repository or custom mirror is configured; packages are resolved from Packagist.
- The `vendor/` directory is gitignored in both projects.

**JavaScript / npm (frontend + frontend-v2 + portal-reference)**
- Three independent Node projects, each with its own `package.json` and lockfile:
  - `frontend/package.json` — React 18 + Vite 7 SPA; lockfile `frontend/package-lock.json`.
  - `frontend-v2/package.json` — Tailwind + Alpine + Vite assets consumed by the Filament app; lockfile `frontend-v2/package-lock.json`.
  - `portal-reference/handayani-joyful-portal/package.json` — TanStack Start reference site using Bun; lockfile `portal-reference/handayani-joyful-portal/bun.lock`.
- All three lockfiles are committed, ensuring reproducible installs.
- No shared `node_modules` or workspace configuration exists between the three frontends.
- The `node_modules/` directory is gitignored everywhere.

**Conventions observed**
- Per-project manifests: each subdirectory is self-contained; there is no cross-project dependency sharing at the package-manager level.
- Lockfiles are treated as source of truth and checked into version control.
- Development scripts orchestrate both PHP and JS install/build steps together (e.g. `composer setup` runs `npm install && npm run build`).
- Private registries, `GOPRIVATE`, or vendored PHP code are not used.