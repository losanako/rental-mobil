#!/usr/bin/env sh
# Satu start command untuk Railway: siapkan DB -> migrasi -> jalankan server.
# Server bind ke [::] (IPv6) yang di Linux dual-stack -> terima IPv4 & IPv6,
# supaya proxy internal Railway (IPv6) bisa konek (fix "connection dial timeout").
set -e

PORT="${PORT:-8080}"

echo ">>> [start] preparing sqlite database at /data"
mkdir -p /data
touch /data/database.sqlite

echo ">>> [start] running migrations"
php artisan migrate --force --no-interaction

php artisan config:clear || true

echo ">>> [start] launching web server on [::]:${PORT} (IPv4+IPv6 dual-stack)"
exec php -S "[::]:${PORT}" -t public public/railway-router.php
