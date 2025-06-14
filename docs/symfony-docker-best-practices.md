
# Symfony Docker Best Practices & Setup Guide

## Table of Contents

- [Project Structure](#project-structure)
- [Docker & Symfony Environment Overview](#docker--symfony-environment-overview)
- [Dockerfile & Entrypoint Explained](#dockerfile--entrypoint-explained)
- [Docker Compose Files Explained](#docker-compose-files-explained)
- [Makefile Automation](#makefile-automation)
- [Managing Environment Variables & Secrets](#managing-environment-variables--secrets)
- [Testing & Running Locally](#testing--running-locally)
- [Production Deployment](#production-deployment)
- [GitHub Actions CI/CD Workflow](#github-actions-cicd-workflow)
- [AWS Lightsail Ubuntu Server Setup](#aws-lightsail-ubuntu-server-setup)
- [Troubleshooting & Monitoring](#troubleshooting--monitoring)
- [References & Further Reading](#references--further-reading)

---

## Project Structure

```
project-root/
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   └── conf.d/
│   ├── nginx/
│   │   └── conf.d/
│   │   └── certs/
│   └── entrypoint.sh
├── public/
├── src/
├── tests/
├── .env
├── .env.prod.local         # Not committed; used for local prod testing
├── compose.yaml
├── compose.override.yaml
├── compose.prod.yaml
├── compose.prod.local.yaml
├── Makefile
├── run-tests.sh
└── composer.json
```
- **docker/**: All image configuration and overrides
- **compose.*.yaml**: Compose files for each environment (dev, prod, local-prod)
- **Makefile**: Automation for common commands
- **run-tests.sh**: Unified test runner
- **.env/.env.prod.local**: Environment variable management

---

## Docker & Symfony Environment Overview

**Containers:**
- **php**: Runs Symfony app (dev/prod config via target)
- **nginx**: Web server (dev/prod config and SSL)
- **database**: MySQL 8.0
- **redis**: Redis for cache/session/messenger
- **messenger**: Symfony Messenger worker
- **mailer**: Mailpit (dev only, local mail)
- **phpmyadmin**: For DB browsing (dev only)

**Environments:**
- **Development**: Hot reload, Xdebug, self-signed SSL, Mailpit, PhpMyAdmin, bind-mount source code
- **Production**: Optimized images, real SSL certs, no dev services, secrets injected at runtime

---

## Dockerfile & Entrypoint Explained

**Dockerfile** (multi-stage, [php-dev/php-prod/nginx-dev/nginx-prod]):
- `php-dev`: Installs Xdebug, dev dependencies, config for rapid iteration
- `php-prod`: Strips dev tools, builds optimized, production-ready PHP
- `nginx-dev/nginx-prod`: Nginx tailored for each env

**Entrypoint** (`docker-entrypoint.sh`):
- Waits for DB/Redis to become healthy before starting PHP/Nginx
- Handles migrations, cache warming, permissions, etc. (add your logic as needed)
- Ensures containers can be used both for dev and prod startup, providing consistency

---

## Docker Compose Files Explained

### compose.yaml

Defines core services (PHP, Nginx, MySQL, Redis, Messenger).  
Sets healthy startup order with `depends_on` and `healthcheck` for reliable boot:

```yaml
php:
  build: ...
  depends_on:
    database:
      condition: service_healthy
    redis:
      condition: service_healthy
```
**Messenger** runs as a separate service, tailing the async queue.

### compose.override.yaml

**Development only:**
- Xdebug, Mailpit, PhpMyAdmin
- Bind-mounts code and dev configs
- Dev SSL certs for local HTTPS

### compose.prod.yaml

**Production only:**
- All prod envs set from env vars (never hardcoded secrets)
- Uses real Let's Encrypt SSL certs
- Dev tools/services excluded

### compose.prod.local.yaml

**Local prod test:**  
- Uses prod configs with local self-signed SSL, letting you test "prod" on your own machine.

---

## Makefile Automation

Automate all common workflows:

```makefile
up:             # Start dev stack
	docker compose -f compose.yaml -f compose.override.yaml up -d --build

down:           # Stop and remove all containers/volumes
	docker compose down -v

up-prod-local:  # Start prod config locally (with local SSL)
	docker compose -f compose.yaml -f compose.prod.yaml -f compose.prod.local.yaml up -d --build

test:           # Run tests in the container
	docker compose run --rm -e APP_ENV=test php ./run-tests.sh

logs:           # Tail all logs
	docker compose logs -f

prune:          # Remove unused images/volumes
	docker system prune -af --volumes
```

**Tip:**  
Always keep `Makefile` up to date and include a `help` target that lists all available commands.

---

## Managing Environment Variables & Secrets

- **`.env`**: All default/dev values, versioned in git.
- **`.env.prod.local`**: For local prod testing, **NOT** in git.
- **Production:**  
  All secrets injected at runtime via the CI/CD pipeline (never committed), using GitHub Actions secrets.
- **Service secrets**: e.g.,  
  - `APP_SECRET`, `DATABASE_URL`, `REDIS_URL`, `AWS_S3_ACCESS_ID`, etc.
- **Best practice:**  
  Do NOT build secrets into images; always inject via environment (compose + CI/CD).

---

## Testing & Running Locally

**Workflow:**
1. Clone repo and copy `.env` if needed
2. Run `make up` to build/start containers
3. Access app on https://localhost (self-signed)
4. PhpMyAdmin: http://localhost:8080 (dev only)
5. Mailpit: http://localhost:8025 (dev only)
6. Run tests with `make test`  
7. Run Messenger worker with `docker compose up messenger` (or use the Makefile)

**Tips:**
- Use bind-mounts for instant feedback (edit code locally, instantly reflected in container)
- Run Composer/Symfony CLI inside container for full parity:  
  `docker compose exec php composer update`

---

## Production Deployment

**Workflow:**
1. Push to `main` branch on GitHub
2. CI builds and tests Docker images (via GitHub Actions)
3. On success, CI deploys to Lightsail via SSH
4. On the server:
   - Latest code is pulled (`git reset --hard origin/main`)
   - All secrets exported as env vars
   - Containers rebuilt and started with `docker compose -f compose.yaml -f compose.prod.yaml up -d --build --remove-orphans --wait`
   - Real SSL certs used (from Let’s Encrypt)

**Zero-downtime:**  
- Run DB migrations/caches on deploy
- Use healthchecks to only start Nginx after PHP is healthy

---

## GitHub Actions CI/CD Workflow

**Pattern:**
- On every push to main:
  - Check out code
  - Build images with Compose, test locally on the runner
  - Run all unit/functional tests via `run-tests.sh`
  - If all pass, deploy to Lightsail using [appleboy/ssh-action](https://github.com/appleboy/ssh-action)
  - All secrets exported via GitHub Actions secrets
  - Use `docker compose up` with production compose files

**Example highlights:**
```yaml
- name: Deploy to AWS Lightsail
  uses: appleboy/ssh-action@v1.0.3
  with:
    ...
    script: |
      cd /home/ubuntu/supplymars
      git fetch origin
      git reset --hard origin/main
      export APP_ENV=prod
      ...
      docker compose -f compose.yaml -f compose.prod.yaml up -d --build --remove-orphans --wait
```

---

## AWS Lightsail Ubuntu Server Setup

### 1. Launch & Connect

- Create a new **Ubuntu LTS** instance on Lightsail
- Assign static IP, update DNS
- SSH in:  
  ```sh
  ssh ubuntu@your-server-ip
  ```

### 2. Harden & Prepare

```sh
# Update everything
sudo apt update && sudo apt upgrade -y

# (Recommended) Create swap file if RAM < 2GB
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Add SSH keys
mkdir -p ~/.ssh
chmod 700 ~/.ssh
nano ~/.ssh/authorized_keys   # Paste your public key
chmod 600 ~/.ssh/authorized_keys

# Set up git
sudo apt install git -y
git config --global user.name "Your Name"
git config --global user.email "your@email.com"
```

### 3. Install Docker & Docker Compose

```sh
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker ubuntu
# Log out/in for group to take effect

# Docker Compose plugin
sudo apt-get install docker-compose-plugin -y
docker compose version
```

### 4. Clone Your Project

```sh
cd /home/ubuntu
git clone git@github.com:your-username/your-repo.git supplymars
cd supplymars
sudo chown -R ubuntu:ubuntu /home/ubuntu/supplymars
```

### 5. SSL Certs

- Use Certbot or copy your Let’s Encrypt certs to `/etc/letsencrypt/live/yourdomain/`
- Ensure correct permissions (644/600)

### 6. Build & Start

```sh
docker compose -f compose.yaml -f compose.prod.yaml up -d --build
```

---

## Troubleshooting & Monitoring

- `docker compose ps` – Show running containers and health
- `docker compose logs -f` – Tail all logs
- `docker compose exec php bash` – Shell inside PHP
- `docker compose logs php` – PHP logs
- `docker compose logs nginx` – Nginx logs
- Check `/var/log/nginx/error.log` (inside container) for web errors
- `curl -f https://localhost` – Simple container healthcheck
- `docker compose down -v` – Remove containers **and** volumes (for DB reset)

---

## References & Further Reading

- [Symfony Docker](https://symfony.com/doc/current/setup/docker.html)
- [Docker Compose](https://docs.docker.com/compose/)
- [GitHub Actions](https://docs.github.com/en/actions)
- [appleboy/ssh-action](https://github.com/appleboy/ssh-action)
- [Mailpit](https://github.com/axllent/mailpit)
- [AWS Lightsail Docs](https://lightsail.aws.amazon.com/ls/docs/en_us/)
- [Let’s Encrypt](https://letsencrypt.org/)

---

### Additional Tips

- Keep all secrets out of source control—use environment variables or secret managers!
- Regularly prune Docker system/images to save space
- Pin all dependencies in `composer.json` for repeatable builds
- Tag images for rollbacks if needed
- Automate as much as possible in your Makefile/CI/CD

---
