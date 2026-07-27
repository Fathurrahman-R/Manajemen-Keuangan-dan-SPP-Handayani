#!/usr/bin/env bash
set -e

SERVICE_NAME="backend"
# shellcheck source=../common/entrypoint-common.sh
source /usr/local/bin/entrypoint-common.sh

wait_for_mysql
composer_install_locked
bootstrap_env_file

optimize_cache_locked

echo "[backend] running migrate..."
php artisan migrate --force

# RBAC seeder (firstOrCreate/updateOrCreate — idempotent) aman dijalankan tiap boot.
# Menggantikan permissions:sync / permissions:sync-endpoints (command dihapus) — semua
# sinkronisasi permission/role/endpoint sekarang lewat seeder saja (lihat RbacSeeder,
# satu-satunya sumber daftar seeder RBAC — dipakai juga oleh DatabaseSeeder).
if [ "${SEED_ON_BOOT:-false}" = "true" ]; then
    echo "[backend] syncing RBAC via seeder..."
    php artisan db:seed --class=RbacSeeder --force

    # Seeder lain (SiswaSeeder dkk) generate data demo pakai factory dengan nilai unik (nis)
    # yang bukan idempotent — hanya boleh jalan sekali per DB, dan hanya dari service ini
    # (bukan dari backend-queue/backend-scheduler yang pakai entrypoint sama).
    DB_NAME="${DB_DATABASE:-handayani}"
    if ! SISWA_COUNT=$(mysql -h "${DB_HOST:-mysql}" -P "${DB_PORT:-3306}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" -N -B -e "SELECT COUNT(*) FROM siswas" "$DB_NAME" 2>&1); then
        echo "[backend] gagal cek jumlah siswa (kemungkinan DB belum siap): $SISWA_COUNT" >&2
        exit 1
    fi

    if [ "$SISWA_COUNT" = "0" ]; then
        echo "[backend] DB kosong, seeding data demo..."
        php artisan db:seed --force
    else
        echo "[backend] DB sudah ada data ($SISWA_COUNT siswa), skip seed data demo."
    fi
fi

exec "$@"
