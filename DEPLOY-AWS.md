# Deploy Laravel API ke AWS Lightsail — Step by Step

Panduan ini memakai **AWS Lightsail (instance Ubuntu, paket $5/bulan)** +
**Nginx + PHP-FPM 8.4 + SQLite**. Opsi murah & sederhana, cocok untuk app kecil.
Database SQLite disimpan di disk instance (persisten, tidak hilang saat restart).

> App ini **API-only** (REST + Sanctum token). Tidak ada build frontend (Vite),
> jadi tidak perlu Node.js di server.

Estimasi waktu: ~20–30 menit.

---

## Ringkasan file yang sudah disiapkan

| File | Fungsi |
|---|---|
| [deploy/nginx.conf](deploy/nginx.conf) | Konfigurasi web server Nginx untuk Laravel |
| [deploy/setup.sh](deploy/setup.sh) | Provisioning server (sekali jalan): install PHP, Nginx, Composer, swap |
| [deploy/deploy.sh](deploy/deploy.sh) | Deploy/update aplikasi (bisa diulang) |
| [.env.production.example](.env.production.example) | Contoh konfigurasi `.env` untuk produksi |

---

## BAGIAN 1 — Buat Instance Lightsail

1. Buka https://lightsail.aws.amazon.com → login akun AWS.
2. Klik **Create instance**.
3. **Instance location**: pilih region terdekat, mis. `Asia Pacific (Singapore)`.
4. **Platform**: pilih **Linux/Unix**.
5. **Blueprint**: pilih **OS Only** → **Ubuntu 24.04 LTS**.
6. **Instance plan**: pilih paket **$5/bulan** (1 GB RAM) — disarankan minimal ini
   (paket $3.5 dengan 512 MB RAM juga bisa karena `setup.sh` membuat swap 2 GB).
7. **Identify your instance**: beri nama `rental-mobil`.
8. Klik **Create instance**. Tunggu status jadi **Running**.

### 1.1 Pasang Static IP (penting agar IP tidak berubah)
1. Di Lightsail, buka tab **Networking** → **Create static IP**.
2. Attach ke instance `rental-mobil`. Catat IP-nya (mis. `13.250.xx.xx`).

### 1.2 Buka port HTTP di firewall
1. Klik instance `rental-mobil` → tab **Networking**.
2. Di **IPv4 Firewall**, klik **Add rule** → pilih **HTTP (TCP 80)** → **Create**.
   (SSH port 22 sudah terbuka secara default.)

---

## BAGIAN 2 — Connect via SSH

Cara termudah: di halaman instance Lightsail, klik tombol **Connect using SSH**
(terminal browser langsung terbuka, tidak perlu key).

Atau dari terminal komputer sendiri (unduh default key di Lightsail → Account → SSH keys):

```bash
chmod 400 LightsailDefaultKey.pem
ssh -i LightsailDefaultKey.pem ubuntu@<STATIC_IP>
```

Kalau berhasil, prompt berubah jadi `ubuntu@ip-...:~$`.

---

## BAGIAN 3 — Ambil Kode & Provisioning

```bash
# Clone repo ke /var/www/rental-mobil
sudo mkdir -p /var/www
sudo chown -R ubuntu:ubuntu /var/www
git clone <URL_REPO_ANDA> /var/www/rental-mobil
cd /var/www/rental-mobil

# Provisioning server (install PHP 8.4, Nginx, Composer, swap, pasang nginx.conf)
bash deploy/setup.sh
```

Tunggu sampai muncul **SELESAI**. (Script ini cukup dijalankan **sekali**.)

---

## BAGIAN 4 — Konfigurasi .env

```bash
cp .env.production.example .env
nano .env
```

Ubah nilai berikut:

```env
APP_NAME="Rental Mobil API"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://<STATIC_IP>

DB_CONNECTION=sqlite
```

Simpan di nano: `Ctrl+O` → `Enter` → `Ctrl+X`.

> `APP_KEY` dibiarkan kosong — akan di-generate otomatis oleh `deploy.sh`.

---

## BAGIAN 5 — Deploy Aplikasi

```bash
bash deploy/deploy.sh
```

Script ini akan: install dependency PHP (production), generate `APP_KEY`,
buat database SQLite, jalankan migrasi, cache config, dan set permission.
Tunggu sampai **DEPLOY SELESAI**.

---

## BAGIAN 6 — Tes

Cek health check:

```bash
curl http://<STATIC_IP>/up
```

Buka di browser `http://<STATIC_IP>` — harus muncul JSON:

```json
{ "message": "Welcome to Rental Mobil API", "version": "1.0" }
```

Uji alur auth:

```bash
# Register user pertama
curl -X POST http://<STATIC_IP>/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Admin","email":"admin@mail.com","password":"secret123","password_confirmation":"secret123"}'
```

Respons berisi `token`. Pakai token itu untuk endpoint lain, mis:

```bash
curl http://<STATIC_IP>/api/me -H "Authorization: Bearer <TOKEN>"
```

🎉 **Aplikasi sudah LIVE.**

---

## BAGIAN 7 (Opsional) — Domain + HTTPS Gratis

Kalau punya domain:
1. Di DNS domain Anda, buat **A record** mengarah ke `<STATIC_IP>`.
2. Edit [deploy/nginx.conf](deploy/nginx.conf): ganti `server_name _;` → `server_name domain-anda.com;`
   lalu:
   ```bash
   sudo cp deploy/nginx.conf /etc/nginx/sites-available/rental-mobil
   sudo nginx -t && sudo systemctl reload nginx
   ```
3. Install SSL gratis (Let's Encrypt):
   ```bash
   sudo apt-get install -y certbot python3-certbot-nginx
   sudo certbot --nginx -d domain-anda.com
   ```
   (Pastikan port **443/HTTPS** sudah dibuka di firewall Lightsail.)
4. Ubah `APP_URL=https://domain-anda.com` di `.env`, lalu `php artisan config:cache`.

---

## Update Aplikasi Berikutnya

Setiap ada perubahan kode (sudah di-push ke GitHub):

```bash
ssh ubuntu@<STATIC_IP>          # atau Connect using SSH dari Lightsail
cd /var/www/rental-mobil
bash deploy/deploy.sh
```

---

## Troubleshooting

| Masalah | Solusi |
|---|---|
| **502 Bad Gateway** | Cek PHP-FPM: `sudo systemctl status php8.4-fpm`. Pastikan socket di `nginx.conf` = `php8.4-fpm.sock`. |
| **500 / halaman putih** | Lihat log: `tail -50 storage/logs/laravel.log`. Sementara set `APP_DEBUG=true` lalu `php artisan config:cache`. |
| **"Permission denied" / gagal nulis log** | `sudo chown -R ubuntu:www-data storage bootstrap/cache database && sudo chmod -R 775 storage bootstrap/cache`. |
| **`database is locked` / gagal migrate** | Pastikan `database/database.sqlite` ada & `chmod 664`, dimiliki `www-data`. |
| **`composer install` ke-kill** | RAM habis — pastikan swap aktif: `free -h` (harus ada Swap 2 GB). Jalankan `bash deploy/setup.sh` ulang. |
| **Tidak bisa diakses dari browser** | Cek **IPv4 Firewall** Lightsail: port **80 (HTTP)** harus ditambahkan. |
| **Setelah ubah `.env` tidak berubah** | Jalankan `php artisan config:cache` lagi. |

---

## Catatan Biaya

- **Lightsail $5/bulan**: harga flat, sudah termasuk 1 GB RAM + 40 GB SSD + transfer data.
  (Sering ada **free trial 3 bulan pertama** untuk paket ini.)
- **SQLite**: tanpa biaya tambahan (tidak pakai database terkelola).
- Untuk hemat, matikan/hapus instance saat tidak dipakai dan pantau di
  **Lightsail → Account → Billing**.
