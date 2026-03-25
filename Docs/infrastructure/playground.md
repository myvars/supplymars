# Playground Infrastructure

The playground is a full copy of the live site where users can experiment freely. Both stacks run on the same Lightsail instance, isolated by Docker Compose project names.

## Architecture

```
                        ┌─────────┐
            Internet ──▶│  Caddy  │ (auto TLS)
                        └────┬────┘
                ┌────────────┼────────────┐
                ▼                         ▼
        localhost:8001            localhost:8002
   ┌─────────────────────┐  ┌───────────────────────┐
   │   supplymars-live   │  │ supplymars-playground │
   │ (www.supplymars.com │  │ (live.supplymars.com) │
   │   supplymars.com)   │  │                       │
   └─────────────────────┘  └───────────────────────┘
```

Each stack has its own database, Redis, RabbitMQ, and PHP containers. They share nothing at the Docker level — no shared networks.

### Nightly Reset

At **02:00 UTC**, the live cron container runs `app:backup-database --local-copy=/backups/latest.sql.gz`. This uploads the backup to S3 and saves a copy to `/home/ubuntu/supplymars-backups/` on the host.

At **02:15 UTC**, the playground reset container runs `playground-reset.sh`, which:

1. Restores the database from `/backups/latest.sql.gz` (read-only volume mount)
2. Redacts staff credentials and creates the demo user (via `playground-redact-staff.sql`, immediately after restore — no window where real credentials are exposed)
3. Syncs S3 uploads/media from the live prefix to the playground prefix
4. Flushes playground Redis
5. Purges the playground RabbitMQ queue

The credential redaction runs as raw SQL in the reset container (which has no PHP) for zero-gap security. See `scripts/playground-redact-staff.sql` for details.

### Key Files

| File | Purpose |
|------|---------|
| `compose.prod.yaml` | Production overrides (both stacks) |
| `docker/php/cron/live-crontab` | Live cron schedule (includes backup) |
| `docker/php/cron/playground-crontab` | Playground cron schedule |
| `docker/php/cron/reset-crontab` | Reset container cron (02:15 UTC) |
| `scripts/playground-reset.sh` | Reset script (DB restore, credential redaction, S3 sync, cache flush) |
| `scripts/playground-redact-staff.sql` | Staff credential scrambling and demo user creation |
| `Docs/infrastructure/Caddyfile` | Reference copy of the Caddyfile |

---

## Environment Files

Both stacks use env files stored at `/home/ubuntu/supplymars-secrets/` on the server (not in the repo).

### live.env

```env
APP_SECRET=<secret>
DEFAULT_DOMAIN=supplymars
DEFAULT_URI=https://www.supplymars.com
DATABASE_URL=mysql://app:<password>@database:3306/app
REDIS_URL=redis://default:<password>@redis:6379
REDIS_SESSION_DSN=redis://default:<password>@redis:6379/1
REDIS_PASSWORD=<password>
MESSENGER_TRANSPORT_DSN=phpamqplib://app:<password>@rabbitmq:5672/%2f/messages
RABBITMQ_PASSWORD=<password>
MAILER_DSN=<mailer-dsn>
AWS_S3_REGION=eu-west-2
AWS_S3_BUCKET=unicorn-bucket-two
AWS_S3_ACCESS_ID=<access-id>
AWS_S3_SECRET_ACCESS_KEY=<secret-key>
AWS_S3_PRODUCTS_PREFIX=
UPLOADS_BASE_URL=https://unicorn-bucket-two.s3.eu-west-2.amazonaws.com
TURNSTILE_SITE_KEY=<key>
TURNSTILE_SECRET_KEY=<key>
MYSQL_PASSWORD=<password>
MYSQL_ROOT_PASSWORD=<password>
PLAYGROUND_MODE=false
NGINX_HOST_PORT=8001
DB_HOST_PORT=3307
CRONTAB_FILE=docker/php/cron/live-crontab
```

### playground.env

Same structure with **different passwords** for all services, plus playground-specific values:

```env
# ... same keys as live.env, with different passwords ...
AWS_S3_PRODUCTS_PREFIX=playground
UPLOADS_BASE_URL=https://unicorn-bucket-two.s3.eu-west-2.amazonaws.com/playground
PLAYGROUND_MODE=true
NGINX_HOST_PORT=8002
DB_HOST_PORT=3308
CRONTAB_FILE=docker/php/cron/playground-crontab
```

Generate passwords: `openssl rand -base64 24`
Generate APP_SECRET: `openssl rand -hex 16`

---

## Fresh Server Setup

### Prerequisites

- Ubuntu on AWS Lightsail
- Ports 80, 443 open in Lightsail firewall
- DNS A records for `supplymars.com`, `www.supplymars.com`, `live.supplymars.com` pointing to the instance IP
- Docker and Docker Compose installed

### 1. Install Caddy

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

sudo systemctl enable caddy
sudo systemctl start caddy
```

### 2. Upload env files

```bash
mkdir -p /home/ubuntu/supplymars-secrets
chmod 700 /home/ubuntu/supplymars-secrets
# scp live.env and playground.env from local machine
chmod 600 /home/ubuntu/supplymars-secrets/*.env
```

### 3. Create backups directory

```bash
mkdir -p /home/ubuntu/supplymars-backups
```

### 4. Clone and deploy

```bash
cd /home/ubuntu
git clone <repo-url> supplymars
cd supplymars
```

Start the live stack:

```bash
docker compose --env-file ../supplymars-secrets/live.env \
    -p supplymars-live \
    -f compose.yaml -f compose.prod.yaml \
    up -d --build --remove-orphans --wait
```

Seed the backup file (required before playground can start):

```bash
docker compose --env-file ../supplymars-secrets/live.env \
    -p supplymars-live \
    -f compose.yaml -f compose.prod.yaml \
    exec -T cron php bin/console app:backup-database --local-copy=/backups/latest.sql.gz
```

Start the playground stack:

```bash
docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground --profile playground \
    -f compose.yaml -f compose.prod.yaml \
    up -d --build --remove-orphans --wait
```

Trigger the initial playground reset:

```bash
docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground --profile playground \
    -f compose.yaml -f compose.prod.yaml \
    exec -T reset /usr/local/bin/playground-reset.sh
```

### 5. Verify

```bash
curl -sf -o /dev/null -w "%{http_code}" https://supplymars.com       # 200
curl -sf -o /dev/null -w "%{http_code}" https://live.supplymars.com   # 200
```

---

## Common Commands

```bash
# --- Start / Stop ---
make up-prod-live
make down-prod-live
make up-prod-playground
make down-prod-playground

# --- Logs ---
# Live
docker compose --env-file ../supplymars-secrets/live.env \
    -p supplymars-live -f compose.yaml -f compose.prod.yaml logs -f

# Playground
docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground -f compose.yaml -f compose.prod.yaml logs -f

# --- Shell access ---
docker compose --env-file ../supplymars-secrets/live.env \
    -p supplymars-live -f compose.yaml -f compose.prod.yaml exec php bash

# --- Manual playground reset ---
docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground --profile playground \
    -f compose.yaml -f compose.prod.yaml \
    exec -T reset /usr/local/bin/playground-reset.sh

# --- Manual backup with local copy ---
docker compose --env-file ../supplymars-secrets/live.env \
    -p supplymars-live -f compose.yaml -f compose.prod.yaml \
    exec -T cron php bin/console app:backup-database --local-copy=/backups/latest.sql.gz

# --- Container health ---
docker compose --env-file ../supplymars-secrets/live.env \
    -p supplymars-live -f compose.yaml -f compose.prod.yaml ps
docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground -f compose.yaml -f compose.prod.yaml ps

# --- SSH tunnels for DB access (run locally) ---
ssh -L 3307:localhost:3307 ubuntu@<server-ip>   # live DB
ssh -L 3308:localhost:3308 ubuntu@<server-ip>   # playground DB

# --- Caddy ---
sudo systemctl status caddy
sudo journalctl -u caddy --no-pager -n 50
```

---

## Troubleshooting

### Reset fails: "Backup file not found"

The live backup hasn't run yet, or the volume mount is misconfigured.

```bash
# Check the file exists on the host
ls -lh /home/ubuntu/supplymars-backups/latest.sql.gz

# Run backup manually
docker compose --env-file ../supplymars-secrets/live.env \
    -p supplymars-live -f compose.yaml -f compose.prod.yaml \
    exec -T cron php bin/console app:backup-database --local-copy=/backups/latest.sql.gz
```

### Caddy won't provision certificates

Ensure ports 80 and 443 are open in the Lightsail firewall and DNS A records point to the instance IP.

```bash
# Check Caddy logs
sudo journalctl -u caddy --no-pager -n 100 | grep -i "error\|fail\|challenge"

# Restart after fixing
sudo systemctl restart caddy
```

### Reset container not running

```bash
docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground --profile playground \
    -f compose.yaml -f compose.prod.yaml ps reset

# Check logs
docker compose --env-file ../supplymars-secrets/playground.env \
    -p supplymars-playground --profile playground \
    -f compose.yaml -f compose.prod.yaml logs reset
```
