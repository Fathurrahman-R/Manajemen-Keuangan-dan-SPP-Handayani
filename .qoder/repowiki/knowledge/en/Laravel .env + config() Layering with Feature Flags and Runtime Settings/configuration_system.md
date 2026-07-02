The monorepo uses a standard Laravel configuration system across both backend/ and frontend-v2/ (Filament admin) applications, layered in three tiers:

1. .env / .env.example — per-environment secrets and toggles. Both apps ship identical boilerplate (APP_*, DB_*, REDIS_*, MAIL_*, AWS_*) plus domain-specific keys prefixed HANDAYANI_MIDTRANS_*, MIDTRANS_*, HANDAYANI_PORTAL_*, HANDAYANI_CUSTOM_NAVIGATION_ENABLED, etc. The frontend-v2 .env.example explicitly documents that its DB is shared with the backend and it has no migrations of its own.

2. config/*.php files — typed defaults and grouping. Each app ships the standard Laravel set (app.php, database.php, services.php, mail.php, queue.php, session.php, cache.php, logging.php, filesystems.php). Domain-specific configs are split by concern:
   - backend/config/midtrans.php — payment gateway toggle, environment, credentials, per-channel fee tables, min amount, expiry, order prefix, finish URL, log retention.
   - frontend-v2/config/handayani.php — feature flags under features.* (portal enabled, custom navigation, profile migration, SPA loading, midtrans), portal path/breadcrumbs, Midtrans client-side settings (fee flat, min amount, snap URL, client key).

3. Runtime / database-backed settings — accessed via the API rather than static config. Branding (logo, primary color, favicon, branch name) is fetched from /app-settings/branding and cached in the Filament session through App\Services\BrandingService -> BrandingConfig. A AppSetting model exists in the backend with corresponding migrations, seeders, factories, controllers, resources, and Livewire pages for editing.

Access patterns
- Static config is read exclusively through config('key') (e.g. config('midtrans.enabled'), config('handayani.features.midtrans_enabled'), config('app.name'), config('app.timezone')). No direct $_ENV or getenv() calls were found in application code.
- Feature flags gate UI visibility (shouldRegisterNavigation, canAccess) and service behavior (throw MidtransDisabledException when disabled).
- Secrets (server_key, merchant_id) never reach HTTP responses; only client_key is exposed to the frontend via config.

Conventions observed
- All new runtime knobs go into a dedicated config/<domain>.php file with an env('VAR', default) fallback.
- Per-channel fees use a nested array keyed by channel name, each overridable via HANDAYANI_MIDTRANS_FEE_<CHANNEL>_PERCENT / _FLAT env vars.
- Environment-only values (server keys) stay out of .env.example; public-safe values (client key, snap URL, fee flat) are documented there.
- Database-driven settings are modeled as Eloquent models with migrations, seeders, factories, API resources, and Livewire edit pages — not baked into PHP config.