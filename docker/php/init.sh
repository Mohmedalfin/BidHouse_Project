#!/bin/bash
set -e

APP_DIR="/var/www/html"
cd "${APP_DIR}"

echo "==> Masuk ke folder aplikasi: ${APP_DIR}"

# 1) Pastikan project Laravel ada
if [ ! -f "composer.json" ]; then
  echo "ERROR: composer.json tidak ditemukan di ${APP_DIR}."
  echo "Pastikan volumes mount benar (misal ./:/var/www/html)."
  exit 1
fi

# 2) Pastikan .env ada
if [ ! -f ".env" ]; then
  if [ -f ".env.docker" ]; then
    echo "==> .env belum ada, menyalin dari .env.docker..."
    cp .env.docker .env
  elif [ -f ".env.example" ]; then
    echo "==> .env belum ada, menyalin dari .env.example..."
    cp .env.example .env
  else
    echo "ERROR: .env/.env.docker/.env.example tidak ditemukan."
    exit 1
  fi
fi

# helper: replace/add KEY=VALUE
set_env () {
  local key="$1"
  local val="$2"
  if grep -qE "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${val}|" .env
  else
    echo "${key}=${val}" >> .env
  fi
}

# 3) Sinkron DB env dari docker-compose (jika disediakan)
[ -n "${DB_CONNECTION}" ] && set_env "DB_CONNECTION" "${DB_CONNECTION}"
[ -n "${DB_HOST}" ]       && set_env "DB_HOST" "${DB_HOST}"
[ -n "${DB_PORT}" ]       && set_env "DB_PORT" "${DB_PORT}"
[ -n "${DB_DATABASE}" ]   && set_env "DB_DATABASE" "${DB_DATABASE}"
[ -n "${DB_USERNAME}" ]   && set_env "DB_USERNAME" "${DB_USERNAME}"
if [ "${DB_PASSWORD+x}" = "x" ]; then
  set_env "DB_PASSWORD" "${DB_PASSWORD}"
fi

echo "==> DB env:"
grep -E "^DB_" .env || true

# 4) Permission yang dibutuhkan Laravel
echo "==> Menyiapkan permission storage & cache..."
mkdir -p storage/logs bootstrap/cache
touch storage/logs/laravel.log
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 5) APP_ENV (default local)
APP_ENV=$(grep -E "^APP_ENV=" .env | cut -d '=' -f2 | tr -d '\r' || true)
APP_ENV=${APP_ENV:-local}
echo "==> APP_ENV: ${APP_ENV}"

# 6) Composer install hanya jika vendor belum ada (biar start cepat)
echo "==> Cek vendor..."
if [ ! -f "vendor/autoload.php" ]; then
  echo "==> vendor belum ada, menjalankan composer install..."
  composer validate --no-check-publish || true
  if [ "${APP_ENV}" = "production" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction
  else
    composer install --no-interaction
  fi
else
  echo "==> vendor sudah ada, skip composer install."
fi

# 7) Generate APP_KEY kalau belum ada
if ! grep -qE "^APP_KEY=base64:" .env; then
  echo "==> APP_KEY belum ada, generate..."
  php artisan key:generate --force
fi

# 8) (Opsional) tunggu DB siap + migrate hanya jika RUN_MIGRATIONS=true
RUN_MIGRATIONS=${RUN_MIGRATIONS:-false}
if [ "${RUN_MIGRATIONS}" = "true" ]; then
  echo "==> RUN_MIGRATIONS=true, menunggu DB siap dan migrate..."

  if [ -n "${DB_HOST}" ]; then
    for i in {1..30}; do
      if php -r "try { new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo 'ok'; } catch (Exception \$e) { exit(1);}"; then
        echo ""
        echo "==> DB siap."
        break
      fi
      echo -n "."
      sleep 1
    done
    echo ""
  fi

  php artisan migrate --force
else
  echo "==> Skip migrate otomatis (set RUN_MIGRATIONS=true untuk menjalankan)."
fi

# 9) Cache (production) / clear (dev) — dibuat ringan
if [ "${APP_ENV}" = "production" ]; then
  echo "==> Production: cache config/route/view..."
  php artisan config:cache || true
  php artisan route:cache  || true
  php artisan view:cache   || true
else
  echo "==> Dev: clear cache..."
  php artisan optimize:clear || true
fi

# 10) Jalankan Apache
echo "==> Menjalankan Apache..."
exec apache2-foreground
