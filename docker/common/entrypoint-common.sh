#!/usr/bin/env bash
# Shared entrypoint helpers for backend & frontend containers — sourced, not executed
# directly. Keeping this in one place avoids the two entrypoints drifting out of sync
# (which already happened once: a fix landed in one and not the other).

wait_for_mysql() {
    echo "[$SERVICE_NAME] waiting for mysql:${DB_PORT:-3306}..."
    until mysqladmin ping -h "${DB_HOST:-mysql}" -P "${DB_PORT:-3306}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" --silent 2>/dev/null; do
        sleep 2
    done
    echo "[$SERVICE_NAME] mysql is up"
}

wait_for_backend_http() {
    echo "[$SERVICE_NAME] waiting for backend:8080..."
    until curl -sf "http://backend:8080/up" >/dev/null 2>&1; do
        sleep 2
    done
    echo "[$SERVICE_NAME] backend is up"
}

bootstrap_env_file() {
    if [ ! -f .env ]; then
        echo "[$SERVICE_NAME] .env missing, copying from .env.example..."
        cp .env.example .env
    fi

    if ! grep -q "^APP_KEY=.\+" .env; then
        echo "[$SERVICE_NAME] APP_KEY empty, generating..."
        php artisan key:generate --ansi --force
    fi
}

# Multiple containers (backend/backend-queue/backend-scheduler) share one vendor volume
# and can start concurrently — flock serializes composer so they don't race and corrupt
# the shared vendor directory. Lock file lives outside vendor/ so it survives even if
# vendor/ itself is wiped.
composer_install_locked() {
    echo "[$SERVICE_NAME] composer install (waiting for lock if another service is installing)..."
    (
        flock -w 300 200 || { echo "[$SERVICE_NAME] timed out waiting for composer install lock"; exit 1; }
        composer install --no-interaction --prefer-dist
    ) 200>.composer-install.lock
}

# Same concurrency concern as composer_install_locked: bootstrap/cache/*.php is
# bind-mounted, shared by backend/backend-queue/backend-scheduler starting together —
# two `artisan route:cache` writes racing can leave a half-written cache file. Reuses
# the same lock file since only one boot-time write step should run at a time anyway.
#
# Deliberately NOT config:cache here. Once cached, config() stops calling env() at
# all — including phpunit.xml's <env> overrides (DB_DATABASE=handayani_testing on
# backend, sqlite :memory: on frontend). `php artisan test` then silently runs
# against the real dev database instead of the isolated test one, and backend's
# TestCase::setUp() does raw `DELETE FROM users/branches/siswas/...` at the start
# of every test — this happened once already and wiped the dev DB. config:cache is
# a production-deploy optimization; OPcache's revalidate_freq tuning (php.ini)
# already covers the actual dev perf win, so it's not worth the risk here.
optimize_cache_locked() {
    echo "[$SERVICE_NAME] caching routes (waiting for lock if another service is caching)..."
    (
        flock -w 300 200 || { echo "[$SERVICE_NAME] timed out waiting for cache lock"; exit 1; }
        php artisan optimize:clear
        php artisan route:cache
        if [ -f app/Providers/Filament/AdminPanelProvider.php ]; then
            php artisan view:cache
            php artisan filament:cache-components
        fi
    ) 200>.composer-install.lock
}
