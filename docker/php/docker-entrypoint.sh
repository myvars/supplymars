#!/bin/bash
set -euo pipefail

echo "[docker-entrypoint] Running in environment: $APP_ENV"

if [ "$APP_ENV" != "prod" ] && [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if [ "$APP_ENV" != "prod" ]; then
    php bin/console importmap:install --no-interaction || echo "Importmap install failed"
    php bin/console tailwind:build --minify || echo "Tailwind build failed"
    php bin/console asset-map:compile || echo "Asset map compile failed"
else
    echo "[docker-entrypoint] Skipping asset builds in production."
fi

php bin/console cache:clear --env="${APP_ENV:-dev}" || echo "Cache clear failed"

# Early exit for test env
if [[ "$APP_ENV" == "test" ]]; then
  echo "[docker-entrypoint] Test environment detected — skipping runtime setup"
  exec "$@"
fi

echo "[docker-entrypoint] Database is ready."

if [ "$( find ./migrations -iname '*.php' -print -quit )" ]; then
  echo "[docker-entrypoint] Running database migrations..."
  php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing
else
  echo "[docker-entrypoint] No migration files found — skipping migrations."
fi

echo "[docker-entrypoint] Setting up Messenger transport..."
php bin/console messenger:setup-transports --no-interaction || true

echo "[docker-entrypoint] Entrypoint complete"
exec docker-php-entrypoint "$@"