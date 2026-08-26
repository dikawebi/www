# syntax=docker/dockerfile:1

# ============================================
# Base PHP dengan semua ekstensi yang dibutuhkan
# ============================================
FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
        bash \
        icu-libs \
        libpq \
        libzip \
        libpng \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        intl \
        mbstring \
        zip \
        gd \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# ============================================
# Stage 1: Build frontend assets (Vite)
# ============================================
FROM node:22-alpine AS assets

WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY vite.config.js ./
COPY resources/ resources/
RUN npm run build

# ============================================
# Stage 2: Composer dependencies
# ============================================
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --no-progress

COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY database/ database/
COPY public/ public/
COPY resources/ resources/
COPY routes/ routes/
COPY artisan .

RUN composer dump-autoload --optimize --no-dev --no-scripts

# ============================================
# Stage 3: Production runtime (PHP-FPM)
# ============================================
FROM base AS app

WORKDIR /var/www/html

# Konfigurasi PHP produksi
COPY .docker/php/opcache.ini "$PHP_INI_DIR"/conf.d/opcache.ini
COPY .docker/php/uploads.ini "$PHP_INI_DIR"/conf.d/uploads.ini

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /var/www/html/public/build ./public/build

RUN chmod +x .docker/php/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT [".docker/php/entrypoint.sh"]
CMD ["php-fpm"]
