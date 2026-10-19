#!/usr/bin/env bash
set -euo pipefail

cmd="${1:-}"

case "$cmd" in
  issue)
    DOMAIN="${2:?Usage: ./ssl.sh issue <domain> <email>}"
    EMAIL="${3:?Usage: ./ssl.sh issue <domain> <email>}"

    if ! command -v certbot >/dev/null 2>&1; then
      echo "Error: Certbot belum terpasang. Pasang certbot dan plugin Nginx terlebih dahulu." >&2
      exit 1
    fi

    sudo certbot --nginx --non-interactive --agree-tos \
      --redirect --email "$EMAIL" -d "$DOMAIN"
    ;;
  renew)
    sudo certbot renew
    sudo systemctl reload nginx
    ;;
  status)
    sudo certbot certificates
    ;;
  *)
    cat <<'USAGE'
SSL Let's Encrypt menggunakan Certbot native:

  ./ssl.sh issue <domain> <email>   Terbitkan sertifikat dan aktifkan HTTPS
  ./ssl.sh renew                    Perpanjang sertifikat dan reload Nginx
  ./ssl.sh status                   Tampilkan sertifikat terpasang
USAGE
    exit 1
    ;;
esac
