#!/bin/bash
set -e
cd "$(dirname "$0")/.."

if [ "$APP_ENV" != "test" ]; then
  echo "[run-tests] Refusing to run: APP_ENV is not 'test' (got '$APP_ENV')"
  exit 1
fi

echo "[run-tests] Resetting test database..."
php bin/console doctrine:database:drop --if-exists --force --env=test
php bin/console doctrine:database:create --if-not-exists --env=test
php bin/console doctrine:schema:create --env=test
#if ! ls migrations/*.php >/dev/null 2>&1; then
#  echo "[run-tests] No migration files found — skipping migrate step."
#else
#    php bin/console doctrine:migrations:migrate --no-interaction --env=test
#fi

php bin/console messenger:setup-transports --no-interaction --env=test

echo "[run-tests] Running tests..."
exec php bin/phpunit "$@"
