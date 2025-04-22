#!/bin/bash

set -e  # Exit immediately if a command exits with a non-zero status
set -u  # Treat unset variables as an error

PROJECT_DIR="/opt/bitnami/projects/app"

# Git reset and pull
cd "${PROJECT_DIR}"
if ! git fetch && git reset --hard && git pull; then
  echo "Error: Git pull failed."
  exit 1
fi

# Run the startup script
if [ -f "${PROJECT_DIR}/deploy/startup.sh" ]; then
  source "${PROJECT_DIR}/deploy/startup.sh"
else
  echo "Error: Startup script not found in ${PROJECT_DIR}/deploy."
  exit 1
fi