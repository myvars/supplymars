#!/bin/bash

set -e  # Exit immediately if a command exits with a non-zero status
set -u  # Treat unset variables as an error

SITE_DOMAIN="duckbongo.com"
PROJECT_DIR="/opt/bitnami/projects/app"

echo "Startup script running..."

# Prompt user for database reset
read -t 5 -p $'Drop existing database? [n]:\n' input || true
choice=${input:-n}

# Run Composer update
if ! composer update --working-dir "${PROJECT_DIR}"; then
  echo "Error: Composer update failed."
  exit 1
fi

# Copy the cryptographic key for secrets decryption
if cp /home/bitnami/turtle.php "${PROJECT_DIR}/config/secrets/prod/prod.decrypt.private.php"; then
    "${PROJECT_DIR}/bin/console" secrets:decrypt-to-local --force --env=prod

    # Extract from .env.prod.local
    db_url=$(grep '^DATABASE_URL=' "${PROJECT_DIR}/.env.prod.local" | cut -d '=' -f2-)

    # Safety check
    if [ -z "$db_url" ]; then
      echo "Error: Could not extract DATABASE_URL from secrets."
      exit 1
    fi

    # Append to .env
    printf "\nDATABASE_URL=%s\n" "$db_url" >> "${PROJECT_DIR}/.env"

    rm "${PROJECT_DIR}/config/secrets/prod/prod.decrypt.private.php"
else
  echo "Error: Failed to copy cryptographic key."
  exit 1
fi

# Handle database reset or migration
if [[ "$choice" =~ ^[yY]$ ]]; then
  echo "Dropping and recreating the database...(skip in production)"
#  "${PROJECT_DIR}/bin/console" doctrine:database:drop --force || true
#  "${PROJECT_DIR}/bin/console" doctrine:database:create
#  "${PROJECT_DIR}/bin/console" doctrine:schema:create
#  "${PROJECT_DIR}/bin/console" doctrine:fixtures:load -n --env=dev --no-debug
#  "${PROJECT_DIR}/bin/console" app:create-warehouse-products --env=dev --no-debug
#  "${PROJECT_DIR}/bin/console" app:create-supplier-products --env=dev --no-debug
else
  echo "Using existing database..."
  "${PROJECT_DIR}/bin/console" doctrine:migrations:migrate -n
fi

# Copy logos and icons
mkdir -p "${PROJECT_DIR}/public/images/icons"
if ! cp -f "${PROJECT_DIR}/assets/images/icons/${SITE_DOMAIN}"/* "${PROJECT_DIR}/assets/images/icons"; then
  echo "Error: Failed to copy icons."
  exit 1
fi

if ! cp -f "${PROJECT_DIR}/templates/logo/${SITE_DOMAIN}"/* "${PROJECT_DIR}/templates/logo"; then
  echo "Error: Failed to copy logos."
  exit 1
fi

# Compile assets
cd "${PROJECT_DIR}"
if ! "${PROJECT_DIR}/bin/console" tailwind:build --minify; then
  echo "Error: Tailwind build failed."
  exit 1
fi

if ! "${PROJECT_DIR}/bin/console" asset-map:compile; then
  echo "Error: Asset map compilation failed."
  exit 1
fi

if ! "${PROJECT_DIR}/bin/console" cache:clear; then
  echo "Error: Cache clear failed."
  exit 1
fi

# Install and restart supervisor scripts
sudo supervisorctl stop messenger-consume:* || true
sudo cp "${PROJECT_DIR}/deploy/messenger-worker.conf" /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start messenger-consume:*

# Copy Apache vhost configurations
if ! sudo cp "${PROJECT_DIR}/deploy/app-vhost.conf" /opt/bitnami/apache2/conf/vhosts/app-vhost.conf; then
  echo "Error: Failed to copy app-vhost.conf."
  exit 1
fi

if ! sudo cp "${PROJECT_DIR}/deploy/app-https-vhost.conf" /opt/bitnami/apache2/conf/vhosts/app-https-vhost.conf; then
  echo "Error: Failed to copy app-https-vhost.conf."
  exit 1
fi

# Restart Apache
if ! sudo /opt/bitnami/ctlscript.sh restart apache; then
  echo "Error: Failed to restart Apache."
  exit 1
fi

echo "Startup script completed successfully."
