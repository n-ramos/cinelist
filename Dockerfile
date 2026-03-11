# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1-php8.5-alpine AS base

# Installation des extensions PHP nécessaires
RUN install-php-extensions \
    pcntl \
    pdo_pgsql \
    pgsql \
    redis \
    opcache \
    intl \
    zip \
    bcmath \
    gd

# Configuration PHP pour la production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY docker/php/php.ini $PHP_INI_DIR/conf.d/custom.ini

WORKDIR /app

# -------------------------------------------------------------------
# Stage: composer dependencies
# -------------------------------------------------------------------
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative

# -------------------------------------------------------------------
# Stage: frontend assets
# -------------------------------------------------------------------
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# -------------------------------------------------------------------
# Stage: production
# -------------------------------------------------------------------
FROM base AS production

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

# Créer un utilisateur non-root
RUN addgroup -g 1000 app && \
    adduser -u 1000 -G app -s /bin/sh -D app

WORKDIR /app

# Copier le Caddyfile
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.prod.sh /usr/local/bin/docker-entrypoint

# Copier l'application
COPY --chown=app:app . .
COPY --chown=app:app --from=vendor /app/vendor ./vendor
COPY --chown=app:app --from=frontend /app/public/build ./public/build

# Créer les dossiers nécessaires
RUN mkdir -p storage/framework/{sessions,views,cache} \
    storage/logs \
    bootstrap/cache && \
    chown -R app:app storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache && \
    chmod +x /usr/local/bin/docker-entrypoint

USER app

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8000/up || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
