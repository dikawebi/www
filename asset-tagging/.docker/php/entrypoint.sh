#!/bin/bash
set -e

ROLE="${CONTAINER_ROLE:-app}"

# Tunggu PostgreSQL siap
if [ -n "$DB_HOST" ]; then
  echo "Menunggu database di ${DB_HOST}:${DB_PORT:-5432}..."
  until php -r 'exit(@fsockopen(getenv("DB_HOST"), (int)(getenv("DB_PORT") ?: 5432), $e, $s, 3) ? 0 : 1);' 2>/dev/null; do
    sleep 2
  done
  echo "Database siap."
fi

if [ ! -f /var/www/html/.env ]; then
  cp /var/www/html/.env.example /var/www/html/.env
  php artisan key:generate --force
fi

# Sync APP_URL dari Docker env_file ke .env
if [ -n "$APP_URL" ]; then
  if grep -q "^APP_URL=" /var/www/html/.env; then
    sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" /var/www/html/.env
  else
    echo "APP_URL=${APP_URL}" >> /var/www/html/.env
  fi
fi

mkdir -p \
  storage/framework/{cache,data,sessions,testing,views} \
  storage/logs \
  bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan storage:link || true

case "$ROLE" in
  app)
    php artisan migrate --force
    ;;
  queue)
    exec php artisan queue:work --tries=1 --timeout=90
    ;;
esac

exec "$@"
