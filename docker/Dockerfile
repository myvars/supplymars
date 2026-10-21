# One image for the web server and the Messenger worker: the php service runs FrankenPHP (the image's
# default command), and messenger runs messenger:consume. See Docs/adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md.

# ------------ Php Base Stage ------------
FROM dunglas/frankenphp:1.13.2-php8.5.12 AS php-base

# System tools (mysqldump for the backup command) and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip procps default-mysql-client \
 && install-php-extensions \
      intl \
      pdo_mysql \
      zip \
      bcmath \
      gd \
      xsl \
      redis-6.3.0 \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set up PHP configuration directory
ENV PHP_INI_SCAN_DIR="$PHP_INI_DIR/conf.d:$PHP_INI_DIR/app.conf.d"

# Copy PHP configuration, the Caddyfile and the entrypoint script
COPY --link docker/php/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile
COPY --link --chmod=755 docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Set the working directory
WORKDIR /app

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]


# ------------ Php Dev Stage ------------
FROM php-base AS php-dev

ENV APP_ENV=dev
ENV APP_DEBUG=1
ENV XDEBUG_MODE=off

# Install and enable Xdebug, set dev stage defaults
RUN set -eux; \
    install-php-extensions xdebug && \
    mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini";


# ------------ Php Production Builder Stage ------------
FROM php-base AS php-prod-builder

# Prod-builder stage defaults
ENV APP_ENV=prod

# Copy only composer files. Named one by one so the build fails if the lock file is missing,
# rather than installing the newest allowed versions instead of the tested ones
COPY --link composer.json composer.lock symfony.lock ./

# Install dependencies (caches unless composer files change). validate fails on a lock file that no
# longer matches composer.json, which install would only warn about
RUN composer validate --no-check-all --no-check-publish && \
    composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress --no-interaction

# Now copy everything else
COPY --link . /app

# Remove docker/ (only the Dockerfile reads it), install PHP dependencies, prepare directories for cache and logs.
RUN set -eux; \
    rm -Rf docker/ && \
    mkdir -p var/cache/prod var/log public/assets assets/vendor && \
    chown -R www-data:www-data var public/assets assets && \
    chmod -R 775 var public/assets assets && \
    composer dump-autoload --classmap-authoritative --no-dev && \
    composer dump-env prod;

# Switch to www-data for cache building (safe, non-root)
USER www-data

# Build assets, compile cache, and warm the Symfony container (generates preload file)
# importmap:install comes first: the Tailwind build imports CSS from assets/vendor/. The Tailwind
# binary (about 105 MB) is deleted after the build; the built CSS stays.
# PHP 8.5 workaround: see the preload comment in docker/php/docker-entrypoint.sh.
RUN set -eux; \
    php bin/console importmap:install --no-interaction && \
    php bin/console tailwind:build --minify && \
    php bin/console asset-map:compile --env=prod --no-debug && \
    php bin/console cache:warmup --env=prod --no-debug && \
    sed -i '/Preloader::preload/d' var/cache/prod/App_KernelProdContainer.preload.php && \
    rm -rf var/tailwind/v*;


# ------------ Php Production Stage ------------
FROM php-base AS php-prod

# Prod stage defaults
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Production php.ini and OPcache settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link docker/php/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/

# PHP runs as www-data, as the PHP-FPM workers did. It needs Caddy's data and config directories; the
# PHP configuration stays root's. The frankenphp binary can bind port 80 without root.
RUN chown -R www-data:www-data /data/caddy /config/caddy

# Copy built Symfony assets from the builder stage
COPY --from=php-prod-builder /app /app

USER www-data


# ---- Playground Reset Stage ----
FROM debian:bookworm-slim AS playground-reset

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    default-mysql-client \
    redis-tools \
    curl \
    cron \
    procps \
    unzip \
    && curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o /tmp/awscli.zip \
    && unzip -q /tmp/awscli.zip -d /tmp \
    && /tmp/aws/install \
    && rm -rf /tmp/aws /tmp/awscli.zip \
    && apt-get remove -y unzip \
    && apt-get autoremove -y \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --link scripts/playground-reset.sh /usr/local/bin/playground-reset.sh
COPY --link scripts/playground-redact-staff.sql /usr/local/bin/playground-redact-staff.sql
RUN chmod +x /usr/local/bin/playground-reset.sh

COPY --link docker/php/cron/reset-crontab /etc/cron.d/reset-cron
RUN chmod 0644 /etc/cron.d/reset-cron

CMD ["cron", "-f"]

