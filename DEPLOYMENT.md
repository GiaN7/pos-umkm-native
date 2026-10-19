# Deployment Native — POS UMKM

Panduan ini menjalankan aplikasi langsung di server Linux dengan PHP-FPM, Nginx, Node.js, dan MySQL/MariaDB. Semua service dipasang dan dikelola oleh sistem operasi; tidak ada container.

## 1. Siapkan server

Contoh berikut menggunakan Ubuntu 24.04 dan PHP 8.3:

```bash
sudo apt update
sudo apt install -y nginx mysql-server php8.3-cli php8.3-fpm \
  php8.3-mysql php8.3-sqlite3 php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-gd php8.3-zip sqlite3 unzip git
```

Pasang Composer dan Node.js 18 atau yang lebih baru sesuai petunjuk resmi distro/proyek masing-masing. Pastikan perintah `php`, `composer`, `node`, `npm`, dan `mysql` tersedia.

Firewall hanya perlu membuka SSH, HTTP, dan HTTPS. Jangan membuka port MySQL ke internet:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

## 2. Ambil kode dan siapkan database

Simpan project di `/var/www/pos-umkm-native` agar cocok dengan konfigurasi Nginx yang tersedia:

```bash
sudo git clone <repo-url> /var/www/pos-umkm-native
sudo chown -R "$USER":"$USER" /var/www/pos-umkm-native
cd /var/www/pos-umkm-native
```

Buat database dan user khusus aplikasi:

```bash
sudo mysql
```

```sql
CREATE DATABASE pos_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pos_user'@'127.0.0.1' IDENTIFIED BY 'ganti-dengan-password-kuat';
GRANT ALL PRIVILEGES ON pos_system.* TO 'pos_user'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

Salin environment backend dan edit `backend/.env`:

```bash
cp backend/.env.example backend/.env
```

Nilai production yang perlu diatur:

```env
APP_NAME="POS UMKM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_system
DB_USERNAME=pos_user
DB_PASSWORD="ganti-dengan-password-kuat"

SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=pos.example.com
CORS_ALLOWED_ORIGINS=https://pos.example.com
```

Jangan pernah commit `backend/.env`. Simpan backup environment dengan aman karena file itu berisi `APP_KEY` dan kredensial database.

## 3. Build dan migrasi aplikasi

Jalankan sebagai user deployment, bukan root:

```bash
chmod +x deploy.sh
./deploy.sh
```

Script memasang dependency production, membuat build frontend, menjalankan migrasi, membuat symlink storage, dan mengoptimalkan cache Laravel. Untuk development lokal dengan SQLite gunakan `./setup.sh`, bukan script deployment ini.

Berikan akses tulis PHP-FPM ke direktori runtime Laravel:

```bash
sudo chown -R www-data:www-data backend/storage backend/bootstrap/cache
sudo chmod -R ug+rwX backend/storage backend/bootstrap/cache
```

## 4. Konfigurasi Nginx + PHP-FPM

Salin konfigurasi site dan aktifkan:

```bash
sudo ln -s /var/www/pos-umkm-native/backend/nginx/pos-umkm.conf \
  /etc/nginx/sites-available/pos-umkm-native
sudo ln -s /etc/nginx/sites-available/pos-umkm-native \
  /etc/nginx/sites-enabled/pos-umkm-native
```

Edit `server_name` pada file site menjadi domain yang digunakan. Konfigurasi contoh memakai socket PHP 8.3: `/run/php/php8.3-fpm.sock`. Sesuaikan `fastcgi_pass` di file Nginx jika versi PHP di server berbeda.

```bash
sudo nginx -t
sudo systemctl enable --now php8.3-fpm nginx
sudo systemctl reload nginx
```

Nginx melayani Vue dari `frontend/dist`, meneruskan `/api` ke Laravel, dan melayani gambar dari `backend/public/storage`.

## 5. SSL

Pastikan DNS domain sudah mengarah ke server dan port 80/443 bisa diakses publik. Pasang Certbot beserta plugin Nginx:

```bash
sudo apt install -y certbot python3-certbot-nginx
chmod +x ssl.sh
```

Terbitkan sertifikat:

```bash
./ssl.sh issue pos.example.com admin@example.com
```

Certbot akan mengubah konfigurasi Nginx untuk HTTPS dan mengatur redirect. Cek atau perpanjang sertifikat:

```bash
./ssl.sh status
./ssl.sh renew
```

Aktifkan timer systemd bawaan Certbot bila tersedia:

```bash
sudo systemctl enable --now certbot.timer
```

## 6. Akun Owner dan pemeriksaan

Buat akun Owner dengan password unik:

```bash
cd backend
php artisan pos:create-owner owner@pos.com --name="Owner Toko"
```

Lalu verifikasi:

```bash
curl -I https://pos.example.com/up
```

Jangan gunakan data login contoh `owner@pos.com` / `password` di production.

## Operasional

Deploy ulang setelah perubahan kode:

```bash
cd /var/www/pos-umkm-native
./deploy.sh
sudo systemctl reload php8.3-fpm nginx
```

Log:

```bash
sudo journalctl -u php8.3-fpm -f
sudo tail -f /var/log/nginx/error.log
tail -f backend/storage/logs/laravel.log
```

Backup:

```bash
./backup.sh
./backup.sh list
./backup.sh restore backups/pos-backup-YYYYMMDD-HHMMSS.tar.gz
```

Backup berisi database, foto produk, dan konfigurasi `.env`; file backup hanya tersimpan di `backups/` dan tidak boleh dimasukkan ke Git. Salin backup secara berkala ke lokasi lain yang aman.
Jika database menggunakan SQLite, hentikan server aplikasi sebelum restore agar file database tidak sedang dipakai.

Untuk menjalankan backup terjadwal, tambahkan cron milik user deployment:

```cron
0 2 * * * cd /var/www/pos-umkm-native && ./backup.sh >> /var/log/pos-backup.log 2>&1
```

## Checklist production

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_KEY` terisi
- [ ] Password database dan password akun Owner bukan nilai contoh
- [ ] `DB_CONNECTION=mysql` dan hak akses database hanya untuk aplikasi
- [ ] Port database tidak dibuka di firewall publik
- [ ] `php artisan migrate --force` sukses
- [ ] Health check `/up` memberikan respons sukses
- [ ] HTTPS aktif dan perpanjangan Certbot terjadwal
- [ ] Backup harian disimpan offsite dan proses restore pernah diuji
