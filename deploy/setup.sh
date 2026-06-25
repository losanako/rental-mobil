#!/usr/bin/env bash
# =============================================================================
# Provisioning awal server EC2 (Ubuntu 22.04/24.04) untuk Laravel.
# Jalankan SEKALI saja, sebagai user dengan sudo:
#   bash deploy/setup.sh
# Script ini: install PHP 8.4 + Nginx + Composer + Node, bikin swap,
# pasang konfigurasi Nginx, dan set permission.
# =============================================================================
set -euo pipefail

APP_DIR="/var/www/rental-mobil"
PHP_VER="8.4"

echo ">>> [1/7] Update sistem"
sudo apt-get update -y && sudo apt-get upgrade -y

echo ">>> [2/7] Buat swap 2GB (penting untuk instance RAM 1GB)"
if [ ! -f /swapfile ]; then
  sudo fallocate -l 2G /swapfile
  sudo chmod 600 /swapfile
  sudo mkswap /swapfile
  sudo swapon /swapfile
  echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
else
  echo "swapfile sudah ada, lewati."
fi

echo ">>> [3/7] Install PHP ${PHP_VER}, Nginx, Git, unzip"
sudo apt-get install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update -y
sudo apt-get install -y nginx git unzip curl \
  php${PHP_VER}-fpm php${PHP_VER}-cli php${PHP_VER}-common \
  php${PHP_VER}-mbstring php${PHP_VER}-xml php${PHP_VER}-bcmath \
  php${PHP_VER}-curl php${PHP_VER}-zip php${PHP_VER}-sqlite3 \
  php${PHP_VER}-intl php${PHP_VER}-gd php${PHP_VER}-mysql

echo ">>> [4/7] Install Composer"
if ! command -v composer >/dev/null 2>&1; then
  curl -sS https://getcomposer.org/installer | php
  sudo mv composer.phar /usr/local/bin/composer
fi

echo ">>> [5/7] Install Node.js 22 (untuk build asset Vite)"
if ! command -v node >/dev/null 2>&1; then
  curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
  sudo apt-get install -y nodejs
fi

echo ">>> [6/7] Pasang konfigurasi Nginx"
sudo cp "${APP_DIR}/deploy/nginx.conf" /etc/nginx/sites-available/rental-mobil
sudo ln -sf /etc/nginx/sites-available/rental-mobil /etc/nginx/sites-enabled/rental-mobil
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl enable nginx php${PHP_VER}-fpm

echo ">>> [7/7] Set kepemilikan folder app"
sudo chown -R "$USER":www-data "${APP_DIR}"

echo ">>> SELESAI. Lanjut: konfigurasi .env lalu jalankan: bash deploy/deploy.sh"
