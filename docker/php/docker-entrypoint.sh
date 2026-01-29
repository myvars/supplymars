#!/bin/bash
set -euo pipefail

echo "[docker-entrypoint] Running in environment: $APP_ENV"

# Configure session for production
if [ "${APP_ENV}" = "prod" ] && [ -n "${REDIS_SESSION_DSN:-}" ]; then
  echo "session.save_path = \"${REDIS_SESSION_DSN}\"" > /usr/local/etc/php/conf.d/99-session-redis.ini
fi

if [ "$APP_ENV" != "prod" ]; then
    echo "[docker-entrypoint] Installing composer dependencies..."
    if [ ! -d vendor ]; then
        composer install --no-interaction --prefer-dist
    fi
    # Build assets (skip for messenger/cron containers that don't serve HTTP)
    if [ "${SKIP_ASSET_BUILD:-false}" != "true" ]; then
        php bin/console importmap:install --no-interaction || echo "Importmap install failed"
        php bin/console tailwind:build --minify || echo "Tailwind build failed"
        php bin/console asset-map:compile || echo "Asset map compile failed"
    else
        echo "[docker-entrypoint] Skipping asset builds (SKIP_ASSET_BUILD=true)"
    fi
else
    echo "[docker-entrypoint] Skipping composer install/asset builds for production"
fi

# Clear cache
php -d memory_limit=256M bin/console cache:clear --env="${APP_ENV:-dev}" || echo "Cache clear failed"

# Early exit for test env
if [ "$APP_ENV" = "test" ]; then
  echo "[docker-entrypoint] Test environment detected — skipping runtime setup"
  exec "$@"
fi

echo "[docker-entrypoint] Database is ready"

# Run migrations if any exist (only on designated leader container to avoid race conditions)
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  if [ -d ./migrations ] && [ "$( find ./migrations -iname '*.php' -print -quit )" ]; then
    echo "[docker-entrypoint] Running database migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
  else
    echo "[docker-entrypoint] No migration files found — skipping migrations"
  fi
else
  echo "[docker-entrypoint] Skipping migrations (RUN_MIGRATIONS!=true)"
fi

# Setup Messenger transport (only on designated leader container)
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "[docker-entrypoint] Setting up Messenger transport..."
  php bin/console messenger:setup-transports --no-interaction || echo "Messenger setup failed (non-fatal)"
else
  echo "[docker-entrypoint] Skipping messenger setup (RUN_MIGRATIONS!=true)"
fi

echo "[docker-entrypoint] Entrypoint complete"
echo "[docker-entrypoint] Command to exec: $@"
exec docker-php-entrypoint "$@"
