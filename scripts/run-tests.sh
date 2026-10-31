#!/bin/bash
# Runs the suite with ParaTest: one PHPUnit process per CPU, each with its own database.
# ParaTest gives each process a TEST_TOKEN (1..N), which doctrine.yaml appends to the
# test database name (app_test1, app_test2, ...). TEST_PROCESSES overrides the count.
set -e
cd "$(dirname "$0")/.."

if [ "$APP_ENV" != "test" ]; then
  echo "[run-tests] Refusing to run: APP_ENV is not 'test' (got '$APP_ENV')"
  exit 1
fi

PROCESSES=${TEST_PROCESSES:-$(getconf _NPROCESSORS_ONLN)}
if ! [[ $PROCESSES =~ ^[1-9][0-9]*$ ]]; then
  echo "[run-tests] TEST_PROCESSES must be a number of processes (got '$PROCESSES')"
  exit 1
fi

reset_database() {
  export TEST_TOKEN=$1
  php bin/console doctrine:database:drop --if-exists --force --quiet
  php bin/console doctrine:database:create --quiet
  php bin/console doctrine:schema:create --quiet
  php bin/console messenger:setup-transports --no-interaction --quiet
}

echo "[run-tests] Resetting $PROCESSES test databases..."
# Warm the cache first, so the parallel resets don't all build it at once
php bin/console cache:warmup --quiet
pids=()
for token in $(seq 1 "$PROCESSES"); do
  reset_database "$token" &
  pids[token]=$!
done
failed=()
for token in "${!pids[@]}"; do
  wait "${pids[token]}" || failed+=("$token")
done
if ((${#failed[@]})); then
  echo "[run-tests] Resetting test databases failed: ${failed[*]}"
  exit 1
fi

echo "[run-tests] Running tests..."
# Pages that failing flow tests saved last time (BrowserExtension in phpunit.xml.dist)
rm -rf var/browser
exec vendor/bin/paratest --processes="$PROCESSES" "$@"
