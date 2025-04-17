#!/bin/bash

set -e  # Exit immediately if a command exits with a non-zero status
set -u  # Treat unset variables as an error

# Define constants
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
echo "Please enter the following values..."
read -p $'Domain Name:\n' domainvar
read -sp $'GitHub Password:\n' gitpassvar
read -sp $'Symfony Secret:\n' symfonysecretvar
read -sp $'Database Password:\n' dbpassvar
read -sp $'Mailer Key:\n' mailerkeypairvar
read -p $'AWS S3 Bucket:\n' s3bucketvar
read -sp $'AWS S3 ID:\n' s3accessidvar
read -sp $'AWS S3 Secret:\n' s3accesssecretvar
read -p $'Dev Email:\n' devemailvar

# Validate required inputs
if [[ -z "$domainvar" || -z "$gitpassvar" || -z "$symfonysecretvar" || -z "$dbpassvar" || -z "$mailerkeypairvar" || -z "$s3bucketvar" || -z "$s3accessidvar" || -z "$s3accesssecretvar" || -z "$devemailvar" ]]; then
  echo "Error: All values are required."
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
if [[ -f "${CERTS_DIR}/${domainvar}.crt" && -f "${CERTS_DIR}/${domainvar}.key" ]]; then
  sudo ln -sf "${CERTS_DIR}/${domainvar}.crt" "${CERTS_DIR}/bitnami/certs/server.crt"
  sudo ln -sf "${CERTS_DIR}/${domainvar}.key" "${CERTS_DIR}/bitnami/certs/server.key"
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

# set Production Environment vars
echo "SITE_DOMAIN=${domainvar}" >> /opt/bitnami/projects/app/.env
echo "SITE_URL=https://www.${domainvar}" >> /opt/bitnami/projects/app/.env
echo "APP_SECRET=${symfonysecretvar}" >> /opt/bitnami/projects/app/.env
echo "MAILER_DSN=ses+smtp://${mailerkeypairvar}@default?region=eu-west-2" >> /opt/bitnami/projects/app/.env
echo "DATABASE_URL=\"mysql://root:${dbpassvar}@127.0.0.1:3306/app?serverVersion=8&charset=utf8mb4\"" >> /opt/bitnami/projects/app/.env
echo "AWS_S3_BUCKET=${s3bucketvar}" >> /opt/bitnami/projects/app/.env
echo "AWS_S3_ACCESS_ID=${s3accessidvar}" >> /opt/bitnami/projects/app/.env
echo "AWS_S3_SECRET_ACCESS_KEY=${s3accesssecretvar}" >> /opt/bitnami/projects/app/.env
echo "DEV_MAIL_RECIPIENT=${devemailvar}" >> /opt/bitnami/projects/app/.env

# Run the startup script
if [[ -f "${PROJECT_DIR}/deploy/startup.sh" ]]; then
  source "${PROJECT_DIR}/deploy/startup.sh"
else
  echo "Error: Startup script not found."
  exit 1
fi