#!/bin/bash
# Runs the checks CI runs, except prod-config, which needs Docker on the host (make check-prod-config).
# With no arguments, runs them all; otherwise only the steps named.
#   ./scripts/check.sh               every step
#   ./scripts/check.sh phpstan lint  only those
# Run it through `make check` or `make check-<step>`, which use the php container.
set -e
cd "$(dirname "$0")/.."

STEPS=(cs rector phpstan lint audit migrations tests)

if [ "$APP_ENV" != "test" ]; then
  echo "[check] Refusing to run: APP_ENV is not 'test' (got '$APP_ENV')"
  exit 1
fi

step_cs() {
  vendor/bin/php-cs-fixer fix --dry-run --diff
}

step_rector() {
  vendor/bin/rector process --dry-run --no-progress-bar
}

step_phpstan() {
  # PHPStan reads the dev container (phpstan.dist.neon)
  php bin/console cache:warmup --env=dev --quiet
  vendor/bin/phpstan analyse --no-progress --memory-limit=512M
}

step_lint() {
  php bin/console lint:container
  php bin/console lint:container --env=dev
  php bin/console lint:twig templates/
  php bin/console lint:yaml config/ --parse-tags
}

step_audit() {
  composer audit --no-interaction
}

# Builds a database from the migrations alone and checks it matches the entities.
# Uses its own database (app_test_migrations), so the test database is untouched.
step_migrations() {
  export TEST_TOKEN=_migrations
  php bin/console doctrine:database:drop --if-exists --force --quiet
  php bin/console doctrine:database:create --quiet
  php bin/console doctrine:migrations:migrate --no-interaction
  php bin/console doctrine:schema:validate
  php bin/console doctrine:database:drop --force --quiet
  unset TEST_TOKEN
}

step_tests() {
  ./scripts/run-tests.sh
}

if [ $# -eq 0 ]; then
  set -- "${STEPS[@]}"
fi

for step in "$@"; do
  if ! declare -F "step_$step" > /dev/null; then
    echo "[check] Unknown step '$step'. Steps: ${STEPS[*]}"
    exit 1
  fi
done

for step in "$@"; do
  echo "[check] === $step ==="
  "step_$step"
done

echo "[check] All passed: $*"
