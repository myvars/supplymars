# ------------ Base Stage ------------
FROM php:8.3-fpm AS php-base

# Install system dependencies for extensions
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev zlib1g-dev \
    libjpeg-dev libpng-dev libfreetype6-dev libwebp-dev libxpm-dev libxslt1-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install \
        intl \
        pdo_mysql \
        zip \
        opcache \
        bcmath \
        gd \
        xsl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set up PHP configuration directory
ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

# Copy application-specific PHP configuration
COPY --link docker/php/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/

# Copy wait-for-it script
COPY docker/php/wait-for-it.sh /usr/local/bin/wait-for-it
RUN chmod +x /usr/local/bin/wait-for-it

# Copy entrypoint scripts
COPY --link docker/php/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set the working directory
WORKDIR /app

# Ensure entrypoint script is used
ENTRYPOINT ["docker-entrypoint.sh"]

# ------------ Dev Stage ------------
FROM php-base AS php-dev

# Dev stage defaults (can be overridden by Compose or ENV files)
ENV APP_ENV=dev
ENV XDEBUG_MODE=off

# Development stage defaults
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Install and enable Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Copy development-specific PHP configuration
COPY --link docker/php/conf.d/20-app.dev.ini $PHP_INI_DIR/app.conf.d/

# no extra steps — entrypoint handles setup

# ------------ Prod Stage ------------
FROM php-base AS php-prod

# Production stage defaults (can be overridden by Compose or ENV files)
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Production stage defaults
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Enable OPcache for production
COPY --link docker/php/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/

# Copy dependency descriptors *first* (enables cache for vendor install)
COPY --link composer.* symfony.* ./

# Install PHP dependencies (production only, no dev)
RUN set -eux; \
	composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress --no-interaction

# copy sources
COPY --link . /app

# Clean up dev/test files (optional but nice)
RUN rm -Rf docker/ tests/

# Prepare directories for cache and logs
RUN set -eux; \
    mkdir -p var/cache/prod var/log public/assets assets/vendor; \
    chown -R www-data:www-data var public/assets assets; \
    chmod -R 775 var public/assets assets; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    composer dump-env prod;

# Switch to www-data for cache building (safe, non-root)
USER www-data

# Build assets and compile cache (using fake Database URL)
RUN set -eux; \
    DATABASE_URL="sqlite:///:memory:" php bin/console tailwind:build --minify; \
    DATABASE_URL="sqlite:///:memory:" php bin/console importmap:install; \
    DATABASE_URL="sqlite:///:memory:" php bin/console asset-map:compile --env=prod --no-debug;


# ---- NGINX dev stage ----
FROM nginx:alpine AS nginx-dev

# Shared config for all environments


# ---- NGINX prod stage ----
FROM nginx-dev AS nginx-prod

# prod stage defaults
COPY --link docker/nginx/conf.d/prod.conf /etc/nginx/conf.d/default.conf

# Copy built Symfony assets from PHP prod stage
COPY --from=php-prod /app/public /app/public