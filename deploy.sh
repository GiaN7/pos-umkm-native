#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"

if [ ! -f "$ROOT_DIR/backend/.env" ]; then
  echo "Error: backend/.env belum dibuat. Salin dari backend/.env.example dan isi konfigurasi production." >&2
  exit 1
fi

cd "$ROOT_DIR/backend"
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan storage:link --force
php artisan optimize

cd "$ROOT_DIR/frontend"
npm ci
npm run build

echo
echo "Deploy selesai. Reload PHP-FPM dan Nginx jika konfigurasi atau proses berubah."
