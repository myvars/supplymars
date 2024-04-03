#!/bin/bash
# Get login credentials
read -sp $'GitHub Password:\n' gitpassvar

# Create project directory
sudo rm -rf /opt/bitnami/projects/app
sudo mkdir -p /opt/bitnami/projects/app
sudo chown $USER /opt/bitnami/projects/app

# Checkout repository
git clone -b main https://myvars:${gitpassvar}@github.com/myvars/turtle /opt/bitnami/projects/app/