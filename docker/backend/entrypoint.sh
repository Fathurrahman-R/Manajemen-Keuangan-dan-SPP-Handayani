#!/usr/bin/env bash
set -e

echo "[backend] waiting for mysql:3306..."
until mysqladmin ping -h "${DB_HOST:-mysql}" -P "${DB_PORT:-3306}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" --silent 2>/dev/null; do
    sleep 2
done
echo "[backend] mysql is up"

echo "[backend] composer install..."
composer install --no-interaction --prefer-dist

if [ ! -f .env ]; then
    echo "[backend] .env missing, copying from .env.example..."
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=.\+" .env; then
    echo "[backend] APP_KEY empty, generating..."
    php artisan key:generate --ansi --force
fi

echo "[backend] running migrate..."
php artisan migrate --force

# RBAC seeder (firstOrCreate/updateOrCreate — idempotent) aman dijalankan tiap boot.
# Menggantikan permissions:sync / permissions:sync-endpoints (command dihapus) — semua
# sinkronisasi permission/role/endpoint sekarang lewat seeder saja.
if [ "${SEED_ON_BOOT:-false}" = "true" ]; then
    echo "[backend] syncing RBAC via seeder..."
    php artisan db:seed --class=RoleAndPermissionSeeder --force
    php artisan db:seed --class=PermissionResourceSeeder --force
    php artisan db:seed --class=PermissionMetadataSeeder --force
    php artisan db:seed --class=PermissionEndpointSeeder --force

    # Seeder lain (SiswaSeeder dkk) generate data demo pakai factory dengan nilai unik (nis)
    # yang bukan idempotent — hanya boleh jalan sekali per DB, dan hanya dari service ini
    # (bukan dari backend-queue/backend-scheduler yang pakai entrypoint sama).
    DB_NAME="${DB_DATABASE:-handayani}"
    SISWA_COUNT=$(mysql -h "${DB_HOST:-mysql}" -P "${DB_PORT:-3306}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" -N -B -e "SELECT COUNT(*) FROM siswas" "$DB_NAME" 2>/dev/null || echo 0)
    if [ "$SISWA_COUNT" = "0" ]; then
        echo "[backend] DB kosong, seeding data demo..."
        php artisan db:seed --force
    else
        echo "[backend] DB sudah ada data ($SISWA_COUNT siswa), skip seed data demo."
    fi
fi

exec "$@"
