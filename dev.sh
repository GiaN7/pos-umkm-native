#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"

if [ ! -f "$ROOT_DIR/backend/.env" ] || [ ! -f "$ROOT_DIR/backend/vendor/autoload.php" ]; then
  echo "Project belum disiapkan. Jalankan ./setup.sh terlebih dahulu." >&2
  exit 1
fi

if [ ! -d "$ROOT_DIR/frontend/node_modules" ]; then
  echo "Dependency frontend belum tersedia. Jalankan ./setup.sh terlebih dahulu." >&2
  exit 1
fi

(cd "$ROOT_DIR/backend" && exec php artisan serve --host=127.0.0.1 --port=8000) &
BACKEND_PID=$!
(cd "$ROOT_DIR/frontend" && exec npm run dev -- --host 127.0.0.1 --port 5173) &
FRONTEND_PID=$!

cleanup() {
  kill "$BACKEND_PID" "$FRONTEND_PID" 2>/dev/null || true
  wait "$BACKEND_PID" "$FRONTEND_PID" 2>/dev/null || true
}

trap cleanup EXIT INT TERM
wait -n "$BACKEND_PID" "$FRONTEND_PID"
