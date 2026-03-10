# Playground Mode: Deployment Guide

Step-by-step guide to migrate from the existing single-stack production setup to the dual-stack (Live + Playground) setup with Caddy.

## Prerequisites

- SSH access to the Lightsail server
- DNS management access (for `live.supplymars.com`)
- The `ai/feature` branch merged to `main`

---

## Phase 1: Prepare env files (local)

Create the two env files on your local machine first, then copy them to the server.

### 1.1 Create `live.env`

This is everything the current single stack uses, plus the new vars. Take the existing values from your GitHub secrets.

```env

APP_SECRET=<your-existing-app-secret>
DEFAULT_DOMAIN=supplymars
DEFAULT_URI=https://www.supplymars.com
DATABASE_URL=mysql://app:<existing-app-password>@database:3306/app
REDIS_URL=redis://default:<existing-redis-password>@redis:6379
REDIS_SESSION_DSN=redis://default:<existing-redis-password>@redis:6379/1
REDIS_PASSWORD=<existing-redis-password>
MESSENGER_TRANSPORT_DSN=phpamqplib://app:<existing-rabbitmq-password>@rabbitmq:5672/%2f/messages
RABBITMQ_PASSWORD=<existing-rabbitmq-password>
MAILER_DSN=<your-existing-mailer-dsn>
AWS_S3_REGION=eu-west-2
AWS_S3_BUCKET=unicorn-bucket-two
AWS_S3_ACCESS_ID=<your-s3-access-id>
AWS_S3_SECRET_ACCESS_KEY=<your-s3-secret-key>
AWS_S3_PRODUCTS_PREFIX=
UPLOADS_BASE_URL=https://unicorn-bucket-two.s3.eu-west-2.amazonaws.com
TURNSTILE_SITE_KEY=<your-turnstile-site-key>
TURNSTILE_SECRET_KEY=<your-turnstile-secret-key>
MYSQL_PASSWORD=<existing-app-password>
MYSQL_ROOT_PASSWORD=<existing-root-password>
PLAYGROUND_MODE=false
NGINX_HOST_PORT=8001
DB_HOST_PORT=3307
CRONTAB_FILE=docker/php/cron/live-crontab
```

### 1.2 Create `playground.env`

Same structure, **different passwords** for all services (new isolated databases), plus playground-specific values.

```env
APP_SECRET=<generate-new-secret>
DEFAULT_DOMAIN=supplymars
DEFAULT_URI=https://www.supplymars.com
DATABASE_URL=mysql://app:<new-playground-db-password>@database:3306/app
REDIS_URL=redis://default:<new-playground-redis-password>@redis:6379
REDIS_SESSION_DSN=redis://default:<new-playground-redis-password>@redis:6379/1
REDIS_PASSWORD=<new-playground-redis-password>
MESSENGER_TRANSPORT_DSN=phpamqplib://app:<new-playground-rabbitmq-password>@rabbitmq:5672/%2f/messages
RABBITMQ_PASSWORD=<new-playground-rabbitmq-password>
MAILER_DSN=<your-existing-mailer-dsn>
AWS_S3_REGION=eu-west-2
AWS_S3_BUCKET=unicorn-bucket-two
AWS_S3_ACCESS_ID=<your-s3-access-id>
AWS_S3_SECRET_ACCESS_KEY=<your-s3-secret-key>
AWS_S3_PRODUCTS_PREFIX=playground
UPLOADS_BASE_URL=https://unicorn-bucket-two.s3.eu-west-2.amazonaws.com/playground
TURNSTILE_SITE_KEY=<your-turnstile-site-key>
TURNSTILE_SECRET_KEY=<your-turnstile-secret-key>
MYSQL_PASSWORD=<new-playground-db-password>
MYSQL_ROOT_PASSWORD=<new-playground-root-password>
PLAYGROUND_MODE=true
NGINX_HOST_PORT=8002
DB_HOST_PORT=3308
CRONTAB_FILE=docker/php/cron/playground-crontab
```

Generate new passwords with: `openssl rand -base64 24`

Generate a new APP_SECRET with: `openssl rand -hex 16`

---

## Phase 2: Server preparation (SSH)

```bash
ssh ubuntu@<server-ip>
```

### 2.1 Upload env files

```bash
mkdir -p /home/ubuntu/supplymars-secrets
```

Copy the files from local to server (run from your local machine):

```bash
scp live.env ubuntu@<server-ip>:/home/ubuntu/supplymars-secrets/live.env
scp playground.env ubuntu@<server-ip>:/home/ubuntu/supplymars-secrets/playground.env
```

Back on the server, lock down permissions:

```bash
chmod 600 /home/ubuntu/supplymars-secrets/*.env
```

### 2.2 Add DNS record

Add an A record for `live.supplymars.com` pointing to the same IP as `supplymars.com`.

Verify propagation (may take a few minutes):

```bash
dig +short live.supplymars.com
```

### 2.3 Install Caddy

```bash
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update
sudo apt install -y caddy
```

Write the Caddyfile:

```bash
sudo tee /etc/caddy/Caddyfile > /dev/null << 'EOF'
supplymars.com, www.supplymars.com {
    reverse_proxy localhost:8001
}

live.supplymars.com {
    reverse_proxy localhost:8002
}
EOF
```

**Don't start Caddy yet** — it needs ports 80/443 which the old nginx still holds.

---

## Phase 3: Backup and stop old stack (SSH)

### 3.1 Take a database backup

```bash
cd /home/ubuntu/supplymars

# Trigger an immediate backup via the existing cron container
docker compose -f compose.yaml -f compose.prod.yaml exec -T cron php bin/console app:backup-database
```

Verify the backup appeared in S3:

```bash
aws s3 ls s3://unicorn-bucket-two/backups/ --human-readable | tail -3
```

### 3.2 Dump the database locally (belt and braces)

This dump will be restored into the new live stack's database.

```bash
docker compose -f compose.yaml -f compose.prod.yaml exec -T database \
    mysqldump -u root -p<existing-root-password> --single-transaction --quick app \
    > /home/ubuntu/supplymars-db-migration.sql
```

Verify the dump isn't empty:

```bash
ls -lh /home/ubuntu/supplymars-db-migration.sql
head -20 /home/ubuntu/supplymars-db-migration.sql
```

### 3.3 Stop the old stack

```bash
# Stop all containers (this frees ports 80/443 for Caddy)
docker compose -f compose.yaml -f compose.prod.yaml down
```

**The site is now offline.** Work quickly through the next steps.

---

## Phase 4: Deploy new code and start stacks (SSH)

### 4.1 Pull the latest code

```bash
cd /home/ubuntu/supplymars
git fetch origin
git reset --hard origin/main
git clean -fd
```

### 4.2 Start the live stack

```bash
docker compose --env-file ../supplymars-secrets/live.env \
    -p supplymars-live \
    -f compose.yaml -f compose.prod.yaml \
    up -d --build --remove-orphans --wait
```

Wait for all health checks to pass. This creates a fresh database — we'll restore data next.

### 4.3 Restore database into live

```bash
docker compose --env-file ../supplymars-secrets/live.env \
    -p supplymars-live \
    -f compose.yaml -f compose.prod.yaml \
    exec -T database mysql -u root -p<live-root-password> app \
    < /home/ubuntu/supplymars-db-migration.sql
```

### 4.4 Create backups directory and seed initial backup

The playground reset restores from a shared host directory. Create it and run the live backup to populate it:

```bash
mkdir -p /home/ubuntu/supplymars-backups

docker compose --env-file ../supplymars-secrets/live.env \
    -p supplymars-live \
    -f compose.yaml -f compose.prod.yaml \
    exec -T cron php bin/console app:backup-database --local-copy=/backups/latest.sql.gz
```

Verify the backup exists:

```bash
ls -lh /home/ubuntu/supplymars-backups/latest.sql.gz
```

### 4.5 Start the playground stack

```bash
docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground --profile playground \
    -f compose.yaml -f compose.prod.yaml \
    up -d --build --remove-orphans --wait
```

This starts all playground services including the `reset` container (which runs the nightly reset cron).

### 4.6 Trigger initial playground reset

The reset container runs on a nightly schedule, but for the first deployment we need to seed playground data immediately. Exec into the reset container and run the script manually:

```bash
docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground --profile playground \
    -f compose.yaml -f compose.prod.yaml \
    exec -T reset /usr/local/bin/playground-reset.sh
```

This restores the database from the backup file, syncs S3 uploads/media to the playground prefix, and flushes playground Redis.

### 4.7 Start Caddy

```bash
sudo systemctl start caddy
sudo systemctl enable caddy
```

**The site is back online.**

---

## Phase 5: Verify everything works

### 5.1 Check both sites load

```bash
# From the server (via Caddy)
curl -sf -o /dev/null -w "%{http_code}" https://supplymars.com
# Should print: 200

curl -sf -o /dev/null -w "%{http_code}" https://live.supplymars.com
# Should print: 200
```

### 5.2 Check playground banner

Open `https://supplymars.com` in a browser — you should see the amber "This is a playground" banner at the top.

Open `https://live.supplymars.com` — no banner.

### 5.3 Check migrations are up to date

```bash
docker compose --env-file ../supplymars-secrets/live.env \
    -p supplymars-live \
    -f compose.yaml -f compose.prod.yaml \
    exec -T php php bin/console doctrine:migrations:up-to-date

docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground \
    -f compose.yaml -f compose.prod.yaml \
    exec -T php php bin/console doctrine:migrations:up-to-date
```

### 5.4 Check SSL certificates

```bash
curl -vI https://supplymars.com 2>&1 | grep "subject:"
curl -vI https://live.supplymars.com 2>&1 | grep "subject:"
```

Both should show valid Let's Encrypt certs (auto-provisioned by Caddy).

### 5.5 Test image uploads (optional)

Upload a product image on each site and verify S3 paths:

```bash
# Live should be at root
aws s3 ls s3://unicorn-bucket-two/uploads/products/ | tail -3

# Playground should be under playground/ prefix
aws s3 ls s3://unicorn-bucket-two/playground/uploads/products/ | tail -3
```

### 5.6 Check containers are healthy

```bash
docker compose --env-file ../supplymars-secrets/live.env \
    -p supplymars-live \
    -f compose.yaml -f compose.prod.yaml \
    ps

docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground \
    -f compose.yaml -f compose.prod.yaml \
    ps
```

All services should show `(healthy)`.

---

## Phase 6: Verify nightly reset

The reset runs automatically via the `reset` container in the playground stack (cron at 2:15 AM UTC, after live's 2:00 AM backup). The live backup command writes to `/home/ubuntu/supplymars-backups/latest.sql.gz` via the `--local-copy` option, and the playground reset container reads from it via a read-only volume mount. No shared Docker network needed.

Check the reset container is running:

```bash
docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground --profile playground \
    -f compose.yaml -f compose.prod.yaml \
    ps reset
```

Check reset logs after the first nightly run:

```bash
docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground --profile playground \
    -f compose.yaml -f compose.prod.yaml \
    logs reset
```

---

## Phase 7: Clean up old setup

### 7.1 Remove old certbot (if present)

Caddy handles SSL now. Certbot is no longer needed.

```bash
# Check if certbot is installed
which certbot && {
    # Remove certbot auto-renewal cron/timer
    sudo systemctl disable certbot.timer 2>/dev/null
    sudo systemctl stop certbot.timer 2>/dev/null

    # Optionally remove certbot entirely
    sudo apt remove -y certbot
}
```

### 7.2 Remove old Docker volumes

The old single-stack volumes are no longer used:

```bash
# List old volumes
docker volume ls | grep supplymars_

# Once you're confident the new stacks are working, remove old volumes
docker volume rm supplymars_db-data supplymars_redis_data supplymars_rabbitmq_data 2>/dev/null
```

**Wait a few days before doing this** — keep them as a fallback.

### 7.3 Clean up the migration dump

```bash
rm /home/ubuntu/supplymars-db-migration.sql
```

### 7.4 Remove old GitHub secrets (optional)

The following GitHub secrets are no longer used by CI (env files replaced them):

`APP_SECRET`, `DATABASE_URL`, `MAILER_DSN`, `AWS_S3_ACCESS_ID`, `AWS_S3_SECRET_ACCESS_KEY`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`, `REDIS_PASSWORD`, `REDIS_URL`, `REDIS_SESSION_DSN`, `RABBITMQ_PASSWORD`, `MESSENGER_TRANSPORT_DSN`, `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`

Keep `SERVER_HOST`, `SERVER_USER`, `SERVER_SSH_KEY` — CI still needs those for SSH.

---

## Rollback plan

If things go wrong, you can revert to the single-stack setup:

```bash
# Stop new stacks
docker compose --env-file ../supplymars-secrets/live.env -p supplymars-live -f compose.yaml -f compose.prod.yaml down
docker compose --env-file ../supplymars-secrets/playground.env -p supplymars-playground -f compose.yaml -f compose.prod.yaml down

# Stop Caddy
sudo systemctl stop caddy

# Checkout previous commit
git log --oneline -5   # find the last pre-playground commit
git reset --hard <commit>

# Restart old stack with inline env vars (as before)
export APP_SECRET=...   # etc.
docker compose -f compose.yaml -f compose.prod.yaml up -d --build --remove-orphans --wait
```

The old `supplymars_db-data` volume still exists (unless you removed it in Phase 7.2) and Docker Compose will reattach to it.

---

## Quick reference: common commands after migration

```bash
# Start/stop live
make up-prod-live
make down-prod-live

# Start/stop playground
make up-prod-playground
make down-prod-playground

# View live logs
docker compose --env-file ../supplymars-secrets/live.env -p supplymars-live -f compose.yaml -f compose.prod.yaml logs -f

# View playground logs
docker compose --env-file ../supplymars-secrets/playground.env -p supplymars-playground -f compose.yaml -f compose.prod.yaml logs -f

# Exec into live PHP container
docker compose --env-file ../supplymars-secrets/live.env -p supplymars-live -f compose.yaml -f compose.prod.yaml exec php bash

# Manual playground reset
docker compose --env-file ../supplymars-secrets/playground.env -p supplymars-playground --profile playground -f compose.yaml -f compose.prod.yaml exec -T reset /usr/local/bin/playground-reset.sh

# SSH tunnels for PhpMyAdmin (run locally)
ssh -L 3307:localhost:3307 ubuntu@<server-ip>   # live DB → phpmyadmin-prod-live at localhost:8081
ssh -L 3308:localhost:3308 ubuntu@<server-ip>   # playground DB → phpmyadmin-prod-playground at localhost:8082

# Check Caddy status
sudo systemctl status caddy
sudo journalctl -u caddy --no-pager -n 50
```
