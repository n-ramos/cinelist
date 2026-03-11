# =============================================================================
# Stage 1: Build frontend assets
# =============================================================================
FROM node:22-alpine AS node

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY vite.config.js ./
COPY resources/ resources/
COPY public/ public/

RUN npm run build

# =============================================================================
# Stage 2: Install PHP dependencies (no dev)
# =============================================================================
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --no-progress

COPY . .

RUN APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    php artisan package:discover --ansi

# =============================================================================
# Stage 3: Production image (FrankenPHP = PHP 8.5 + Caddy en un seul process)
# =============================================================================
FROM dunglas/frankenphp:1-php8.5-alpine

WORKDIR /app

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    opcache \
    pcntl \
    intl \
    zip \
    redis

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/Caddyfile /etc/caddy/Caddyfile

COPY --from=composer --chown=www-data:www-data /app /app
COPY --from=node --chown=www-data:www-data /app/public/build /app/public/build

RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions \
        storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY --chmod=755 docker/entrypoint.prod.sh /entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
