#!/usr/bin/env bash
set -euo pipefail
umask 077

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKUP_DIR="$ROOT_DIR/backups"
KEEP_DAYS="${BACKUP_KEEP_DAYS:-14}"
STORAGE_DIR="$ROOT_DIR/backend/storage/app/public"
ENV_FILE="$ROOT_DIR/backend/.env"
CMD="${1:-run}"
FILE="${2:-}"
STAGING_DIR=""

cleanup_staging() {
  if [ -n "$STAGING_DIR" ] && [ -d "$STAGING_DIR" ]; then
    rm -rf -- "$STAGING_DIR"
  fi
}

trap cleanup_staging EXIT

usage() {
  cat <<'USAGE'
Native backup/restore:

  ./backup.sh                         Buat backup database + storage + .env
  ./backup.sh list                    Daftar backup
  ./backup.sh restore <file>          Restore setelah konfirmasi
  ./backup.sh purge                   Hapus backup lebih lama dari retention

Retention default 14 hari; ubah dengan BACKUP_KEEP_DAYS.
USAGE
}

get_database_config() {
  (
    cd "$ROOT_DIR/backend"
    php -r '
      require "vendor/autoload.php";
      $app = require "bootstrap/app.php";
      $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
      $driver = config("database.default");
      $connection = config("database.connections." . $driver);
      $values = [
        $driver,
        $connection["database"] ?? "",
        $connection["host"] ?? "",
        $connection["port"] ?? "",
        $connection["username"] ?? "",
        $connection["password"] ?? "",
        $connection["unix_socket"] ?? "",
      ];
      foreach ($values as $value) {
        echo base64_encode((string) $value), PHP_EOL;
      }
      echo "__END__", PHP_EOL;
    '
  )
}

load_database_config() {
  local raw_config
  local -a fields

  if [ ! -f "$ENV_FILE" ] || [ ! -f "$ROOT_DIR/backend/vendor/autoload.php" ]; then
    echo "Error: siapkan backend/.env dan dependency PHP terlebih dahulu." >&2
    exit 1
  fi

  raw_config="$(get_database_config)"
  mapfile -t fields <<< "$raw_config"
  if [ "${#fields[@]}" -ne 8 ] || [ "${fields[7]}" != "__END__" ]; then
    echo "Error: konfigurasi database Laravel tidak terbaca." >&2
    exit 1
  fi

  decode_field() {
    printf '%s' "${fields[$1]}" | base64 --decode
  }

  DB_DRIVER="$(decode_field 0)"
  DB_NAME="$(decode_field 1)"
  DB_HOST="$(decode_field 2)"
  DB_PORT="$(decode_field 3)"
  DB_USERNAME="$(decode_field 4)"
  DB_PASSWORD="$(decode_field 5)"
  DB_SOCKET="$(decode_field 6)"
}

write_mysql_defaults() {
  local defaults_file="$1"
  (
    cd "$ROOT_DIR/backend"
    php -r '
      require "vendor/autoload.php";
      $app = require "bootstrap/app.php";
      $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
      $connection = config("database.connections.mysql");
      $escape = static function ($value): string {
        return str_replace(
          ["\\", "\"", "\n", "\r", "\t"],
          ["\\\\", "\\\"", "\\n", "\\r", "\\t"],
          (string) $value
        );
      };
      $options = [
        "host" => $connection["host"] ?? "127.0.0.1",
        "port" => $connection["port"] ?? "3306",
        "user" => $connection["username"] ?? "",
        "password" => $connection["password"] ?? "",
      ];
      if (!empty($connection["unix_socket"])) {
        $options["socket"] = $connection["unix_socket"];
      }
      $content = "[client]" . PHP_EOL;
      foreach ($options as $key => $value) {
        $content .= $key . "=\"" . $escape($value) . "\"" . PHP_EOL;
      }
      if (file_put_contents($argv[1], $content, LOCK_EX) === false) {
        fwrite(STDERR, "Tidak bisa menulis konfigurasi sementara MySQL." . PHP_EOL);
        exit(1);
      }
      chmod($argv[1], 0600);
    ' "$defaults_file"
  )
}

list_backups() {
  local found=0
  mkdir -p "$BACKUP_DIR"
  printf "%-46s %10s  %s\n" "FILE" "SIZE" "MODIFIED"
  while IFS= read -r -d '' file; do
    found=1
    printf "%-46s %10s  %s\n" "$(basename "$file")" \
      "$(du -h "$file" | cut -f1)" "$(date -r "$file" '+%Y-%m-%d %H:%M')"
  done < <(find "$BACKUP_DIR" -maxdepth 1 -type f -name 'pos-backup-*.tar.gz' -print0 | sort -z)
  if [ "$found" -eq 0 ]; then
    echo "(belum ada backup di $BACKUP_DIR)"
  fi
}

purge_backups() {
  mkdir -p "$BACKUP_DIR"
  find "$BACKUP_DIR" -maxdepth 1 -type f -name 'pos-backup-*.tar.gz' \
    -mtime +"$KEEP_DAYS" -print -delete
}

create_backup() {
  local timestamp output db_archive mysql_defaults
  load_database_config

  timestamp="$(date +%Y%m%d-%H%M%S)"
  STAGING_DIR="$(mktemp -d)"
  output="$BACKUP_DIR/pos-backup-$timestamp.tar.gz"
  mkdir -p "$BACKUP_DIR"
  chmod 700 "$BACKUP_DIR"
  mkdir -p "$STAGING_DIR/storage"
  mysql_defaults=""

  case "$DB_DRIVER" in
    sqlite)
      if [ "$DB_NAME" = ":memory:" ] || [ ! -f "$DB_NAME" ]; then
        echo "Error: file SQLite tidak ditemukan: $DB_NAME" >&2
        return 1
      fi
      if ! command -v sqlite3 >/dev/null 2>&1; then
        echo "Error: pasang sqlite3 untuk membuat backup konsisten dari SQLite." >&2
        return 1
      fi
      echo "Membuat snapshot SQLite..."
      sqlite3 "$DB_NAME" ".backup '$STAGING_DIR/db.sqlite'"
      db_archive="db.sqlite"
      ;;
    mysql|mariadb)
      if ! command -v mysqldump >/dev/null 2>&1; then
        echo "Error: mysqldump belum tersedia di server." >&2
        return 1
      fi
      mysql_defaults="$STAGING_DIR/mysql.cnf"
      write_mysql_defaults "$mysql_defaults"
      echo "Dump database MySQL/MariaDB..."
      mysqldump --defaults-extra-file="$mysql_defaults" \
        --single-transaction --quick --triggers --routines --events \
        --hex-blob --add-drop-table "$DB_NAME" > "$STAGING_DIR/db.sql"
      db_archive="db.sql"
      ;;
    *)
      echo "Error: driver database '$DB_DRIVER' belum didukung untuk backup." >&2
      return 1
      ;;
  esac

  if [ ! -s "$STAGING_DIR/$db_archive" ]; then
    echo "Error: file dump database kosong." >&2
    return 1
  fi

  if [ -d "$STORAGE_DIR" ]; then
    cp -R "$STORAGE_DIR/." "$STAGING_DIR/storage/"
  fi
  cp "$ENV_FILE" "$STAGING_DIR/env"
  {
    echo "created_at : $(date -Is)"
    echo "database   : $DB_NAME"
    echo "driver     : $DB_DRIVER"
    echo "dump_bytes : $(wc -c < "$STAGING_DIR/$db_archive")"
  } > "$STAGING_DIR/manifest.txt"

  tar -czf "$output" -C "$STAGING_DIR" "$db_archive" storage env manifest.txt
  chmod 600 "$output"
  purge_backups >/dev/null
  echo "Backup tersimpan: $output"
}

restore_backup() {
  local manifest_driver mysql_defaults answer
  if [ -z "$FILE" ] || [ ! -f "$FILE" ]; then
    echo "Error: file backup tidak ditemukan: ${FILE:-<belum diisi>}" >&2
    exit 1
  fi

  load_database_config
  STAGING_DIR="$(mktemp -d)"

  if tar -tzf "$FILE" | grep -Eq '(^/|(^|/)\.\.(/|$))'; then
    echo "Error: archive memiliki path yang tidak aman." >&2
    return 1
  fi
  tar -xzf "$FILE" -C "$STAGING_DIR"
  if [ ! -f "$STAGING_DIR/manifest.txt" ]; then
    echo "Error: manifest.txt tidak ditemukan di dalam backup." >&2
    return 1
  fi

  manifest_driver="$(sed -n 's/^driver[[:space:]]*:[[:space:]]*//p' "$STAGING_DIR/manifest.txt")"
  if [ "$manifest_driver" != "$DB_DRIVER" ]; then
    echo "Error: backup menggunakan '$manifest_driver', database saat ini '$DB_DRIVER'." >&2
    return 1
  fi

  echo
  echo "PERINGATAN: restore akan mengganti database '$DB_NAME' dan foto produk."
  echo "File environment saat ini tidak akan ditimpa."
  printf "Ketik RESTORE untuk lanjut: "
  read -r answer
  if [ "$answer" != "RESTORE" ]; then
    echo "Restore dibatalkan."
    return 1
  fi

  if [ "$DB_DRIVER" = "sqlite" ]; then
    if [ ! -f "$STAGING_DIR/db.sqlite" ]; then
      echo "Error: db.sqlite tidak ditemukan di dalam backup." >&2
      return 1
    fi
    cp "$STAGING_DIR/db.sqlite" "$DB_NAME"
  else
    if [ ! -f "$STAGING_DIR/db.sql" ]; then
      echo "Error: db.sql tidak ditemukan di dalam backup." >&2
      return 1
    fi
    mysql_defaults="$STAGING_DIR/mysql.cnf"
    write_mysql_defaults "$mysql_defaults"
    mysql --defaults-extra-file="$mysql_defaults" "$DB_NAME" < "$STAGING_DIR/db.sql"
  fi

  mkdir -p "$STORAGE_DIR"
  find "$STORAGE_DIR" -mindepth 1 -delete
  if [ -d "$STAGING_DIR/storage" ]; then
    cp -R "$STAGING_DIR/storage/." "$STORAGE_DIR/"
  fi

  (cd "$ROOT_DIR/backend" && php artisan optimize:clear)
  echo "Restore selesai. Periksa aplikasi melalui endpoint /up."
}

case "$CMD" in
  run) create_backup ;;
  list) list_backups ;;
  restore) restore_backup ;;
  purge) purge_backups ;;
  help|-h|--help) usage ;;
  *)
    echo "Perintah tidak dikenal: $CMD" >&2
    usage >&2
    exit 2
    ;;
esac
