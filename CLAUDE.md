# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository layout

Monorepo with two independent Laravel apps:

- **backend/** — headless Laravel 12 API. Owns the database schema, migrations, models, business rules, and Sanctum authentication.
- **frontend-v2/** — Laravel 12 + Filament 4 + Livewire 3 admin/portal UI. No migrations of its own; it calls `backend` through `ApiService` and stores the Sanctum token in session.

Both apps point at one shared database. `backend` alone owns migrations — never add migrations under `frontend-v2`.

## Commands

Test frameworks differ per app:
- **backend**: PHPUnit — `php artisan test`, or a single test with `vendor/bin/phpunit tests/Feature/SomeTest.php` / `php artisan test --filter=SomeTest`.
- **frontend-v2**: Pest — `php artisan test`, or a single test with `vendor/bin/pest tests/Unit/BrandingConfigTest.php`.

Format both with `vendor/bin/pint`. No CI is configured — tests must be run locally.

Backend-specific:
- `php artisan permissions:sync` (add `--prune` to also remove stale ones) — run after adding or renaming an `App\Enum\Permission` case, otherwise `superadmin`'s `Gate::before` bypass still lacks the permission row Spatie's middleware checks.
- `php artisan midtrans:prune-logs --days=180` — prune Midtrans transaction logs.

## Local dev gotcha

`frontend-v2/.env` expects the API at `http://127.0.0.1:8080/api` — run the backend with `php artisan serve --port=8080`, not Laravel's default 8000.

## Domain gotchas

- Permission names are Indonesian strings (e.g. `view-tagihan`, `create-pengeluaran-request`). Frontend's `PermissionHelper` checks exact names from the session.
- `Tagihan` (bills) ↔ `Siswa` (students) join on `nis`, not `siswa.id`. Changing a student's NIS can break bill linkage.
- The Midtrans webhook (`POST /api/midtrans/notification`) is intentionally public with no auth middleware — don't "fix" that.

## Commit style

Commit messages are written in Indonesian as free-form descriptive sentences — no conventional-commit prefixes (`feat:`, `fix:`, etc).

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
