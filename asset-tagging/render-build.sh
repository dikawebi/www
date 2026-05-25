#!/usr/bin/env bash
# exit on error
set -o errexit

# Install dependensi
composer install --no-dev --optimize-autoloader

# Jalankan optimasi Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan migrasi database otomatis saat deploy
php artisan migrate --force
