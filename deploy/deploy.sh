#!/usr/bin/env bash
# =============================================================================
# Deploy / update aplikasi. Jalankan dari dalam folder project:
#   bash deploy/deploy.sh
# Aman dijalankan berulang kali (idempotent).
# =============================================================================
set -euo pipefail

APP_DIR="/var/www/rental-mobil"
PHP_VER="8.4"
cd "${APP_DIR}"

echo ">>> [1/6] Tarik perubahan terbaru dari git"
git pull --ff-only || echo "(lewati git pull jika belum pakai remote)"

echo ">>> [2/6] Install dependency PHP (production)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo ">>> [3/6] Siapkan file .env & APP_KEY"
if [ ! -f .env ]; then
  cp .env.production.example .env
  echo "    .env dibuat dari .env.production.example — EDIT APP_URL dulu sebelum lanjut produksi!"
fi
# Generate APP_KEY HANYA jika belum ada (regenerate akan membatalkan session/enkripsi)
if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

echo ">>> [4/6] Siapkan database SQLite"
mkdir -p database
[ -f database/database.sqlite ] || touch database/database.sqlite

echo ">>> [5/6] Jalankan migrasi"
php artisan migrate --force

echo ">>> [6/6] Cache konfigurasi (route:cache DILEWATI karena ada Closure route di web.php)"
php artisan config:cache

echo ">>> Set permission writable untuk web server"
sudo chown -R "$USER":www-data storage bootstrap/cache database
sudo chmod -R 775 storage bootstrap/cache
sudo chmod 664 database/database.sqlite
sudo systemctl reload php${PHP_VER}-fpm
sudo systemctl reload nginx

echo ">>> DEPLOY SELESAI."
