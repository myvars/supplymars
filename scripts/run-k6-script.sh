#!/bin/bash
set -e
cd "$(dirname "$0")/.."

SCRIPT="${1:-homepage.js}"
ENV="${2:-dev}"
DASHBOARD="${3:-false}"
MANIFEST_PATH="tests/Performance/k6/resources/manifest.json"

# Set up dashboard flags
if [ "$DASHBOARD" = "true" ]; then
  DASH_FLAGS="-p 5665:5665 -e K6_WEB_DASHBOARD=true"
else
  DASH_FLAGS=""
fi

case "$ENV" in
  symfony)
    if [ "$DASHBOARD" = "true" ]; then
      echo ""
      echo "============================"
      echo "REMINDER: To view the K6 dashboard remotely, set up an SSH tunnel:"
      echo ""
      echo "e.g.) ssh -N -L 5665:localhost:5665 ubuntu@supplymars.com"
      echo ""
      echo "Then visit: http://localhost:5665"
      echo "============================"
      echo ""
    fi
    cp "$(pwd)/public/assets/manifest.json" "$MANIFEST_PATH"
    docker run --rm \
      -v "$(pwd)/tests/Performance/k6":/scripts \
      -e SITE_URL="https://host.docker.internal:8000" \
      $DASH_FLAGS \
      grafana/k6 run --insecure-skip-tls-verify /scripts/"$SCRIPT"
    ;;
  dev)
    docker compose cp php:/app/public/assets/manifest.json "$MANIFEST_PATH" && \
    docker run --rm \
      -v "$(pwd)/tests/Performance/k6":/scripts \
      -e SITE_URL="https://host.docker.internal" \
      $DASH_FLAGS \
      grafana/k6 run --insecure-skip-tls-verify /scripts/"$SCRIPT"
    ;;
  prod)
    docker compose cp php:/app/public/assets/manifest.json "$MANIFEST_PATH" && \
    docker run --rm \
      -v "$(pwd)/tests/Performance/k6":/scripts \
      -e SITE_URL="https://www.supplymars.com" \
      $DASH_FLAGS \
      grafana/k6 run /scripts/"$SCRIPT"
    ;;
  *)
    echo "ERROR: ENV must be one of: symfony, dev, prod"
    exit 1
    ;;
esac

rm -f "$MANIFEST_PATH"
