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

# Run package discovery with a temporary key (doesn't need a real one)
RUN APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    php artisan package:discover --ansi

# =============================================================================
# Stage 3: Production image (FrankenPHP)
# =============================================================================
FROM dunglas/frankenphp:latest-php8.4-alpine

WORKDIR /app

# Install required PHP extensions
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    opcache \
    pcntl \
    intl \
    zip \
    redis

# PHP & OPcache config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Caddy config
COPY docker/Caddyfile /etc/caddy/Caddyfile

# Copy application (from composer stage, includes vendor/)
COPY --from=composer --chown=www-data:www-data /app /app

# Copy compiled frontend assets
COPY --from=node --chown=www-data:www-data /app/public/build /app/public/build

# Ensure writable dirs have correct permissions
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions \
        storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint
COPY --chmod=755 docker/entrypoint.prod.sh /entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
