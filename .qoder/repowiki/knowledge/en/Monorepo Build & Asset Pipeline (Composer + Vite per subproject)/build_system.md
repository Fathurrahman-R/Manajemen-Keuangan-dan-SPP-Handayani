This repository is a monorepo with four independently built subprojects. There is no top-level orchestrator, Dockerfile, or CI pipeline; each subproject owns its own dependency manager and asset build toolchain.

**Backend (`backend/`)** — Laravel 12 PHP application
- Dependency management: `composer.json` with `require` / `require-dev`, PSR-4 autoloading for `App\`, `Database\Factories\`, `Database\Seeders\`, and `Tests\`.
- Composer scripts provide the local dev workflow:
  - `composer setup` — installs deps, copies `.env.example`, generates app key, runs migrations, then `npm install && npm run build`.
  - `composer dev` — concurrently boots `php artisan serve`, `php artisan queue:listen --tries=1`, and `npm run dev` via `concurrently`.
  - `composer test` — clears config then runs `artisan test`.
  - Post-install/update hooks publish assets and discover packages.
- Asset build: Vite via `laravel-vite-plugin` with Tailwind (`@tailwindcss/vite`). Inputs are `resources/css/app.css` and `resources/js/app.js`. Development hot-reload enabled.
- Testing: PHPUnit 11 (`phpunit.xml`) plus Pest in `frontend-v2`; factories, seeders, and feature/unit tests live under `tests/`.

**Admin & Parent Portal (`frontend-v2/`)** — Laravel + Filament v4 + Livewire SPA
- Same Composer/Laravel bootstrap as backend but packaged as a separate Laravel project with its own `composer.json`, `artisan`, migrations, seeders, and routes.
- Frontend assets built by Vite (`laravel-vite-plugin`) with Tailwind v4 and an extra Filament theme CSS input. Production build splits vendor chunks, minifies with esbuild, and disables sourcemaps.
- Tests: Pest (`tests/Pest.php`) alongside PHPUnit-style classes.

**React Vite portal (`frontend/`)** — Standalone React + Vite app
- Independent `package.json` with `vite` dev server and `vite build` production step. Uses `@vitejs/plugin-react`, Tailwind v3, ESLint, and PostCSS/Autoprefixer.
- No integration with the Laravel backend at build time; it is a separate deployable SPA.

**Public reference site (`portal-reference/handayani-joyful-portal/`)** — TanStack Start (Lovable-generated)
- Built with Vite + TanStack Start via `@lovable.dev/vite-tanstack-config`. Scripts include `dev`, `build`, `build:dev`, `preview`, `lint`, and `format` (Prettier). Uses Bun lockfile (`bun.lock`) and TypeScript.

**Cross-cutting conventions**
- Each subproject is self-contained: it ships its own `composer.json` / `package.json`, `vite.config.*`, and entry points (`public/index.php` for Laravel apps).
- There is no shared root `Makefile`, `Dockerfile`, or GitHub Actions workflow in this branch (`.github/` is empty); orchestration is expected to be external.
- The only cross-project link is that `backend/composer.json`'s `setup` script also builds the legacy `frontend/` assets via `npm install && npm run build`, while `frontend-v2/` is built separately through its own Vite pipeline.