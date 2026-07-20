#!/usr/bin/env bash
set -e

SERVICE_NAME="frontend"
# shellcheck source=../common/entrypoint-common.sh
source /usr/local/bin/entrypoint-common.sh

wait_for_mysql
wait_for_backend_http
composer_install_locked
bootstrap_env_file
optimize_cache_locked

echo "[frontend] npm install..."
npm install

exec "$@"
