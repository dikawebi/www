#!/bin/sh

# Optimize configuration and routes
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components

# Run database migrations safely
php artisan migrate --force
