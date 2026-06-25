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

echo ">>> [1/8] Tarik perubahan terbaru dari git"
git pull --ff-only || echo "(lewati git pull jika belum pakai remote)"

echo ">>> [2/8] Install dependency PHP (production)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo ">>> [3/8] Build asset frontend (Vite)"
npm ci
npm run build

echo ">>> [4/8] Siapkan file .env & APP_KEY"
if [ ! -f .env ]; then
  cp .env.example .env
  echo "    .env dibuat dari .env.example — EDIT dulu sebelum lanjut produksi!"
fi
# Generate APP_KEY HANYA jika belum ada (regenerate akan membatalkan session/enkripsi)
if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

echo ">>> [5/8] Siapkan database SQLite"
mkdir -p database
[ -f database/database.sqlite ] || touch database/database.sqlite

echo ">>> [6/8] Jalankan migrasi"
php artisan migrate --force

echo ">>> [7/8] Cache konfigurasi & view (route:cache DILEWATI karena ada Closure route)"
php artisan config:cache
php artisan view:cache

echo ">>> [8/8] Set permission writable untuk web server"
sudo chown -R "$USER":www-data storage bootstrap/cache database
sudo chmod -R 775 storage bootstrap/cache
sudo chmod 664 database/database.sqlite
sudo systemctl reload php${PHP_VER}-fpm
sudo systemctl reload nginx

echo ">>> DEPLOY SELESAI."
