# Dokumentasi Deploy: Laravel API "Rental Mobil" ke AWS Lightsail

Panduan lengkap dari **nol** — mulai membuat instance Lightsail sampai aplikasi
**live** dan bisa diakses. Stack yang dipakai:

- **AWS Lightsail** — instance Ubuntu 24.04 LTS (paket $5/bulan, 1 GB RAM)
- **Nginx** sebagai web server
- **PHP-FPM 8.4** sebagai runtime
- **SQLite** sebagai database (file di disk instance, persisten)

> Aplikasi ini **API-only** (REST + Laravel Sanctum token auth). Tidak ada
> frontend yang perlu di-build (Vite), jadi **tidak perlu Node.js** di server.

Estimasi total waktu: **±20–30 menit**.

---

## Daftar Isi

1. [Prasyarat](#prasyarat)
2. [Arsitektur & file pendukung](#arsitektur--file-pendukung)
3. [Bagian 1 — Buat instance Lightsail](#bagian-1--buat-instance-lightsail)
4. [Bagian 2 — Static IP & buka port](#bagian-2--static-ip--buka-port-firewall)
5. [Bagian 3 — Connect via SSH](#bagian-3--connect-ke-server-via-ssh)
6. [Bagian 4 — Ambil kode & provisioning](#bagian-4--ambil-kode--provisioning-server)
7. [Bagian 5 — Konfigurasi .env](#bagian-5--konfigurasi-env)
8. [Bagian 6 — Deploy aplikasi](#bagian-6--deploy-aplikasi)
9. [Bagian 7 — Uji aplikasi](#bagian-7--uji-aplikasi)
10. [Bagian 8 (opsional) — Domain + HTTPS](#bagian-8-opsional--domain--https-gratis)
11. [Update aplikasi berikutnya](#update-aplikasi-berikutnya)
12. [Perawatan (backup, log, restart)](#perawatan-backup-log-restart)
13. [Troubleshooting](#troubleshooting)
14. [Catatan biaya](#catatan-biaya)

---

## Prasyarat

Sebelum mulai, pastikan punya:

- **Akun AWS** yang aktif (sudah verifikasi kartu/pembayaran). Daftar di
  https://aws.amazon.com.
- **Kode sudah ada di Git remote** (mis. GitHub) — karena server akan
  `git clone` dari sana. Pastikan branch yang mau dideploy sudah di-push.
- Sedikit familiar dengan **terminal/SSH**. (Lightsail juga menyediakan SSH lewat
  browser, jadi tanpa setup key pun bisa.)

---

## Arsitektur & file pendukung

Alur request saat sudah live:

```
Internet  →  Nginx (port 80)  →  PHP-FPM 8.4  →  Laravel (public/index.php)  →  SQLite
```

File yang sudah disiapkan di repo (tidak perlu dibuat manual):

| File | Fungsi |
|---|---|
| [deploy/setup.sh](deploy/setup.sh) | Provisioning server **(jalan sekali)**: install PHP 8.4, Nginx, Composer, buat swap, pasang Nginx |
| [deploy/deploy.sh](deploy/deploy.sh) | Deploy/update aplikasi **(boleh diulang)**: composer install, key, migrasi, cache, permission |
| [deploy/nginx.conf](deploy/nginx.conf) | Konfigurasi virtual host Nginx (root ke `public/`, fastcgi ke `php8.4-fpm.sock`) |
| [.env.production.example](.env.production.example) | Template `.env` untuk produksi |

---

## Bagian 1 — Buat Instance Lightsail

1. Buka **https://lightsail.aws.amazon.com** dan login akun AWS.
2. Klik tombol **Create instance**.
3. **Instance location**: pilih region terdekat dengan pengguna, mis.
   `Asia Pacific (Singapore) ap-southeast-1`.
4. **Pick your instance image**:
   - **Platform**: `Linux/Unix`
   - **Blueprint**: pilih tab **OS Only** → **Ubuntu 24.04 LTS**
5. **Choose your instance plan**: pilih paket **$5 USD/bulan** (1 GB RAM, 2 vCPU, 40 GB SSD).
   > Paket $3.5 (512 MB RAM) juga bisa karena `setup.sh` membuat swap 2 GB, tapi
   > $5 lebih nyaman saat `composer install`.
6. **Identify your instance**: beri nama `rental-mobil`.
7. Klik **Create instance**.
8. Tunggu di dashboard sampai status instance menjadi **Running** (±1–2 menit).

---

## Bagian 2 — Static IP & Buka Port Firewall

### 2.1 Pasang Static IP (agar IP tidak berubah saat restart)
1. Buka tab **Networking** (di menu atas Lightsail).
2. Klik **Create static IP**.
3. Pilih region yang sama, **Attach** ke instance `rental-mobil`.
4. Beri nama (mis. `rental-mobil-ip`) → **Create**.
5. **Catat IP-nya**, mis. `13.250.10.20`. IP ini dipakai di langkah-langkah berikut
   (ditulis sebagai `<STATIC_IP>`).

### 2.2 Buka port HTTP
1. Klik instance `rental-mobil` → tab **Networking**.
2. Di bagian **IPv4 Firewall**, klik **Add rule**.
3. Pilih **Application: HTTP** (otomatis TCP port **80**) → **Create**.
   > Port **22 (SSH)** sudah terbuka default. Port **443 (HTTPS)** baru ditambahkan
   > nanti jika pakai domain (Bagian 8).

---

## Bagian 3 — Connect ke Server via SSH

**Cara termudah (tanpa key):** di halaman instance Lightsail, klik tombol
**Connect using SSH** → terminal browser langsung terbuka sebagai user `ubuntu`.

**Atau dari terminal komputer sendiri:**
1. Lightsail → **Account** → tab **SSH keys** → **Download** default key
   (mis. `LightsailDefaultKey-ap-southeast-1.pem`).
2. Jalankan:
   ```bash
   chmod 400 LightsailDefaultKey-ap-southeast-1.pem
   ssh -i LightsailDefaultKey-ap-southeast-1.pem ubuntu@<STATIC_IP>
   ```
3. Ketik `yes` saat pertama kali ditanya fingerprint.

Kalau berhasil, prompt berubah jadi:
```
ubuntu@ip-172-26-xx-xx:~$
```

---

## Bagian 4 — Ambil Kode & Provisioning Server

Jalankan di dalam server (via SSH). Ganti `<URL_REPO_ANDA>` dengan URL Git repo Anda.

```bash
# 1) Siapkan folder app
sudo mkdir -p /var/www
sudo chown -R ubuntu:ubuntu /var/www

# 2) Clone repo ke /var/www/rental-mobil
git clone <URL_REPO_ANDA> /var/www/rental-mobil
cd /var/www/rental-mobil

# (opsional) jika deploy dari branch tertentu:
# git checkout prepare-lightsail-deploy

# 3) Provisioning server — JALAN SEKALI saja
bash deploy/setup.sh
```

`setup.sh` akan: update sistem, membuat **swap 2 GB**, install **PHP 8.4 + Nginx +
Composer**, memasang konfigurasi Nginx, dan mengatur kepemilikan folder.

**Output yang diharapkan** di akhir:
```
>>> [6/6] Set kepemilikan folder app
>>> SELESAI. Lanjut: konfigurasi .env lalu jalankan: bash deploy/deploy.sh
```

> Jika `nginx -t` di tengah proses error, lihat [Troubleshooting](#troubleshooting).

---

## Bagian 5 — Konfigurasi .env

```bash
cp .env.production.example .env
nano .env
```

Ubah baris berikut (sisanya boleh dibiarkan default):

```env
APP_NAME="Rental Mobil API"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://<STATIC_IP>

DB_CONNECTION=sqlite
```

Simpan di `nano`: tekan `Ctrl+O` → `Enter` → `Ctrl+X`.

> `APP_KEY` sengaja dibiarkan kosong — akan di-generate otomatis oleh `deploy.sh`.
> **Jangan** set `APP_DEBUG=true` di produksi (membocorkan detail error).

---

## Bagian 6 — Deploy Aplikasi

```bash
bash deploy/deploy.sh
```

Script ini (idempotent, aman diulang) akan:
1. `git pull` perubahan terbaru
2. `composer install --no-dev --optimize-autoloader`
3. Generate `APP_KEY` (hanya jika belum ada)
4. Membuat file `database/database.sqlite`
5. `php artisan migrate --force`
6. `php artisan config:cache`
7. Set permission `storage/`, `bootstrap/cache/`, `database/` ke `www-data` + reload Nginx & PHP-FPM

**Output yang diharapkan** di akhir:
```
>>> DEPLOY SELESAI.
```

---

## Bagian 7 — Uji Aplikasi

### 7.1 Health check
```bash
curl http://<STATIC_IP>/up
```
Harus mengembalikan halaman status 200 (bawaan Laravel).

### 7.2 Root endpoint
Buka browser ke `http://<STATIC_IP>` — harus muncul:
```json
{ "message": "Welcome to Rental Mobil API", "version": "1.0" }
```

### 7.3 Uji alur autentikasi
```bash
# Register user pertama
curl -X POST http://<STATIC_IP>/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Admin","email":"admin@mail.com","password":"secret123","password_confirmation":"secret123"}'
```
Respons berisi `"token": "..."`. Simpan token tersebut, lalu:
```bash
# Lihat profil user (butuh token)
curl http://<STATIC_IP>/api/me -H "Authorization: Bearer <TOKEN>"

# Tambah 1 mobil
curl -X POST http://<STATIC_IP>/api/cars \
  -H "Authorization: Bearer <TOKEN>" -H "Content-Type: application/json" \
  -d '{"brand":"Toyota","model":"Avanza","plate_number":"B1234XY","year":2022,"color":"Hitam","price_per_day":350000,"status":"available"}'

# Lihat daftar mobil
curl http://<STATIC_IP>/api/cars -H "Authorization: Bearer <TOKEN>"
```

### 7.4 Referensi endpoint (semua butuh `Authorization: Bearer <TOKEN>` kecuali login/register)

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/register` | Daftar user (publik) |
| POST | `/api/login` | Login, dapat token (publik) |
| POST | `/api/logout` | Hapus token aktif |
| GET | `/api/me` | Profil user |
| GET/POST | `/api/cars` | List / tambah mobil |
| GET | `/api/cars/available` | Mobil yang tersedia |
| POST | `/api/cars/batch` | Tambah banyak mobil sekaligus |
| GET/PUT/DELETE | `/api/cars/{id}` | Detail / ubah / hapus mobil |
| GET/POST | `/api/customers` | List / tambah customer |
| GET/PUT/DELETE | `/api/customers/{id}` | Detail / ubah / hapus customer |
| GET/POST | `/api/rentals` | List / buat rental |
| GET | `/api/rentals/customer/{id}` | Rental milik customer |
| PUT | `/api/rentals/{id}/status` | Ubah status rental |
| GET/PUT/DELETE | `/api/rentals/{id}` | Detail / ubah / hapus rental |
| GET/POST | `/api/payments` | List / buat pembayaran |
| GET | `/api/payments/paid` | Pembayaran lunas |
| GET/PUT/DELETE | `/api/payments/{id}` | Detail / ubah / hapus pembayaran |
| GET | `/api/history` | Riwayat rental & pembayaran |

✅ Jika langkah di atas berhasil, **aplikasi sudah LIVE**.

---

## Bagian 8 (Opsional) — Domain + HTTPS Gratis

Kalau punya domain:

1. Di pengelola DNS domain Anda, buat **A record** mengarah ke `<STATIC_IP>`.
2. Buka port **443 (HTTPS)** di **IPv4 Firewall** Lightsail (lihat Bagian 2.2,
   pilih Application: HTTPS).
3. Edit `deploy/nginx.conf`: ganti `server_name _;` → `server_name domain-anda.com;`,
   lalu pasang ulang:
   ```bash
   sudo cp deploy/nginx.conf /etc/nginx/sites-available/rental-mobil
   sudo nginx -t && sudo systemctl reload nginx
   ```
4. Install sertifikat SSL gratis (Let's Encrypt):
   ```bash
   sudo apt-get install -y certbot python3-certbot-nginx
   sudo certbot --nginx -d domain-anda.com
   ```
   Certbot otomatis mengatur HTTPS + auto-renew.
5. Ubah `APP_URL=https://domain-anda.com` di `.env`, lalu:
   ```bash
   php artisan config:cache
   ```

---

## Update Aplikasi Berikutnya

Setiap ada perubahan kode yang sudah di-push ke Git:

```bash
ssh ubuntu@<STATIC_IP>           # atau Connect using SSH dari Lightsail
cd /var/www/rental-mobil
bash deploy/deploy.sh
```

`deploy.sh` akan menarik kode terbaru, install dependency, migrasi, dan cache ulang.

---

## Perawatan (backup, log, restart)

**Backup database SQLite** (cukup salin 1 file):
```bash
cp /var/www/rental-mobil/database/database.sqlite ~/backup-$(date +%F).sqlite
# Lalu unduh ke komputer lokal (dari terminal lokal):
# scp -i key.pem ubuntu@<STATIC_IP>:~/backup-*.sqlite .
```

**Lihat log aplikasi:**
```bash
tail -f /var/www/rental-mobil/storage/logs/laravel.log
```

**Restart layanan:**
```bash
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
```

**Cek status layanan:**
```bash
sudo systemctl status nginx php8.4-fpm
free -h          # pastikan swap aktif (Swap: 2.0Gi)
```

---

## Troubleshooting

| Masalah | Solusi |
|---|---|
| **502 Bad Gateway** | PHP-FPM mati/socket salah. `sudo systemctl status php8.4-fpm`. Pastikan socket di `nginx.conf` = `unix:/run/php/php8.4-fpm.sock`. |
| **500 / halaman putih** | Lihat `tail -50 storage/logs/laravel.log`. Sementara set `APP_DEBUG=true` → `php artisan config:cache` → lihat error → kembalikan ke `false`. |
| **Permission denied / gagal nulis log** | `sudo chown -R ubuntu:www-data storage bootstrap/cache database && sudo chmod -R 775 storage bootstrap/cache`. |
| **`database is locked` / gagal migrate** | Pastikan `database/database.sqlite` ada, `chmod 664`, dimiliki `www-data`. |
| **`composer install` ke-kill / out of memory** | RAM habis. Pastikan swap aktif: `free -h` (harus ada Swap 2 GB). Ulangi `bash deploy/setup.sh`. |
| **Tidak bisa diakses dari browser** | Cek **IPv4 Firewall** Lightsail: port **80** harus ditambahkan (Bagian 2.2). |
| **Perubahan `.env` tidak terbaca** | `php artisan config:cache` lagi (config di-cache). |
| **`nginx -t` error setelah ganti server_name** | Cek typo di `deploy/nginx.conf`, lalu pasang & test ulang. |

---

## Catatan Biaya

- **Lightsail $5/bulan**: harga flat — 1 GB RAM, 2 vCPU, 40 GB SSD, 2 TB transfer.
  Sering ada **free trial 3 bulan pertama** untuk paket ini.
- **Static IP**: gratis selama ter-attach ke instance yang aktif.
- **SQLite**: tanpa biaya tambahan (tidak pakai database terkelola/RDS).
- Pantau tagihan di **Lightsail → Account → Billing**. Untuk hemat, hapus instance
  & static IP saat tidak dipakai.
