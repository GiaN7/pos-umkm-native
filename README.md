# POS UMKM — Native

Sistem Point of Sale untuk UMKM. Ini adalah salinan native dari project POS UMKM: Laravel 12 berjalan langsung dengan PHP, Vue 3 berjalan dengan Vite, dan tidak membutuhkan Docker, Redis, atau server database untuk development.

## Stack

- Backend: Laravel 12, PHP 8.2+
- Frontend: Vue 3, TypeScript, Vite
- Database lokal: SQLite
- Database production yang disarankan: MySQL/MariaDB
- Web server production: Nginx + PHP-FPM

Fitur aplikasi tetap mencakup login Owner/Kasir, produk dan kategori, pelanggan, penjualan, stok, dashboard, laporan, serta cetak struk Bluetooth melalui browser yang mendukung Web Bluetooth.

## Persyaratan

- PHP 8.2+ dengan ekstensi `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `pdo_sqlite`, `tokenizer`, dan `xml`
- Composer
- Node.js 18+ dan npm

Tidak perlu memasang Docker, MySQL, atau Redis untuk menjalankan aplikasi secara lokal. SQLite dipakai secara default.

## Setup lokal

```bash
cd pos-umkm-native
chmod +x setup.sh dev.sh
./setup.sh
./dev.sh
```

`setup.sh` menyiapkan `.env`, SQLite, dependency PHP/Node, application key, migrasi, symlink storage, dan build frontend. `dev.sh` menjalankan Laravel dan Vite sekaligus.

- Frontend: http://localhost:5173
- Backend API: http://127.0.0.1:8000/api
- Health check backend: http://127.0.0.1:8000/up

Frontend meneruskan request `/api` dan `/storage` ke backend lokal melalui konfigurasi Vite. Untuk menghentikan server, tekan `Ctrl+C`.

Jika ingin menjalankan proses sendiri di dua terminal:

```bash
# Terminal 1
cd backend
php artisan serve --host=127.0.0.1 --port=8000
```

```bash
# Terminal 2
cd frontend
npm run dev -- --host 127.0.0.1
```

## Akun awal

Seeder berisi akun contoh `owner@pos.com` dengan password `password`, dan hanya dijalankan jika diminta. Jangan gunakan akun contoh di production.

Untuk membuat akun Owner dengan password sendiri:

```bash
cd backend
php artisan pos:create-owner owner@pos.com --name="Owner Toko"
```

Masukkan password saat diminta. Perintah ini juga bisa mereset akun dengan email yang sama setelah konfirmasi.

## Build frontend

```bash
cd frontend
npm run build
```

Hasil build berada di `frontend/dist/`. Petunjuk instalasi production dengan Nginx, PHP-FPM, MySQL/MariaDB, dan SSL tersedia di [DEPLOYMENT.md](./DEPLOYMENT.md).

## Testing

```bash
cd backend
composer test
```

```bash
cd frontend
npx vitest run
```

## Dokumentasi terkait

- [DEPLOYMENT.md](./DEPLOYMENT.md) — instalasi native di server
- [pos-system-plan.md](./pos-system-plan.md) — rencana sistem dan fitur
- [backend/README.md](./backend/README.md) — dokumentasi Laravel
