#!/bin/bash
read -p $'Domain Name:\n' domainvar

# Copy current .env
cp /opt/bitnami/projects/app/.env /home/bitnami/.env

# Git reset and pull
cd /opt/bitnami/projects/app
git fetch && git reset --hard && git pull

# Restore old .env .env
mv /home/bitnami/.env /opt/bitnami/projects/app/.env

# Copy logos/icons/favicons
cp /opt/bitnami/projects/app/assets/images/icons/${domainvar}/* /opt/bitnami/projects/app/public/images/icons
cp /opt/bitnami/projects/app/templates/logo/${domainvar}/* /opt/bitnami/projects/app/templates/logo

# Run the startup script
source /opt/bitnami/projects/app/deploy/startup.sh
