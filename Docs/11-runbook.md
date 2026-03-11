# Production Runbook

Step-by-step operational procedures for the SupplyMars production environment on AWS Lightsail. For architecture details, cron schedules, and worker configuration, see [06-operations.md](06-operations.md). For all console commands, see [09-cli-reference.md](09-cli-reference.md). For playground-specific details, see [infrastructure/playground.md](infrastructure/playground.md).

---

## 1. Service Architecture Quick Reference

Two Docker Compose stacks run on the same Lightsail instance, fronted by Caddy (auto TLS via Let's Encrypt):

| Stack | Project Name | Nginx Port | Domain |
|-------|-------------|------------|--------|
| Live | `supplymars-live` | 8001 | `supplymars.com`, `www.supplymars.com` |
| Playground | `supplymars-playground` | 8002 | `live.supplymars.com` |

Each stack runs 7 containers (8 for playground, which includes `reset`):

| Service | Image | Purpose |
|---------|-------|---------|
| nginx | nginx-prod | Reverse proxy (HTTP only — Caddy handles TLS) |
| php | php-prod | PHP-FPM application server |
| messenger | php-prod | Async message consumer |
| cron | cron-prod | Scheduled tasks |
| database | mysql:8.4 | Primary data store |
| redis | redis:8.4-alpine | Cache, sessions |
| rabbitmq | rabbitmq:4.2-management | Message queue |
| reset | playground-reset | Nightly DB reset (playground only) |

**Deploy path:** `/home/ubuntu/supplymars`
**Env files:** `/home/ubuntu/supplymars-secrets/{live,playground}.env`
**Backups:** `/home/ubuntu/supplymars-backups/`

### Compose Command Prefix

All `docker compose` commands require the env file and project name. For brevity, this runbook uses `$LIVE` and `$PLAYGROUND` as shorthand:

```bash
# Live stack
LIVE="--env-file ../supplymars-secrets/live.env -p supplymars-live -f compose.yaml -f compose.prod.yaml"

# Playground stack
PLAYGROUND="--env-file ../supplymars-secrets/playground.env -p supplymars-playground --profile playground -f compose.yaml -f compose.prod.yaml"
```

**Quick status:**
```bash
cd /home/ubuntu/supplymars
docker compose $LIVE ps
docker compose $PLAYGROUND ps
```

---

## 2. Deployment

### 2.1 Automated (CI/CD)

Merging to `main` triggers the GitHub Actions pipeline (`.github/workflows/ci.yml`):

1. Runs code quality checks (PHP-CS-Fixer, PHPStan, Doctrine schema validation)
2. Runs the full test suite
3. SSHs to the Lightsail instance
4. `git fetch origin && git reset --hard origin/main && git clean -fd`
5. Creates `/home/ubuntu/supplymars-backups` if missing
6. Builds and starts both stacks
7. Runs smoke tests (HTTP 200 check + migration status)

The PHP container runs migrations automatically on startup (`RUN_MIGRATIONS=true` in `docker-entrypoint.sh`).

### 2.2 Manual Deployment

Use when CI is down or for emergency hotfixes:

```bash
ssh ubuntu@<server-ip>
cd /home/ubuntu/supplymars

git fetch origin
git reset --hard origin/main
git clean -fd

mkdir -p /home/ubuntu/supplymars-backups

docker compose $LIVE up -d --build --remove-orphans --wait
docker compose $PLAYGROUND up -d --build --remove-orphans --wait
```

### 2.3 Rollback

There is no blue-green or canary deployment. Rollback means deploying a previous commit:

```bash
ssh ubuntu@<server-ip>
cd /home/ubuntu/supplymars

git log --oneline -10
git reset --hard <commit-sha>

docker compose $LIVE up -d --build --remove-orphans --wait
docker compose $PLAYGROUND up -d --build --remove-orphans --wait
```

**If the rollback involves a migration reversal**, you must manually revert the migration:
```bash
docker compose $LIVE exec php php bin/console doctrine:migrations:migrate prev --no-interaction
```

---

## 3. Health Checks

### 3.1 Post-Deploy Verification

Run after every deployment:

```bash
cd /home/ubuntu/supplymars

# 1. All services healthy
docker compose $LIVE ps
docker compose $PLAYGROUND ps

# 2. Apps respond (via Caddy)
curl -sf -o /dev/null -w "%{http_code}" https://supplymars.com          # 200
curl -sf -o /dev/null -w "%{http_code}" https://live.supplymars.com     # 200

# 3. Migrations up to date
docker compose $LIVE exec -T php php bin/console doctrine:migrations:up-to-date
docker compose $PLAYGROUND exec -T php php bin/console doctrine:migrations:up-to-date

# 4. Messenger transport
docker compose $LIVE exec -T php php bin/console messenger:stats

# 5. Check application logs for errors
docker compose $LIVE logs --tail=50 php
```

### 3.2 Ongoing Monitoring

```bash
# Container resource usage
docker stats --no-stream

# Check for failed Messenger messages
docker compose $LIVE exec -T php php bin/console messenger:failed:show

# Check cron output
docker compose $LIVE logs --tail=50 cron

# MySQL process list (check for long-running queries)
docker compose $LIVE exec -T database mysqladmin -u root -p processlist
```

---

## 4. Backup & Restore

### 4.1 Automated Daily Backup

The live cron container runs `app:backup-database` daily at 02:00 UTC (see `docker/php/cron/live-crontab`):

- Creates a gzipped mysqldump (`supplymars-YYYY-MM-DD-HHmmss.sql.gz`)
- Uploads to the S3 backups filesystem (`unicorn-bucket-two/backups/`)
- Saves a local copy to `/backups/latest.sql.gz` (host: `/home/ubuntu/supplymars-backups/latest.sql.gz`)
- Deletes S3 backups older than 30 days (default retention)

The local copy is used by the playground reset at 02:15 UTC.

Backup logs appear in `docker compose $LIVE logs cron`.

### 4.2 Manual Backup

Trigger a backup outside the cron schedule:

```bash
# Run backup (S3 + local copy)
docker compose $LIVE exec -T cron \
    php bin/console app:backup-database --local-copy=/backups/latest.sql.gz

# S3 only (no local copy)
docker compose $LIVE exec -T cron php bin/console app:backup-database

# Dry run (see what would happen)
docker compose $LIVE exec -T cron php bin/console app:backup-database --dry-run

# Custom retention (e.g., keep 60 days)
docker compose $LIVE exec -T cron php bin/console app:backup-database --retention-days=60
```

### 4.3 Pre-Deploy Backup

Always take a manual backup before risky deployments (migrations that alter/drop columns, large schema changes):

```bash
docker compose $LIVE exec -T cron php bin/console app:backup-database
```

### 4.4 Restore (Production)

The `app:restore-database` command is dev-only (`#[When(env: 'dev')]`). For production restore, SSH in and use mysql directly:

```bash
ssh ubuntu@<server-ip>
cd /home/ubuntu/supplymars

# 1. Download backup from S3
aws s3 ls s3://unicorn-bucket-two/backups/ --region eu-west-2
aws s3 cp s3://unicorn-bucket-two/backups/supplymars-2026-02-13-020001.sql.gz /tmp/

# 2. Stop the application (prevent writes during restore)
docker compose $LIVE stop php messenger cron

# 3. Restore
gunzip < /tmp/supplymars-2026-02-13-020001.sql.gz | \
    docker compose $LIVE exec -T database mysql -u root -p app

# 4. Restart services
docker compose $LIVE up -d php messenger cron

# 5. Verify
docker compose $LIVE ps
```

### 4.5 Verify a Backup

Periodically verify backups are valid (download and check, or test-restore on dev):

```bash
# Check file exists and has reasonable size on S3
aws s3 ls s3://unicorn-bucket-two/backups/ --region eu-west-2 --human-readable

# Check local backup exists
ls -lh /home/ubuntu/supplymars-backups/latest.sql.gz

# Dev: restore and verify
symfony console app:restore-database --from-s3
```

---

## 5. Maintenance Mode

### 5.1 Taking the App Offline

For planned maintenance (database migrations, infrastructure changes):

```bash
# 1. Stop cron (prevent new jobs)
docker compose $LIVE stop cron

# 2. Drain the Messenger worker (let it finish current messages, then stop)
docker compose $LIVE stop messenger

# 3. Stop the PHP container (app returns 502 via nginx)
docker compose $LIVE stop php
```

### 5.2 Bringing the App Back Online

```bash
# 1. Start PHP (runs migrations if pending)
docker compose $LIVE up -d php

# 2. Wait for PHP to become healthy
docker compose $LIVE ps php

# 3. Start Messenger and Cron
docker compose $LIVE up -d messenger cron

# 4. Verify all services
docker compose $LIVE ps
```

---

## 6. Troubleshooting

### 6.1 App Returns 502/504

**Symptoms:** Nginx returns 502 Bad Gateway or 504 Gateway Timeout.

**Diagnosis:**
```bash
docker compose $LIVE ps php
docker compose $LIVE logs --tail=100 php
docker compose $LIVE logs --tail=50 nginx
```

**Common causes:**
- PHP container crashed or is restarting (check logs for OOM or fatal errors)
- Migrations failed on startup (check entrypoint logs for migration errors)
- Database is down (PHP can't connect)

**Fix:**
```bash
docker compose $LIVE restart php

# If database is the issue, restart it first
docker compose $LIVE restart database
# Wait for healthy, then restart PHP
docker compose $LIVE restart php
```

### 6.2 Queue Backlog Growing

**Symptoms:** RabbitMQ queue depth increasing, async operations delayed.

**Diagnosis:**
```bash
docker compose $LIVE ps messenger
docker compose $LIVE exec -T php php bin/console messenger:stats
docker compose $LIVE logs --tail=100 messenger
```

**Fix:**
```bash
docker compose $LIVE restart messenger
```

### 6.3 Cron Jobs Not Running

**Symptoms:** No new orders being created, reports not updating, stale data.

**Diagnosis:**
```bash
docker compose $LIVE ps cron
docker compose $LIVE logs --tail=100 cron
docker compose $LIVE exec cron crontab -l
```

**Fix:**
```bash
docker compose $LIVE restart cron
```

### 6.4 Slow Pages

**Diagnosis:**
```bash
# MySQL process list for long-running queries
docker compose $LIVE exec -T database mysqladmin -u root -p processlist

# Container resource usage
docker stats --no-stream

# Redis stats
docker compose $LIVE exec redis redis-cli -a "$REDIS_PASSWORD" info stats | grep instantaneous_ops_per_sec
```

**Common causes:**
- Missing database indexes (check after schema changes)
- Redis eviction (memory pressure)
- Large report calculation running during peak traffic

### 6.5 Failed Messenger Messages

```bash
# View failed messages
docker compose $LIVE exec -T php php bin/console messenger:failed:show

# Retry all
docker compose $LIVE exec -T php php bin/console messenger:failed:retry

# Retry a specific message
docker compose $LIVE exec -T php php bin/console messenger:failed:retry <id>

# Remove a message that can't be retried
docker compose $LIVE exec -T php php bin/console messenger:failed:remove <id>
```

### 6.6 Disk Space

**Diagnosis:**
```bash
df -h
docker system df
```

**Fix:**
```bash
docker system prune -f
docker image prune -a -f
```

---

## 7. SSL Certificates

SSL is managed by **Caddy**, which auto-provisions and renews Let's Encrypt certificates. No manual certificate management is needed.

### 7.1 Check Status

```bash
sudo systemctl status caddy
sudo journalctl -u caddy --no-pager -n 50
```

### 7.2 Verify Certificates

```bash
curl -vI https://supplymars.com 2>&1 | grep "subject:"
curl -vI https://live.supplymars.com 2>&1 | grep "subject:"
```

### 7.3 Troubleshooting

If Caddy fails to provision certificates:

1. Ensure ports 80 and 443 are open in the Lightsail firewall
2. Ensure DNS A records point to the instance IP
3. Check Caddy logs: `sudo journalctl -u caddy --no-pager -n 100`
4. Restart: `sudo systemctl restart caddy`

---

## 8. Database Maintenance

### 8.1 Check Table Sizes

```bash
docker compose $LIVE exec -T database \
    mysql -u root -p -e "
    SELECT table_name,
           ROUND(data_length/1024/1024, 2) AS data_mb,
           ROUND(index_length/1024/1024, 2) AS index_mb,
           table_rows
    FROM information_schema.tables
    WHERE table_schema = 'app'
    ORDER BY data_length DESC
    LIMIT 20;
  "
```

### 8.2 Optimize Tables

Run periodically (monthly) for tables with heavy INSERT/DELETE activity:

```bash
docker compose $LIVE exec -T database \
    mysql -u root -p -e "
    ANALYZE TABLE app.purchase_order_item;
    ANALYZE TABLE app.customer_order_item;
    ANALYZE TABLE app.product_sales;
    ANALYZE TABLE app.order_sales;
    ANALYZE TABLE app.customer_sales;
  "
```

**Note:** `OPTIMIZE TABLE` locks the table briefly. Run during low-traffic periods.

### 8.3 Rebuild Reporting Data

If reporting data becomes inconsistent, rebuild from source:

```bash
docker compose $LIVE exec -T php php bin/console app:calculate-product-sales 90
docker compose $LIVE exec -T php php bin/console app:calculate-order-sales 90
docker compose $LIVE exec -T php php bin/console app:calculate-customer-sales 90
```

See [09-cli-reference.md](09-cli-reference.md) for full command options.
