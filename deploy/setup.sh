#!/bin/bash

set -e  # Exit immediately if a command exits with a non-zero status
set -u  # Treat unset variables as an error

# Define constants
SITE_DOMAIN="duckbongo.com"
CERTS_DIR="/opt/bitnami/apache/conf"
PROJECT_DIR="/opt/bitnami/projects/app"

# Check for required commands
for cmd in sudo git wget; do
  if ! command -v $cmd &>/dev/null; then
    echo "Error: $cmd is not installed. Please install it and try again."
    exit 1
  fi
done

# Get environment variables
read -sp $'GitHub password:\n' gitpassvar

# Validate required inputs
if [[ -z "$gitpassvar" ]]; then
  echo "Error: Github password is required"
  exit 1
fi

# Update system
sudo apt update && sudo apt upgrade -y

# Install supervisor
if ! command -v supervisorctl &>/dev/null; then
  sudo apt install -y supervisor
else
  echo "Supervisor is already installed."
fi

# Install Symfony CLI
wget -qO- "https://get.symfony.com/cli/installer" | bash
sudo mv /home/bitnami/.symfony5/bin/symfony /usr/local/bin/symfony

# Link site certificates
if [[ -f "${CERTS_DIR}/${SITE_DOMAIN}.crt" && -f "${CERTS_DIR}/${SITE_DOMAIN}.key" ]]; then
  sudo ln -sf "${CERTS_DIR}/${SITE_DOMAIN}.crt" "${CERTS_DIR}/bitnami/certs/server.crt"
  sudo ln -sf "${CERTS_DIR}/${SITE_DOMAIN}.key" "${CERTS_DIR}/bitnami/certs/server.key"
else
  echo "Error: Domain certificate/key files don't exist."
  exit 1
fi

# Create project directory
sudo rm -rf "$PROJECT_DIR"
sudo mkdir -p "$PROJECT_DIR"
sudo chown "$USER" "$PROJECT_DIR"

# Checkout repository
if ! git clone -b main "https://myvars:${gitpassvar}@github.com/myvars/turtle" "$PROJECT_DIR"; then
  echo "Error: Failed to clone repository."
  exit 1
fi

# Run the startup script
if [[ -f "${PROJECT_DIR}/deploy/startup.sh" ]]; then
  source "${PROJECT_DIR}/deploy/startup.sh"
else
  echo "Error: Startup script not found."
  exit 1
fi