# Deploy Laravel ke AWS EC2 (Free Tier) — Step by Step

Panduan ini memakai **AWS EC2 t2.micro/t3.micro (Free Tier, gratis 12 bulan)** +
**Nginx + PHP-FPM + SQLite**. Ini opsi **termurah** (tanpa biaya RDS/Load Balancer)
dan cocok untuk pelajar. Database SQLite disimpan di disk instance (persisten,
tidak hilang saat restart).

Estimasi waktu: ~30–45 menit.

---

## Ringkasan file yang sudah disiapkan

| File | Fungsi |
|---|---|
| [deploy/nginx.conf](deploy/nginx.conf) | Konfigurasi web server Nginx untuk Laravel |
| [deploy/setup.sh](deploy/setup.sh) | Provisioning server (sekali jalan): install PHP, Nginx, Composer, Node, swap |
| [deploy/deploy.sh](deploy/deploy.sh) | Deploy/update aplikasi (bisa diulang) |

---

## BAGIAN 1 — Buat & Launch EC2 Instance

### 1.1 Login AWS
1. Buka https://aws.amazon.com → **Create an AWS Account** (butuh kartu, tapi Free Tier $0).
2. Login ke **AWS Management Console**.
3. Di kanan atas, pilih **Region** terdekat, mis. `Asia Pacific (Singapore) ap-southeast-1`.

### 1.2 Launch Instance
1. Cari **EC2** di search bar → buka **EC2 Dashboard** → klik **Launch instance**.
2. **Name**: `rental-mobil`.
3. **Application and OS Images**: pilih **Ubuntu Server 24.04 LTS** (pastikan label **Free tier eligible**).
4. **Instance type**: pilih **t2.micro** atau **t3.micro** (yang bertanda **Free tier eligible**).
5. **Key pair (login)**: klik **Create new key pair**
   - Name: `rental-mobil-key`
   - Type: RSA, Format: **.pem**
   - Klik **Create** → file `rental-mobil-key.pem` ter-download. **SIMPAN, jangan hilang.**
6. **Network settings** → klik **Edit**, centang:
   - ✅ Allow SSH traffic from **My IP** (lebih aman) atau Anywhere
   - ✅ Allow HTTP traffic from the internet
   - ✅ Allow HTTPS traffic from the internet
7. **Configure storage**: biarkan default (8 GB gp3 cukup, masih Free Tier sampai 30 GB).
8. Klik **Launch instance** → **View all instances**.
9. Tunggu **Instance state = Running** dan **Status check = 2/2 passed**.
10. Klik instance-nya, catat **Public IPv4 address** (mis. `13.250.xx.xx`).

---

## BAGIAN 2 — Connect via SSH

Di terminal komputer Anda (macOS/Linux), masuk ke folder tempat file `.pem` berada:

```bash
chmod 400 rental-mobil-key.pem
ssh -i rental-mobil-key.pem ubuntu@<PUBLIC_IP>
```

Ganti `<PUBLIC_IP>` dengan IP dari langkah 1.10. Ketik `yes` saat ditanya pertama kali.
Kalau berhasil, prompt berubah jadi `ubuntu@ip-...:~$`.

> **Windows**: pakai aplikasi terminal (PowerShell) dengan perintah sama, atau pakai
> PuTTY (konversi `.pem` → `.ppk` dulu via PuTTYgen).

---

## BAGIAN 3 — Ambil Kode & Provisioning Server

### 3.1 Clone project ke server
Project harus ada di GitHub dulu. Dari dalam server (SSH):

```bash
sudo mkdir -p /var/www
sudo chown ubuntu:ubuntu /var/www
cd /var/www
git clone https://github.com/<username>/<repo-rental-mobil>.git rental-mobil
cd rental-mobil
```

> Kalau repo private, buat **Personal Access Token** di GitHub dan pakai sebagai password,
> atau setup SSH deploy key.

### 3.2 Jalankan provisioning (sekali saja)

```bash
chmod +x deploy/*.sh
bash deploy/setup.sh
```

Script ini memasang PHP 8.4, Nginx, Composer, Node 22, membuat swap 2 GB,
dan mengaktifkan konfigurasi Nginx. Tunggu sampai muncul **SELESAI**.

---

## BAGIAN 4 — Konfigurasi `.env` Produksi

```bash
cp .env.example .env
nano .env
```

Ubah/isi nilai berikut (sisanya boleh default):

```env
APP_NAME="Rental Mobil"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://<PUBLIC_IP>

DB_CONNECTION=sqlite
# Baris DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD biarkan dikomentari (#)

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Simpan di nano: `Ctrl+O` → `Enter` → `Ctrl+X`.

> `APP_KEY` belum diisi — akan di-generate otomatis oleh `deploy.sh` di bagian berikut.

---

## BAGIAN 5 — Deploy Aplikasi

```bash
bash deploy/deploy.sh
```

Script ini akan: install dependency, build asset, generate `APP_KEY`, buat SQLite,
jalankan migrasi, cache config, dan set permission. Tunggu sampai **DEPLOY SELESAI**.

---

## BAGIAN 6 — Tes

Buka browser:

```
http://<PUBLIC_IP>
```

Harus muncul JSON:

```json
{ "message": "Welcome to Rental Mobil API", "version": "1.0" }
```

🎉 **Aplikasi sudah LIVE.**

---

## BAGIAN 7 (Opsional) — Domain + HTTPS Gratis

Kalau punya domain:
1. Di DNS domain Anda, buat **A record** mengarah ke `<PUBLIC_IP>`.
2. Edit [deploy/nginx.conf](deploy/nginx.conf): ganti `server_name _;` → `server_name domain-anda.com;`
   lalu `sudo cp deploy/nginx.conf /etc/nginx/sites-available/rental-mobil && sudo nginx -t && sudo systemctl reload nginx`.
3. Install SSL gratis (Let's Encrypt):
   ```bash
   sudo apt-get install -y certbot python3-certbot-nginx
   sudo certbot --nginx -d domain-anda.com
   ```
4. Ubah `APP_URL=https://domain-anda.com` di `.env`, lalu `php artisan config:cache`.

---

## Update Aplikasi Berikutnya

Setiap ada perubahan kode (sudah di-push ke GitHub):

```bash
ssh -i rental-mobil-key.pem ubuntu@<PUBLIC_IP>
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
| **`composer`/`npm` ke-kill saat install** | RAM habis — pastikan swap aktif: `free -h` (harus ada Swap 2 GB). Jalankan `bash deploy/setup.sh` ulang. |
| **Tidak bisa diakses dari browser** | Cek Security Group instance: port **80** (HTTP) harus terbuka ke `0.0.0.0/0`. |
| **Setelah ubah `.env` tidak berubah** | Jalankan `php artisan config:cache` lagi. |

---

## Catatan Biaya

- **t2.micro/t3.micro**: gratis 750 jam/bulan selama **12 bulan pertama** (1 instance nonstop = ~730 jam, jadi $0).
- **SQLite**: tanpa biaya tambahan (tidak pakai RDS).
- Setelah 12 bulan, instance ini ~$8–10/bulan. Untuk tetap murah, bisa pindah ke
  **Lightsail $5/bulan** atau matikan instance saat tidak dipakai.
- **Pantau Free Tier**: AWS Console → Billing → **Free Tier** untuk hindari tagihan tak terduga.
  Disarankan set **Billing alarm** di $1.
