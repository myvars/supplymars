# ------------ Php Base Stage ------------
FROM php:8.5.2-fpm AS php-base

# Install system dependencies for extensions
RUN apt-get update && apt-get install -y \
    git cron unzip procps \
    libicu-dev libzip-dev zlib1g-dev \
    libjpeg62-turbo-dev libpng-dev libfreetype6-dev libwebp-dev libxpm-dev \
    libxslt1-dev \
 && docker-php-ext-configure gd \
      --with-freetype \
      --with-jpeg \
      --with-webp \
      --with-xpm \
 && docker-php-ext-install \
      intl \
      pdo_mysql \
      zip \
      bcmath \
      gd \
      xsl \
      sockets \
 && pecl install redis-6.3.0 \
 && docker-php-ext-enable redis \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set up PHP configuration directory
ENV PHP_INI_SCAN_DIR="$PHP_INI_DIR/conf.d:$PHP_INI_DIR/app.conf.d"

# Copy PHP configuration, wait-for-it script, and entrypoint script
COPY --link docker/php/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Configure PHP-FPM to listen on port 9000
RUN sed -i 's/^listen = .*/listen = 9000/' /usr/local/etc/php-fpm.d/www.conf

# Make scripts executable
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set the working directory
WORKDIR /app


# ------------ Php Dev Stage ------------
FROM php-base AS php-dev

ENV APP_ENV=dev
ENV APP_DEBUG=1
ENV XDEBUG_MODE=off

# Install and enable Xdebug, set dev stage defaults
RUN set -eux; \
    pecl install xdebug && docker-php-ext-enable xdebug && \
    mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini";

# Ensure entrypoint script is used
ENTRYPOINT ["docker-entrypoint.sh"]


# ------------ Php Production Builder Stage ------------
FROM php-base AS php-prod-builder

# Prod-builder stage defaults
ENV APP_ENV=prod

# Copy only composer files
COPY --link composer.* symfony.* ./

# Install dependencies (caches unless composer files change)
RUN composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress --no-interaction

# Now copy everything else
COPY --link . /app

# Clean up dev/test files, set prod php.ini, install PHP dependencies, prepare directories for cache and logs.
RUN set -eux; \
    rm -Rf docker/ tests/ Docs/ && \
    mkdir -p var/cache/prod var/log public/assets assets/vendor && \
    chown -R www-data:www-data var public/assets assets /usr/local/etc/php/conf.d && \
    chmod -R 775 var public/assets assets && \
    composer dump-autoload --classmap-authoritative --no-dev && \
    composer dump-env prod;

# Switch to www-data for cache building (safe, non-root)
USER www-data

# Build assets and compile cache
RUN set -eux; \
    php bin/console tailwind:build --minify && \
    php bin/console importmap:install --no-interaction && \
    php bin/console asset-map:compile --env=prod --no-debug;


# ------------ Php Production Stage ------------
FROM php-base AS php-prod

# Prod stage defaults
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Set prod stage defaults
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini";

# Enable OPcache for production
COPY --link docker/php/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/

# Copy built Symfony assets from the builder stage
COPY --from=php-prod-builder /app /app

# Ensure entrypoint script is used
ENTRYPOINT ["docker-entrypoint.sh"]


# ---- Cron Production Stage ----
# Note: messenger uses php-dev/php-prod directly (no separate stage needed)
# Note: cron-dev uses php-dev directly (no separate stage needed)
FROM php-prod AS cron-prod

# Copy crontab file for the cron service
COPY --link docker/php/cron/prod-crontab /etc/cron.d/app-cron

# Ensure the crontab file has the correct permissions
RUN chmod 0644 /etc/cron.d/app-cron;


# ---- NGINX Dev Stage ----
FROM nginx:alpine AS nginx-dev


# ---- NGINX Production Stage ----
FROM nginx:alpine AS nginx-prod

# prod stage defaults
COPY --link docker/nginx/conf.d/prod.conf /etc/nginx/conf.d/default.conf

# Copy built Symfony assets from PHP prod stage
COPY --from=php-prod /app/public /app/public
