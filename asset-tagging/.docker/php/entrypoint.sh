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

# Sync env vars dari Docker env_file ke .env
# Skip APP_NAME karena berisi spasi, biarkan dari .env.example
php -r '
$keys = ["APP_ENV","APP_KEY","APP_DEBUG","APP_URL","APP_LOCALE","APP_FALLBACK_LOCALE","APP_FAKER_LOCALE","APP_MAINTENANCE_DRIVER","BCRYPT_ROUNDS","LOG_CHANNEL","LOG_STACK","LOG_DEPRECATIONS_CHANNEL","LOG_LEVEL","DB_CONNECTION","DB_HOST","DB_PORT","DB_DATABASE","DB_USERNAME","DB_PASSWORD","DB_SSLMODE","SESSION_DRIVER","SESSION_LIFETIME","SESSION_ENCRYPT","SESSION_PATH","SESSION_DOMAIN","BROADCAST_CONNECTION","FILESYSTEM_DISK","QUEUE_CONNECTION","CACHE_STORE","MAIL_MAILER","MAIL_FROM_ADDRESS","MAIL_FROM_NAME","VITE_APP_NAME"];
$envFile = "/var/www/html/.env";
$lines = file_exists($envFile) ? file($envFile, FILE_IGNORE_NEW_LINES) : [];
foreach ($keys as $key) {
    $val = getenv($key);
    if ($val === false) continue;
    if (str_contains($val, " ")) $val = "\"" . $val . "\"";
    $found = false;
    foreach ($lines as &$line) {
        if (preg_match("/^" . preg_quote($key) . "=/" , $line)) {
            $line = $key . "=" . $val;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $lines[] = $key . "=" . $val;
    }
}
file_put_contents($envFile, implode("\n", $lines) . "\n");
echo "Done syncing .env\n";
'

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
