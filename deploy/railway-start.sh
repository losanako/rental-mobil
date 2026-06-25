#!/usr/bin/env sh
# Satu start command untuk Railway: siapkan DB -> migrasi -> jalankan server.
# Semua dalam satu container supaya log linear & volume /data pasti ter-mount.
set -e

PORT="${PORT:-8080}"

echo ">>> [start] preparing sqlite database at /data"
mkdir -p /data
touch /data/database.sqlite

echo ">>> [start] running migrations"
php artisan migrate --force --no-interaction

php artisan config:clear || true

echo ">>> [start] launching web server on 0.0.0.0:${PORT}"
exec php artisan serve --host 0.0.0.0 --port "${PORT}"
