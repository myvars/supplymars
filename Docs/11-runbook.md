# Production Runbook

Step-by-step operational procedures for the SupplyMars production environment on AWS Lightsail. For architecture details, cron schedules, and worker configuration, see [06-operations.md](06-operations.md). For all console commands, see [09-cli-reference.md](09-cli-reference.md).

---

## 1. Service Architecture Quick Reference

The production stack runs 7 Docker containers:

| Service | Image | Port | Healthcheck | Purpose |
|---------|-------|------|-------------|---------|
| nginx | nginx-prod | 80, 443 | `curl -kf https://localhost` | Reverse proxy, SSL termination |
| php | php-prod | 9000 (internal) | `php-fpm -t` | PHP-FPM application server |
| messenger | php-prod | — | `ps aux \| grep messenger:consume` | Async message consumer |
| cron | cron-prod | — | `ps aux \| grep cron` | Scheduled tasks |
| database | mysql:8.4 | 127.0.0.1:3306 | `mysqladmin ping` | Primary data store |
| redis | redis:8.4-alpine | 6379 (internal) | `redis-cli ping` | Cache, sessions |
| rabbitmq | rabbitmq:4.2-management | 5672 (internal) | `rabbitmq-diagnostics ping` | Message queue |

**Deploy path:** `/home/ubuntu/supplymars`

**Compose files:** `compose.yaml` + `compose.prod.yaml`

**Quick status:**
```bash
cd /home/ubuntu/supplymars
docker compose -f compose.yaml -f compose.prod.yaml ps
```

---

## 2. Deployment

### 2.1 Automated (CI/CD)

Merging to `main` triggers the GitHub Actions pipeline (`.github/workflows/ci.yml`):

1. Runs code quality checks (PHP-CS-Fixer, PHPStan, Doctrine schema validation)
2. Runs the full test suite
3. SSHs to the Lightsail instance
4. `git fetch origin && git reset --hard origin/main && git clean -fd`
5. `docker compose -f compose.yaml -f compose.prod.yaml up -d --build --remove-orphans --wait`

The PHP container runs migrations automatically on startup (`RUN_MIGRATIONS=true` in `docker-entrypoint.sh`).

### 2.2 Manual Deployment

Use when CI is down or for emergency hotfixes:

```bash
# 1. SSH to the server
ssh ubuntu@<server-ip>

# 2. Navigate to project
cd /home/ubuntu/supplymars

# 3. Pull latest code
git fetch origin
git reset --hard origin/main
git clean -fd

# 4. Export environment variables (source from .env.prod or set manually)
export APP_ENV=prod
export APP_SECRET=<secret>
export DATABASE_URL=<url>
# ... (all variables from compose.prod.yaml)

# 5. Rebuild and restart containers
docker compose -f compose.yaml -f compose.prod.yaml up -d --build --remove-orphans --wait

# 6. Verify (see Health Checks below)
docker compose -f compose.yaml -f compose.prod.yaml ps
```

### 2.3 Rollback

There is no blue-green or canary deployment. Rollback means deploying a previous commit:

```bash
ssh ubuntu@<server-ip>
cd /home/ubuntu/supplymars

# Find the commit to roll back to
git log --oneline -10

# Reset to a known-good commit
git reset --hard <commit-sha>

# Rebuild
docker compose -f compose.yaml -f compose.prod.yaml up -d --build --remove-orphans --wait
```

**If the rollback involves a migration reversal**, you must manually revert the migration:
```bash
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console doctrine:migrations:migrate prev --no-interaction
```

---

## 3. Health Checks

### 3.1 Post-Deploy Verification

Run after every deployment:

```bash
cd /home/ubuntu/supplymars

# 1. All 7 services healthy
docker compose -f compose.yaml -f compose.prod.yaml ps
# Expect: all services show "healthy"

# 2. App responds
curl -kf https://localhost
# Expect: HTTP 200

# 3. Migration status
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console doctrine:migrations:status
# Expect: no pending migrations

# 4. Messenger transport
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console messenger:stats
# Expect: queue counts shown, no errors

# 5. Cron is running
docker compose -f compose.yaml -f compose.prod.yaml exec cron \
  ps aux | grep cron
# Expect: cron process visible

# 6. Check application logs for errors
docker compose -f compose.yaml -f compose.prod.yaml logs --tail=50 php
```

### 3.2 Ongoing Monitoring

```bash
# Container resource usage
docker stats --no-stream

# Check for failed Messenger messages
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console messenger:failed:show

# Check cron output
docker compose -f compose.yaml -f compose.prod.yaml logs --tail=50 cron

# MySQL process list (check for long-running queries)
docker compose -f compose.yaml -f compose.prod.yaml exec database \
  mysqladmin -u root -p processlist
```

---

## 4. Backup & Restore

### 4.1 Automated Daily Backup

The cron container runs `app:backup-database` daily at 02:00 UTC (see `docker/php/cron/prod-crontab`):

- Creates a gzipped mysqldump (`supplymars-YYYY-MM-DD-HHmmss.sql.gz`)
- Uploads to the S3 backups filesystem (`unicorn-bucket-two/backups/`)
- Deletes backups older than 30 days (default retention)

Backup logs appear in `docker compose logs cron`.

### 4.2 Manual Backup

Trigger a backup outside the cron schedule:

```bash
# Run backup
docker compose -f compose.yaml -f compose.prod.yaml exec cron \
  php bin/console app:backup-database

# Dry run (see what would happen)
docker compose -f compose.yaml -f compose.prod.yaml exec cron \
  php bin/console app:backup-database --dry-run

# Custom retention (e.g., keep 60 days)
docker compose -f compose.yaml -f compose.prod.yaml exec cron \
  php bin/console app:backup-database --retention-days=60
```

### 4.3 Pre-Deploy Backup

Always take a manual backup before risky deployments (migrations that alter/drop columns, large schema changes):

```bash
ssh ubuntu@<server-ip>
cd /home/ubuntu/supplymars
docker compose -f compose.yaml -f compose.prod.yaml exec cron \
  php bin/console app:backup-database
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
docker compose -f compose.yaml -f compose.prod.yaml stop php messenger cron

# 3. Restore
gunzip < /tmp/supplymars-2026-02-13-020001.sql.gz | \
  docker compose -f compose.yaml -f compose.prod.yaml exec -T database \
  mysql -u root -p supplymars

# 4. Restart services
docker compose -f compose.yaml -f compose.prod.yaml up -d php messenger cron

# 5. Verify (run health checks)
docker compose -f compose.yaml -f compose.prod.yaml ps
```

### 4.5 Verify a Backup

Periodically verify backups are valid (download and check, or test-restore on dev):

```bash
# Check file exists and has reasonable size on S3
aws s3 ls s3://unicorn-bucket-two/backups/ --region eu-west-2 --human-readable
# Expect: recent files, several MB each

# Dev: restore and verify
symfony console app:restore-database --from-s3
```

---

## 5. Maintenance Mode

### 5.1 Taking the App Offline

For planned maintenance (database migrations, infrastructure changes):

```bash
ssh ubuntu@<server-ip>
cd /home/ubuntu/supplymars

# 1. Stop cron (prevent new jobs)
docker compose -f compose.yaml -f compose.prod.yaml stop cron

# 2. Drain the Messenger worker (let it finish current messages, then stop)
docker compose -f compose.yaml -f compose.prod.yaml stop messenger

# 3. Stop the PHP container (app returns 502 via nginx)
docker compose -f compose.yaml -f compose.prod.yaml stop php
```

### 5.2 Bringing the App Back Online

```bash
cd /home/ubuntu/supplymars

# 1. Start PHP (runs migrations if pending)
docker compose -f compose.yaml -f compose.prod.yaml up -d php

# 2. Wait for PHP to become healthy
docker compose -f compose.yaml -f compose.prod.yaml ps php
# Wait until status shows "healthy"

# 3. Start Messenger and Cron
docker compose -f compose.yaml -f compose.prod.yaml up -d messenger cron

# 4. Verify all services
docker compose -f compose.yaml -f compose.prod.yaml ps
```

---

## 6. Troubleshooting

### 6.1 App Returns 502/504

**Symptoms:** Nginx returns 502 Bad Gateway or 504 Gateway Timeout.

**Diagnosis:**
```bash
# Check PHP-FPM is running
docker compose -f compose.yaml -f compose.prod.yaml ps php
# If unhealthy or restarting:
docker compose -f compose.yaml -f compose.prod.yaml logs --tail=100 php

# Check nginx can reach PHP
docker compose -f compose.yaml -f compose.prod.yaml logs --tail=50 nginx
```

**Common causes:**
- PHP container crashed or is restarting (check logs for OOM or fatal errors)
- Migrations failed on startup (check entrypoint logs for migration errors)
- Database is down (PHP can't connect)

**Fix:**
```bash
# Restart PHP
docker compose -f compose.yaml -f compose.prod.yaml restart php

# If database is the issue, restart it first
docker compose -f compose.yaml -f compose.prod.yaml restart database
# Wait for healthy, then restart PHP
docker compose -f compose.yaml -f compose.prod.yaml restart php
```

### 6.2 Queue Backlog Growing

**Symptoms:** RabbitMQ queue depth increasing, async operations delayed.

**Diagnosis:**
```bash
# Check Messenger worker is running
docker compose -f compose.yaml -f compose.prod.yaml ps messenger

# Check queue stats
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console messenger:stats

# Check worker logs
docker compose -f compose.yaml -f compose.prod.yaml logs --tail=100 messenger
```

**Common causes:**
- Messenger worker crashed and hasn't restarted
- Slow consumer (database bottleneck, external service timeout)
- Spike in async events (pricing cascade after bulk update)

**Fix:**
```bash
# Restart the worker
docker compose -f compose.yaml -f compose.prod.yaml restart messenger
```

### 6.3 Cron Jobs Not Running

**Symptoms:** No new orders being created, reports not updating, stale data.

**Diagnosis:**
```bash
# Check cron container
docker compose -f compose.yaml -f compose.prod.yaml ps cron

# Check cron log
docker compose -f compose.yaml -f compose.prod.yaml logs --tail=100 cron

# Verify crontab is installed
docker compose -f compose.yaml -f compose.prod.yaml exec cron \
  crontab -l
```

**Common causes:**
- Cron container not running
- Environment variables not available to cron (check `/etc/environment`)
- Command failing silently (check exit codes in log)

**Fix:**
```bash
# Restart cron container
docker compose -f compose.yaml -f compose.prod.yaml restart cron
```

### 6.4 Slow Pages

**Diagnosis:**
```bash
# Check MySQL slow query log
docker compose -f compose.yaml -f compose.prod.yaml exec database \
  mysqladmin -u root -p status

# Check MySQL process list for long-running queries
docker compose -f compose.yaml -f compose.prod.yaml exec database \
  mysqladmin -u root -p processlist

# Check Redis
docker compose -f compose.yaml -f compose.prod.yaml exec redis \
  redis-cli info stats | grep instantaneous_ops_per_sec

# Check container resource usage
docker stats --no-stream
```

**Common causes:**
- Missing database indexes (check after schema changes)
- Redis eviction (memory pressure)
- Large report calculation running during peak traffic

### 6.5 Failed Messenger Messages

**Diagnosis:**
```bash
# View failed messages
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console messenger:failed:show

# View a specific failed message
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console messenger:failed:show <id>
```

**Fix:**
```bash
# Retry all failed messages
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console messenger:failed:retry

# Retry a specific message
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console messenger:failed:retry <id>

# Remove a message that can't be retried
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console messenger:failed:remove <id>
```

### 6.6 Disk Space

**Diagnosis:**
```bash
# Check host disk usage
df -h

# Check Docker disk usage
docker system df

# Check database volume size
docker volume ls
du -sh /var/lib/docker/volumes/supplymars_db-data/
```

**Fix:**
```bash
# Remove unused Docker images and build cache
docker system prune -f
docker image prune -a -f

# Remove old backup files if stored locally
find /tmp -name "db_backup_*" -mtime +1 -delete
```

---

## 7. Certificate Renewal

SSL certificates are managed by Let's Encrypt (certbot) on the host, mounted into the nginx container.

**Certificate paths (host):**
- Certificate: `/etc/letsencrypt/live/supplymars.com/fullchain.pem`
- Private key: `/etc/letsencrypt/live/supplymars.com/privkey.pem`

**Certificate paths (nginx container, mounted read-only):**
- `/etc/ssl/certs/fullchain.pem`
- `/etc/ssl/private/privkey.pem`

### 7.1 Check Expiry

```bash
# On the host
sudo certbot certificates
# or
openssl x509 -enddate -noout -in /etc/letsencrypt/live/supplymars.com/fullchain.pem
```

### 7.2 Renew

Certbot typically auto-renews via a systemd timer or cron. To renew manually:

```bash
# Stop nginx temporarily (certbot needs port 80)
cd /home/ubuntu/supplymars
docker compose -f compose.yaml -f compose.prod.yaml stop nginx

# Renew
sudo certbot renew

# Restart nginx (picks up new certs via volume mount)
docker compose -f compose.yaml -f compose.prod.yaml up -d nginx
```

### 7.3 Verify

```bash
# Check the cert served by nginx
echo | openssl s_client -connect supplymars.com:443 -servername supplymars.com 2>/dev/null | \
  openssl x509 -noout -dates
```

---

## 8. Database Maintenance

### 8.1 Check Table Sizes

```bash
docker compose -f compose.yaml -f compose.prod.yaml exec database \
  mysql -u root -p -e "
    SELECT table_name,
           ROUND(data_length/1024/1024, 2) AS data_mb,
           ROUND(index_length/1024/1024, 2) AS index_mb,
           table_rows
    FROM information_schema.tables
    WHERE table_schema = 'supplymars'
    ORDER BY data_length DESC
    LIMIT 20;
  "
```

### 8.2 Optimize Tables

Run periodically (monthly) for tables with heavy INSERT/DELETE activity:

```bash
docker compose -f compose.yaml -f compose.prod.yaml exec database \
  mysql -u root -p -e "
    ANALYZE TABLE supplymars.purchase_order_item;
    ANALYZE TABLE supplymars.customer_order_item;
    ANALYZE TABLE supplymars.product_sales;
    ANALYZE TABLE supplymars.order_sales;
    ANALYZE TABLE supplymars.customer_sales;
  "
```

For tables with significant fragmentation (check `DATA_FREE` in `information_schema.tables`):

```bash
docker compose -f compose.yaml -f compose.prod.yaml exec database \
  mysql -u root -p -e "OPTIMIZE TABLE supplymars.<table_name>;"
```

**Note:** `OPTIMIZE TABLE` locks the table briefly. Run during low-traffic periods.

### 8.3 Rebuild Reporting Data

If reporting data becomes inconsistent, rebuild from source:

```bash
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console app:calculate-product-sales 90
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console app:calculate-order-sales 90
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  php bin/console app:calculate-customer-sales 90
```

See [09-cli-reference.md](09-cli-reference.md) for full command options.
