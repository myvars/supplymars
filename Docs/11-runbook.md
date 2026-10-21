# Production Runbook

Step-by-step operational procedures for the SupplyMars production environment on AWS Lightsail. For architecture details, scheduled jobs, and worker configuration, see [06-operations.md](06-operations.md). For all console commands, see [09-cli-reference.md](09-cli-reference.md). For playground-specific details, see [infrastructure/playground.md](infrastructure/playground.md).

---

## 1. Service Architecture Quick Reference

Two Docker Compose stacks run on the same Lightsail instance, fronted by Caddy (auto TLS via Let's Encrypt):

| Stack | Project Name | Port (`HTTP_HOST_PORT`) | Domain |
|-------|-------------|------------------------|--------|
| Live | `supplymars-live` | 8001 | `live.supplymars.com` |
| Playground | `supplymars-playground` | 8002 | `supplymars.com`, `www.supplymars.com` (the public demo) |

Each stack runs 4 containers (5 for playground, which includes `reset`). The queue is a table in the stack's own database; RabbitMQ is an opt-in and does not run ([ADR 013](adr/013-database-queue-by-default.md)):

| Service | Image | Purpose |
|---------|-------|---------|
| php | `ghcr.io/myvars/supplymars/php` | FrankenPHP: web server and PHP in one, in worker mode (HTTP only — Caddy on the host handles TLS). Runs as `www-data` ([ADR 016](adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md)) |
| messenger | `ghcr.io/myvars/supplymars/php` | Async message consumer, and the scheduled jobs (`src/Shared/Infrastructure/Scheduler/MainSchedule.php`; `PLAYGROUND_MODE` picks the stack's) |
| database | mysql:8.4 | Primary data store |
| redis | redis:8.8-alpine | Cache, sessions |
| reset | `ghcr.io/myvars/supplymars/reset` | Nightly DB reset at 02:15, on its own cron (playground only) |

**CI builds, the server runs.** The two application images are built by GitHub Actions from a commit on `main`, tagged with its SHA and pushed to the GitHub Container Registry (GHCR). The server pulls the images of the commit it has checked out and starts them; it never builds. The checkout supplies only the compose files and the Makefile: the code is inside the images.

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

The Makefile's `LIVE` and `PLAYGROUND` variables are the same flags. Start or recreate containers only with `make up-prod-live` and `make up-prod-playground`, which run the images of the checked-out commit. A bare `docker compose $LIVE up` does not know the commit: it looks for images tagged `local` and builds them on the server. `ps`, `logs`, `exec`, `stop` and `restart` are safe to run directly.

### Where Secrets Live

| Secret | Lives in | Used by |
|--------|----------|---------|
| Application configuration: `APP_SECRET`, database, Redis, mailer, S3, Sentry, Turnstile | `/home/ubuntu/supplymars-secrets/{live,playground}.env` on the server. The master copies are in `../supplymars-secrets/` on the maintainer's machine and are copied up with `scp` | `make up-prod-live`, `make up-prod-playground` |
| Registry read token | `~/.docker/config.json` on the server, written once by `docker login ghcr.io` ([playground.md](infrastructure/playground.md#fresh-server-setup)) | Pulling the images |
| Deploy SSH key, server address and user | GitHub repository secrets `SERVER_SSH_KEY`, `SERVER_HOST`, `SERVER_USER` | The deploy job |

Application secrets are never GitHub secrets: the deploy job does not see them, and changing one does not need a deploy. After editing an env file:

```bash
scp ../supplymars-secrets/live.env ubuntu@<server-ip>:/home/ubuntu/supplymars-secrets/live.env
ssh ubuntu@<server-ip> 'cd /home/ubuntu/supplymars && make up-prod-live'
```

`make up-prod-live` recreates the containers whose environment changed.

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

1. **test**: each step of `make check`: the production config check, code style, Rector, PHPStan, the Symfony linters, `composer audit`, migrations from an empty database, and the full test suite (see [07-testing.md](07-testing.md#running-all-checks))
2. **publish**, once the tests pass: builds the `php` and `reset` production images from that commit and pushes them to `ghcr.io/myvars/supplymars/`, tagged with its SHA. The tests ran against the development image of the same commit; the production images are built from the same code but are not tested separately
3. **deploy**, once they are pushed, over SSH:

    ```bash
    cd /home/ubuntu/supplymars
    git fetch origin
    git reset --hard <sha>              # the compose files and Makefile of that commit
    git clean -fd
    mkdir -p /home/ubuntu/supplymars-backups
    make up-prod-live                   # pull that commit's images and start them; waits until healthy
    make up-prod-playground
    make verify-prod-live               # /health answers 200 on the port Caddy uses; no migration is pending
    make verify-prod-playground
    ```

The PHP container runs migrations automatically on startup (`RUN_MIGRATIONS=true` in `docker-entrypoint.sh`). If any step fails, the job stops and fails, and its log in the Actions tab shows which step. Deploys are queued, never cancelled part-way (`concurrency: ci-deploy`).

### 2.2 Manual Deployment

Use when the deploy job failed after the images were published, or to redeploy:

```bash
ssh ubuntu@<server-ip>
cd /home/ubuntu/supplymars

git fetch origin
git reset --hard origin/main

make up-prod-live           # runs the images of the checked-out commit
make up-prod-playground
make verify-prod-live
make verify-prod-playground
```

This only works for a commit whose images CI has published. If they are missing, the pull fails and nothing is restarted.

The server does not build. If the publish job or GHCR is unavailable, a new commit waits until it is back; the running version, and any earlier published one (section 2.3), keeps working.

### 2.3 Rollback

Check out the previous commit and start its images:

```bash
git reset --hard <previous-sha> && make up-prod-live && make up-prod-playground
```

Then run `make verify-prod-live` and `make verify-prod-playground`. Every published commit's images stay in GHCR. Take `<previous-sha>` from the last successful deploy run in the Actions tab: only commits that went through a full CI run on `main` have images (a pull of any other fails and changes nothing), and a commit from before the image pipeline brings back the old Makefile, which builds on the server. The checkout decides which images run, so a later `make up-prod-live` (after an env file change or a restore) keeps running the rolled-back images.

**If the bad commit added a migration**, rolling back the code does not roll back the schema. Take a backup (section 4.2), then revert the migration with the current images, before checking out the previous commit:

```bash
docker compose $LIVE exec php php bin/console doctrine:migrations:migrate prev --no-interaction
docker compose $PLAYGROUND exec php php bin/console doctrine:migrations:migrate prev --no-interaction
```

The next push to `main` deploys as normal. To stay on the old version until a fix is ready, revert the bad commit on `main` instead, which deploys through CI.

### 2.4 Restarting the PHP Workers

The `php` container runs FrankenPHP in worker mode: each worker keeps the app in memory and serves up to 500 requests (`FRANKENPHP_LOOP_MAX` in `compose.prod.yaml`) before it starts afresh. A deploy recreates the container, so every deploy starts new workers on the new code.

To restart them by hand, for example if the container's memory keeps growing (`docker stats`):

```bash
docker compose $LIVE exec php curl -fsS -X POST http://localhost:2019/frankenphp/workers/restart
docker compose $PLAYGROUND exec php curl -fsS -X POST http://localhost:2019/frankenphp/workers/restart
```

This goes through FrankenPHP's admin API, which listens inside the container only. Requests in progress finish first, and the site stays up. `docker compose $LIVE restart php` also works, but the site is down for the few seconds it takes to start.

---

## 3. Health Checks

### 3.1 Post-Deploy Verification

Run after every deployment:

```bash
cd /home/ubuntu/supplymars

# 1. All services healthy
docker compose $LIVE ps
docker compose $PLAYGROUND ps

# 2. Apps and their dependencies respond (via Caddy); a 503 names the failed check
curl -s -w " %{http_code}\n" https://supplymars.com/health            # 200 (playground)
curl -s -w " %{http_code}\n" https://live.supplymars.com/health       # 200 (live)

# 3. Migrations up to date
docker compose $LIVE exec -T php php bin/console doctrine:migrations:up-to-date
docker compose $PLAYGROUND exec -T php php bin/console doctrine:migrations:up-to-date

# 4. Messenger transport
docker compose $LIVE exec -T php php bin/console messenger:stats

# 5. Check application logs for errors
docker compose $LIVE logs --tail=50 php
```

### 3.2 Ongoing Monitoring

Errors are reported to Sentry and an external uptime monitor calls `/health` on both stacks ([13-monitoring.md](13-monitoring.md)). By hand:

```bash
# Container resource usage
docker stats --no-stream

# Check for failed Messenger messages
docker compose $LIVE exec -T php php bin/console messenger:failed:show

# Scheduled jobs: what runs when, and the worker's log (failed runs also reach Sentry)
docker compose $LIVE exec -T messenger php bin/console debug:scheduler
docker compose $LIVE logs --tail=50 messenger

# MySQL process list (check for long-running queries)
docker compose $LIVE exec -T database mysqladmin -u root -p processlist
```

---

## 4. Backup & Restore

### 4.1 Automated Daily Backup

The live messenger container runs `app:backup-database --local-copy=/backups/latest.sql.gz` daily at 02:00 UTC, as a scheduled job (`src/Shared/Infrastructure/Scheduler/MainSchedule.php`). It is first of the jobs due at 02:00, and the worker runs one job at a time:

- Creates a gzipped mysqldump (`supplymars-YYYY-MM-DD-HHmmss.sql.gz`)
- Uploads to the S3 backups filesystem (`unicorn-bucket-two/backups/`)
- Saves a local copy to `/backups/latest.sql.gz` (host: `/home/ubuntu/supplymars-backups/latest.sql.gz`), readable by its owner only (`0600`)
- Deletes S3 backups older than 30 days (default retention)

The local copy is used by the playground reset at 02:15 UTC. The worker runs as `www-data` (uid 33), so the host directory must be its: `sudo chown -R 33:33 /home/ubuntu/supplymars-backups` once on a new server. Otherwise the S3 upload still succeeds, the local copy fails (reported to Sentry), and the playground keeps restoring an old `latest.sql.gz`.

Backup errors appear in `docker compose $LIVE logs messenger`; a successful run's output is not logged (`RunCommandMessage` keeps it). A failed run, or one skipped because a manual backup holds the lock, exits with a failure and reaches Sentry as `RunCommandFailedException`. It is not retried: the next run is the next night. What tells you backups have stopped, for any reason, is the uptime monitor on `/health/backup`, which fails once the newest backup on S3 is more than 26 hours old ([13-monitoring.md](13-monitoring.md#backup-check)).

### 4.2 Manual Backup

Trigger a backup outside the schedule:

```bash
# Run backup (S3 + local copy)
docker compose $LIVE exec -T messenger \
    php bin/console app:backup-database --local-copy=/backups/latest.sql.gz

# S3 only (no local copy)
docker compose $LIVE exec -T messenger php bin/console app:backup-database

# Dry run (see what would happen)
docker compose $LIVE exec -T messenger php bin/console app:backup-database --dry-run

# Custom retention (e.g., keep 60 days)
docker compose $LIVE exec -T messenger php bin/console app:backup-database --retention-days=60
```

Only one backup runs at a time. A run that starts while another is still going does not wait: it prints a warning and exits with a failure, without dumping anything. If that happens to the 02:00 run, `/backups/latest.sql.gz` is whatever the run holding the lock leaves there, or the previous night's copy, and the 02:15 playground reset restores that. The copy is written beside the target and renamed into place, so the reset never reads a half-written file. The lock (`LOCK_DSN=flock`) is a file in the container, so run a manual backup in the `messenger` container, where the scheduler runs, as above. One started in the `php` container takes a different lock and can run at the same time as the nightly one.

**Scheduled commands of your own.** Add a `RecurringMessage::cron()` with a `RunCommandMessage` to `MainSchedule`, inside the `PLAYGROUND_MODE` check if it is for live only, and to `MainScheduleTest`. The schedule is locked and the worker runs one job at a time, so a scheduled command never overlaps its own previous run. It can still overlap a manual run: a command where that matters takes the same lock as `BackupDatabaseCommand` (`LockableTrait` with the `LockFactory`, returning early when `lock()` fails). A job that takes more than a few seconds delays async messages; one that takes minutes is the point to give `scheduler_default` a worker of its own ([ADR 015](adr/015-symfony-scheduler-in-place-of-cron.md)).

### 4.3 Pre-Deploy Backup

Always take a manual backup before risky deployments (migrations that alter/drop columns, large schema changes):

```bash
docker compose $LIVE exec -T messenger php bin/console app:backup-database
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
docker compose $LIVE stop php messenger

# 3. Restore
gunzip < /tmp/supplymars-2026-02-13-020001.sql.gz | \
    docker compose $LIVE exec -T database mysql -u root -p app

# 4. Start the app again (php runs any migrations newer than the backup), then check it
make up-prod-live
make verify-prod-live
```

The queue is a table in the same database, so the restore brings back the emails that were waiting when the backup was taken, and the worker sends them when it starts. If they were already sent, delete them before step 4: `DELETE FROM messenger_messages WHERE queue_name = 'default';` (`messenger:stats` shows how many there are).

### 4.5 Verify a Backup

Quick checks that a backup exists. To show that it can be restored, do the rehearsal in 4.6.

```bash
# Check file exists and has reasonable size on S3
aws s3 ls s3://unicorn-bucket-two/backups/ --region eu-west-2 --human-readable

# Check local backup exists
ls -lh /home/ubuntu/supplymars-backups/latest.sql.gz

# Dev: restore and verify
symfony console app:restore-database --from-s3
```

### 4.6 Restore Rehearsal

Once a month, restore last night's backup on your own machine and use the app. A backup is only known to be good once it has been restored.

1. `make up-dev-tools`, then `symfony console app:restore-database --from-s3`. It asks for the production S3 keys (from `../supplymars-secrets/live.env`), downloads the newest backup to `var/backups/` and replaces the dev database with it.
2. `symfony serve -d`, log in and open the dashboard, an order, a product and a purchase order. The newest orders should be from the previous evening.
3. Delete the downloaded file from `var/backups/` when done: it is a full copy of production.

If any step fails, treat it as a broken backup and fix it before the next one is needed.

---

## 5. Maintenance Mode

### 5.1 Taking the App Offline

For planned maintenance (database migrations, infrastructure changes):

```bash
# 1. Stop the Messenger worker, so no new jobs start. It cannot finish the job in hand: Docker kills it
#    after 30 seconds (stop_grace_period). A job cut short, and jobs missed while it is down, run once
#    when it is back. Check first that the 02:00 backup is not running (docker top)
docker compose $LIVE stop messenger

# 2. Stop the PHP container (the host Caddy returns 502)
docker compose $LIVE stop php
```

### 5.2 Bringing the App Back Online

```bash
# Starts PHP first (it runs pending migrations), then Messenger once PHP is healthy
make up-prod-live
make verify-prod-live
```

---

## 6. Troubleshooting

### 6.1 App Returns 502/504

**Symptoms:** The host Caddy returns 502 Bad Gateway or 504 Gateway Timeout.

**Diagnosis:**
```bash
docker compose $LIVE ps php
docker compose $LIVE logs --tail=100 php    # FrankenPHP's own log lines, then the app's
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

**Symptoms:** `async` count in `messenger:stats` increasing, emails delayed. The same worker runs the scheduled jobs, so a long-running job (the backup at 02:00) delays emails until it ends.

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

### 6.3 Scheduled Jobs Not Running

**Symptoms:** No new orders being created, reports not updating, stale data.

**Diagnosis:**
```bash
docker compose $LIVE ps messenger
# The worker must consume scheduler_default as well as async
docker compose $LIVE exec -T messenger ps aux | grep messenger:consume
# The jobs and their next run times (PLAYGROUND_MODE picks the stack's)
docker compose $LIVE exec -T messenger php bin/console debug:scheduler
docker compose $LIVE logs --tail=100 messenger
```

**Fix:**
```bash
docker compose $LIVE restart messenger
```

### 6.4 Deploy Timed Out

**Symptoms:** the deploy job fails with `Run Command Timeout`; the site may return 502.

When the SSH session ends, a `docker compose up` already started keeps running on the server, but nothing after it does (the other stack, the verify steps). It can leave containers `Created` and never started: the old ones already removed, the new ones not running.

```bash
# 1. Is a compose run from the deploy still going? Let it finish, or Ctrl-C it, before starting another
pgrep -af "docker compose"

# 2. What it left behind: Created means replaced but never started
docker compose $LIVE ps -a

# 3. Finish the deploy by hand, for both stacks
make up-prod-live && make up-prod-playground
make verify-prod-live && make verify-prod-playground
```

On 2026-10-21 the deploy of #81 stopped this way: Compose waited for the messenger worker, which ignores `SIGTERM`, through a 15-minute `stop_grace_period` (now 30 seconds, #83). Live was down for about 20 minutes, until step 3.

### 6.5 Signed-In Users Get a 500 After a Deploy

**Symptoms:** after a deploy, every page fails for users who were signed in before it, while new visitors are fine. The log shows a `TypeError` from `unserialize()` on a `User` property.

The session in Redis holds the signed-in `User` object, serialized by the previous code. When a property of `User` changes type, PHP cannot restore the old session, and Symfony does not catch the error. Clear the sessions on both stacks. Everyone is signed out once, and remember-me signs them back in.

```bash
# Session keys start with sf_s (the session handler's default prefix); cache keys are left alone
docker compose $LIVE exec redis sh -c 'export REDISCLI_AUTH="$REDIS_PASSWORD"; redis-cli --scan --pattern "sf_s*" | xargs -r redis-cli unlink'
docker compose $PLAYGROUND exec redis sh -c 'export REDISCLI_AUTH="$REDIS_PASSWORD"; redis-cli --scan --pattern "sf_s*" | xargs -r redis-cli unlink'
```

#87 needed this: `publicId` changed from a string to a `Ulid`.

### 6.6 Slow Pages

**Diagnosis:**
```bash
# MySQL process list for long-running queries
docker compose $LIVE exec -T database mysqladmin -u root -p processlist

# Container resource usage
docker stats --no-stream

# Redis stats
docker compose $LIVE exec redis sh -c 'REDISCLI_AUTH="$REDIS_PASSWORD" redis-cli info stats' | grep instantaneous_ops_per_sec
```

**Common causes:**
- Missing database indexes (check after schema changes)
- Redis eviction (memory pressure)
- Large report calculation running during peak traffic

### 6.7 Failed Messenger Messages

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

### 6.8 Disk Space

**Diagnosis:**
```bash
df -h
docker system df
```

**Fix:**

Every deploy leaves its images on the server, so a rollback can start without a download. Remove images not used by a container and older than 30 days:

```bash
docker system prune -f
docker image prune -a -f --filter "until=720h"
```

A pruned version can still be rolled back to: `make up-prod-live` pulls it again from GHCR.

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
