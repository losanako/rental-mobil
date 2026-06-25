#!/usr/bin/env sh
# Start command untuk Railway.
# Dijalankan via "sh deploy/railway-start.sh" dari railway.json.
# Membaca $PORT dari environment (Railway meng-inject-nya) dan menjalankan
# web server yang mengikat 0.0.0.0:$PORT — interface yang dijangkau Railway.
set -e

PORT="${PORT:-8080}"
echo ">>> Starting Laravel web server on 0.0.0.0:${PORT}"

# Bersihkan cache config supaya env terbaru terpakai
php artisan config:clear || true

# exec agar php jadi PID 1 (menerima sinyal stop dengan benar dari Railway)
exec php artisan serve --host 0.0.0.0 --port "${PORT}"
