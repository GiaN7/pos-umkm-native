#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"

for command in php composer node npm; do
  if ! command -v "$command" >/dev/null 2>&1; then
    echo "Error: '$command' belum terpasang atau tidak ada di PATH." >&2
    exit 1
  fi
done

if ! php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);'; then
  echo "Error: aplikasi membutuhkan PHP 8.2 atau lebih baru." >&2
  exit 1
fi

if ! node -e 'process.exit(Number(process.versions.node.split(".")[0]) >= 18 ? 0 : 1)'; then
  echo "Error: frontend membutuhkan Node.js 18 atau lebih baru." >&2
  exit 1
fi

for extension in pdo_sqlite mbstring xml gd; do
  if ! php -m | grep -iq "^${extension}$"; then
    echo "Error: ekstensi PHP '$extension' belum aktif." >&2
    exit 1
  fi
done

cd "$ROOT_DIR/backend"

if [ ! -f .env ]; then
  cp .env.example .env
fi

mkdir -p database
touch database/database.sqlite

composer install

if ! grep -Eq '^APP_KEY=base64:.+' .env; then
  php artisan key:generate
fi

php artisan migrate
php artisan storage:link --force

cd "$ROOT_DIR/frontend"
npm ci
npm run build

echo
echo "Setup selesai. Jalankan ./dev.sh untuk memulai aplikasi."
