#!/usr/bin/env bash
set -e

echo "[frontend] waiting for mysql:3306..."
until mysqladmin ping -h "${DB_HOST:-mysql}" -P "${DB_PORT:-3306}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" --silent 2>/dev/null; do
    sleep 2
done
echo "[frontend] mysql is up"

echo "[frontend] waiting for backend:8080..."
until curl -sf "http://backend:8080/up" >/dev/null 2>&1 || curl -s "http://backend:8080/up" >/dev/null 2>&1; do
    sleep 2
done
echo "[frontend] backend is up"

echo "[frontend] composer install..."
composer install --no-interaction --prefer-dist

if [ ! -f .env ]; then
    echo "[frontend] .env missing, copying from .env.example..."
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=.\+" .env; then
    echo "[frontend] APP_KEY empty, generating..."
    php artisan key:generate --ansi --force
fi

echo "[frontend] npm install..."
npm install

exec "$@"
