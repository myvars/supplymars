# Production Runbook

How to run the two production stacks on the Lightsail server: what runs where, the env files, first-time set-up, deploy, rollback, backup and restore, and what to do when something breaks. Read it before touching the server. Scheduled jobs and the worker are described in [06-operations.md](06-operations.md), what each command does in [09-cli-reference.md](09-cli-reference.md), the playground's own behaviour in [infrastructure/playground.md](infrastructure/playground.md), and error tracking and `/health` in [13-monitoring.md](13-monitoring.md).

## 1. How it fits together

```
visitor ──https──▶ Caddy (host, 80/443, TLS) ──http──▶ 127.0.0.1:8001  supplymars-live        live.supplymars.com
                                                └────▶ 127.0.0.1:8002  supplymars-playground  www.supplymars.com
```

`supplymars.com` redirects to `www.supplymars.com` (301): each stack has one origin, its `DEFAULT_URI`, or the CSP blocks its Mercure hub ([6.2](#62-live-updates-or-toasts-not-arriving)).

The names read the wrong way round: `www.supplymars.com` is the public playground (the demo visitors are given) and `live.supplymars.com` is the live site, which the playground is restored from each night.

Each stack has its own containers, database, Redis and queue (a table in its database; RabbitMQ is an opt-in and does not run, [ADR 013](adr/013-database-queue-by-default.md)):

| Service | Image | Purpose |
|---------|-------|---------|
| php | `ghcr.io/myvars/supplymars/php` | FrankenPHP in worker mode, plain HTTP on localhost; Caddy on the host does TLS. Runs as `www-data` ([ADR 016](adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md)) |
| messenger | `ghcr.io/myvars/supplymars/php` | The queue and the scheduled jobs (`src/Shared/Infrastructure/Scheduler/MainSchedule.php`; `PLAYGROUND_MODE` picks the stack's) |
| database | `mysql:8.4` | Settings sized for this small server (`docker/mysql/production.cnf`, [06-operations.md](06-operations.md#database-optimization)) |
| redis | `redis:8.8-alpine` | Cache and sessions |
| reset | `ghcr.io/myvars/supplymars/reset` | Playground only: the nightly restore from live's backup at 02:15 UTC, on its own cron |

**CI builds, the server runs.** GitHub Actions builds the `php` and `reset` images from a commit on `main`, tags them with its SHA and pushes them to GHCR. The server pulls the images of the commit it has checked out and starts them; it never builds. The checkout supplies only the compose files and the Makefile.

| What | Where |
|------|-------|
| Checkout | `/home/ubuntu/supplymars` |
| Env files | `/home/ubuntu/supplymars-secrets/{live,playground}.env` |
| Latest backup (local copy) | `/home/ubuntu/supplymars-backups/latest.sql.gz`, mounted at `/backups` in live's `messenger` and (read-only) playground's `reset` |
| Backups on S3 | `s3://unicorn-bucket-two/backups/` |
| Caddy config | `/etc/caddy/Caddyfile`; reference copy `Docs/infrastructure/Caddyfile` |

### Compose Command Prefix

Every `docker compose` command needs the env file and project name. This runbook uses `$LIVE` and `$PLAYGROUND`, the same flags as the Makefile's variables. Set them once per shell, from the checkout:

```bash
cd /home/ubuntu/supplymars
LIVE="--env-file ../supplymars-secrets/live.env -p supplymars-live -f compose.yaml -f compose.prod.yaml"
PLAYGROUND="--env-file ../supplymars-secrets/playground.env -p supplymars-playground --profile playground -f compose.yaml -f compose.prod.yaml"

# MySQL as root in the app's database. The password and database name come from the container's own
# environment, so nothing is typed and stdin stays free for a piped dump
mysql_live() { docker compose $LIVE exec -T database sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" "$@"' sh "$@"; }
```

Start or recreate containers only with `make up-prod-live` and `make up-prod-playground`. They pull and run the images of the checked-out commit (`IMAGE_TAG :=` in the Makefile, so a stale `IMAGE_TAG` in the shell cannot pick other images). A bare `docker compose $LIVE up` does not know the commit: it looks for images tagged `local` and builds them on the server. `ps`, `logs`, `exec`, `stop` and `restart` are safe to run directly. Leave out `--profile playground` and the playground's `reset` container does not show in `ps` or `logs`.

### Where Secrets Live

| Secret | Lives in | Used by |
|--------|----------|---------|
| Application configuration (`APP_SECRET`, database, Redis, mailer, S3, Sentry, Turnstile) | The env files on the server. The master copies are in `../supplymars-secrets/` on the maintainer's machine, copied up with `scp` | `make up-prod-live`, `make up-prod-playground` |
| Registry read token | `~/.docker/config.json` on the server, written once by `docker login ghcr.io` | Pulling the images |
| Deploy SSH key, server address and user | GitHub repository secrets `SERVER_SSH_KEY`, `SERVER_HOST`, `SERVER_USER` | The deploy job |

Application secrets are never GitHub secrets: the deploy job does not see them, and changing one does not need a deploy. After editing an env file:

```bash
scp ../supplymars-secrets/live.env ubuntu@<server-ip>:/home/ubuntu/supplymars-secrets/live.env
ssh ubuntu@<server-ip> 'cd /home/ubuntu/supplymars && make up-prod-live'
```

`make up-prod-live` recreates the containers whose environment changed.

### Production Env Files

`make up-prod-live` reads `live.env`, `make up-prod-playground` reads `playground.env`. `make up-prod-local` reads `prod-local.env`, the same variables with throwaway values, to run the production build on your own machine (http://127.0.0.1:8000, `compose.prod.local.yaml`; no scheduled jobs run there).

An env file only reaches the app through `compose.prod.yaml`: the variables listed in its `x-common-php-env` block go into the containers, and the others are used by Compose itself. Anything else the app reads comes from `.env`, baked into the image by `composer dump-env prod` (`docker/Dockerfile`): `DEFAULT_STAFF_EMAIL` (`admin@supplymars.com`), `LOCK_DSN` (`flock`), `MERCURE_COOKIE_NAME` (`__Secure-mercure_access_token`; `compose.prod.local.yaml` sets the unprefixed name for plain HTTP), `TRUSTED_PROXIES` (`private_ranges`, which covers Caddy reaching the container). Setting one of those in an env file has no effect.

| Variable | Live | Playground | Notes |
|----------|------|------------|-------|
| `APP_SECRET` | own | own | `openssl rand -hex 16` |
| `DEFAULT_DOMAIN` | `supplymars` | `supplymars` | |
| `DEFAULT_URI` | `https://live.supplymars.com` | `https://www.supplymars.com` | URLs generated outside a request: emails sent by the worker, commands |
| `DATABASE_URL` | `mysql://app:<MYSQL_PASSWORD>@database:3306/app` | same shape | The app connects as `MYSQL_USER` `app`, which has rights on one database only |
| `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` | own | own | Read by the database container; the root one also by `reset` and the commands below. `openssl rand -base64 24` |
| `DATABASE_NAME` | unset | unset | Leave it unset, so the database is `app` (`compose.yaml`). The table queries in section 8 assume `app`, and `DATABASE_URL` must name the same database |
| `REDIS_PASSWORD`, `REDIS_URL` | own | own | Redis refuses to start without the password; `REDIS_URL` (`redis://default:<password>@redis:6379`) carries the same one, for sessions and the cache |
| `MAILER_DSN`, `MAILER_FROM` | real | real | `MAILER_FROM=no-reply@supplymars.com` |
| `AWS_S3_REGION`, `AWS_S3_BUCKET`, `AWS_S3_ACCESS_ID`, `AWS_S3_SECRET_ACCESS_KEY` | `eu-west-2`, `unicorn-bucket-two` | same | Uploads and backups; the reset container uses them for the S3 sync |
| `AWS_S3_PRODUCTS_PREFIX` | empty | `playground` | S3 key prefix for uploads |
| `UPLOADS_BASE_URL` | `https://unicorn-bucket-two.s3.eu-west-2.amazonaws.com` | the same plus `/playground` | |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | real | real | Left out, they are empty and every form with a Turnstile field, including the playground's demo login, rejects every submission |
| `PLAYGROUND_MODE` | `false` | `true` | Also picks the scheduled jobs ([playground.md](infrastructure/playground.md#what-playground_mode-changes)) |
| `SENTRY_DSN` | shared | shared | Empty sends nothing ([13-monitoring.md](13-monitoring.md)) |
| `SENTRY_ENVIRONMENT` | unset (`live`) | `playground` | Separates the two stacks' events |
| `MERCURE_JWT_SECRET` | own | own | Signs the tokens of the stack's Mercure hub, which runs in its `php` container (live updates, [ADR 017](adr/017-live-updates-with-mercure.md)). At least 32 characters: `openssl rand -base64 32`. `make up-prod-live`, `make up-prod-playground` and `make up-prod-local` refuse to start without it, because FrankenPHP cannot parse its Caddyfile. The hub's issuer is `DEFAULT_URI` and its URLs follow from it (`compose.prod.yaml`) |
| `HTTP_HOST_PORT` | `8001` | `8002` | The localhost port Caddy proxies to |
| `DB_HOST_PORT` | `3307` | `3308` | MySQL on localhost, for SSH tunnels (`ssh -L 3307:localhost:3307 ubuntu@<server-ip>`; `make up-dev-tools` starts a phpMyAdmin for each) |

`MESSENGER_TRANSPORT_DSN` is not set: the queue defaults to the database (`compose.prod.yaml`). An old AMQP DSN left in an env file overrides that default and stops the worker, since the AMQP transport is not installed.

### Fresh Server Setup

Once per server. Live and playground share it.

1. **The server.** Ubuntu on Lightsail, ports 80 and 443 open in the Lightsail firewall, DNS A records for `supplymars.com`, `www.supplymars.com` and `live.supplymars.com` pointing at it. Install Docker Engine with the Compose plugin (2.24.4 or later: `compose.prod.yaml` uses `!override` and `!reset`), `git` and `make` (`sudo apt install -y git make`), and add `ubuntu` to the `docker` group (`sudo usermod -aG docker ubuntu`, then log in again).
2. **Clone.** `git clone <repo-url> /home/ubuntu/supplymars`.
3. **Caddy.** Install it from Caddy's apt repository (caddyserver.com/docs/install), then:

    ```bash
    sudo cp /home/ubuntu/supplymars/Docs/infrastructure/Caddyfile /etc/caddy/Caddyfile
    sudo systemctl enable --now caddy
    ```

    It gets and renews the Let's Encrypt certificates itself.
4. **Env files.** `mkdir -p /home/ubuntu/supplymars-secrets && chmod 700 /home/ubuntu/supplymars-secrets`, `scp` `live.env` and `playground.env` into it, then `chmod 600 /home/ubuntu/supplymars-secrets/*.env`.
5. **Backups directory**, owned by `www-data` (uid 33), which the worker runs as. One made by `ubuntu`, or by Docker on first mount, is not writable by it (section 4.1 says what fails):

    ```bash
    sudo mkdir -p /home/ubuntu/supplymars-backups && sudo chown -R 33:33 /home/ubuntu/supplymars-backups
    ```

6. **Registry login.** The packages are private, so the server needs a classic personal access token with only the `read:packages` scope. Note its expiry date: pulls, and so deploys, fail once it expires.

    ```bash
    echo <token> | docker login ghcr.io -u <github-user> --password-stdin
    ```

7. **Start live.** From the checkout, set `$LIVE` and `$PLAYGROUND` ([above](#compose-command-prefix)), then `make up-prod-live`. It pulls the images CI published for that commit. A new database gets the schema from the migrations but no data, and the fixtures are development only. To move an existing site, restore its backup (section 4.4). Otherwise create the first user:
    1. Sign up on `https://live.supplymars.com/register` with the `DEFAULT_STAFF_EMAIL` address (`admin@supplymars.com`).
    2. Make that account staff and super admin by hand, as in [08-security.md](08-security.md#users-and-roles).

    Until that user exists, every simulation job fails ([09-cli-reference.md](09-cli-reference.md#simulation-behaviour)).

8. **Seed the backup file**, which the first playground reset reads (afterwards the 02:00 backup writes it):

    ```bash
    docker compose $LIVE exec -T messenger php bin/console app:backup-database --local-copy=/backups/latest.sql.gz
    ```

9. **Start the playground and reset it once:**

    ```bash
    make up-prod-playground
    docker compose $PLAYGROUND exec -T reset /usr/local/bin/playground-reset.sh
    ```

10. **Check:**

    ```bash
    make verify-prod-live && make verify-prod-playground
    curl -s https://www.supplymars.com/login | grep -c "Welcome to the Playground"    # 1
    curl -s https://live.supplymars.com/login | grep -c "Welcome to the Playground"   # 0
    ```

## 2. Deployment

### 2.1 Automated (CI/CD)

A push to `main` runs `.github/workflows/ci.yml`:

1. **test**: every step of `make check` ([07-testing.md](07-testing.md#running-all-checks)).
2. **publish**, once the tests pass: builds the `php` and `reset` production images and pushes them tagged with the commit SHA. The tests ran against the development image of the same commit; the production images are not tested separately.
3. **deploy**, over SSH, once they are pushed:

    ```bash
    cd /home/ubuntu/supplymars
    git fetch origin
    # if a newer commit is already deployed: print "A newer commit is already deployed; skipping." and stop
    git reset --hard <sha>              # the compose files and Makefile of that commit
    git clean -fd
    mkdir -p /home/ubuntu/supplymars-backups
    make up-prod-live                   # pull that commit's images and start them; waits until healthy
    make up-prod-playground
    make verify-prod-live               # /health answers on the port Caddy uses; no migration is pending
    make verify-prod-playground
    ```

Deploys are queued, never cancelled part-way (`concurrency: ci-deploy`). Because a run can wait behind a later one, the job never goes back to an older commit: a green deploy that changed nothing is that guard. If any step fails, the job fails, and its log in the Actions tab shows which step.

The php container runs pending migrations, then `messenger:setup-transports`, when it starts (`RUN_MIGRATIONS=true` in `compose.prod.yaml`, read by `docker/php/docker-entrypoint.sh`). The messenger container does neither.

### 2.2 Manual Deployment

When the deploy job failed after the images were published, or to redeploy:

```bash
cd /home/ubuntu/supplymars
git fetch origin && git reset --hard origin/main
make up-prod-live && make up-prod-playground
make verify-prod-live && make verify-prod-playground
```

This works only for a commit whose images CI published; otherwise the pull fails and nothing is restarted. The server does not build, so while the publish job or GHCR is down a new commit waits, and the running version, or any earlier published one, keeps working.

### 2.3 Rollback

```bash
git reset --hard <previous-sha> && make up-prod-live && make up-prod-playground
make verify-prod-live && make verify-prod-playground
```

- Take `<previous-sha>` from the last successful deploy run in the Actions tab. Only commits that went through a full CI run on `main` have images (a pull of any other fails and changes nothing). A commit from before the image pipeline brings back the old Makefile, which builds on the server.
- The checkout decides which images run, so a later `make up-prod-live` (after an env file change or a restore) keeps running the rolled-back images.

**If the bad commit added a migration**, rolling back the code does not roll back the schema. Take a backup (section 4.2), then revert the migration with the current images, before checking out the previous commit:

```bash
docker compose $LIVE exec php php bin/console doctrine:migrations:migrate prev --no-interaction
docker compose $PLAYGROUND exec php php bin/console doctrine:migrations:migrate prev --no-interaction
```

The next push to `main` deploys as normal. To stay on the old version until a fix is ready, revert the bad commit on `main` instead, which deploys through CI.

### 2.4 Restarting the PHP Workers

Each FrankenPHP worker keeps the app in memory and serves up to 500 requests (`FRANKENPHP_LOOP_MAX` in `compose.prod.yaml`) before it starts afresh, and every deploy recreates the container. To restart them by hand, for example if the container's memory keeps growing (`docker stats`):

```bash
docker compose $LIVE exec php curl -fsS -X POST http://localhost:2019/frankenphp/workers/restart
docker compose $PLAYGROUND exec php curl -fsS -X POST http://localhost:2019/frankenphp/workers/restart
```

This goes through FrankenPHP's admin API, which listens inside the container only. Requests in progress finish first, and the site stays up. `docker compose $LIVE restart php` also works, but the site is down for the few seconds it takes to start.

## 3. Health Checks

### 3.1 Post-Deploy Verification

`make verify-prod-live` / `make verify-prod-playground` check `/health` on the port Caddy uses and that no migration is pending. Then:

```bash
docker compose $LIVE ps && docker compose $PLAYGROUND ps                 # every service healthy
curl -s -w " %{http_code}\n" https://live.supplymars.com/health          # 200 through Caddy; a 503 names the failed check
curl -s -w " %{http_code}\n" https://www.supplymars.com/health
curl -sI https://supplymars.com/login | grep -i -E "^HTTP|^location"   # 301 to https://www.supplymars.com/login
docker compose $LIVE exec -T php php bin/console messenger:stats
docker compose $LIVE logs --tail=50 php
```

### 3.2 Ongoing Monitoring

Errors go to Sentry, and an external uptime monitor calls `/health` on both stacks and `/health/backup` on live ([13-monitoring.md](13-monitoring.md)). By hand:

```bash
docker stats --no-stream
docker compose $LIVE exec -T php php bin/console messenger:failed:show

# Scheduled jobs: what runs when, and the worker's log (failed runs also reach Sentry)
docker compose $LIVE exec -T messenger php bin/console debug:scheduler
docker compose $LIVE logs --tail=50 messenger

# Long-running queries
mysql_live -e "SHOW FULL PROCESSLIST"

# MySQL settings in use: production.cnf's values, not the image's defaults
mysql_live -e "SHOW VARIABLES WHERE Variable_name IN ('performance_schema', 'max_connections', 'innodb_log_buffer_size', 'innodb_buffer_pool_size', 'log_bin')"

# Memory and swap: compare with 06-operations.md#database-optimization. Each open logged-in tab holds one
# connection to the stack's Mercure hub, in php (FrankenPHP's Go side, not a PHP worker)
free -m
for p in $(pgrep -x mysqld); do grep VmSwap /proc/$p/status; done
```

## 4. Backup & Restore

### 4.1 Automated Daily Backup

Live's messenger container runs `app:backup-database --local-copy=/backups/latest.sql.gz` at 02:00 UTC, as a scheduled job (`MainSchedule`). It is the first of the jobs due at 02:00, and the worker runs one job at a time. It:

- writes a gzipped `mysqldump` to a temporary file and uploads it, streamed (so PHP's memory limit does not cap its size), to `s3://unicorn-bucket-two/backups/supplymars-YYYY-MM-DD-HHmmss.sql.gz`
- writes the local copy beside the target and renames it into place, readable by its owner only (`0600`), so the 02:15 playground reset never reads a half-written file
- deletes S3 backups older than 30 days

The backups directory must belong to uid 33 ([Fresh Server Setup](#fresh-server-setup), step 5; the deploy job's `mkdir -p` runs as `ubuntu`). Otherwise the S3 upload still succeeds, then the local copy fails, the run fails (reported to Sentry, retention skipped), and the playground keeps restoring an old `latest.sql.gz`.

A successful run's output is not logged (`RunCommandMessage` keeps it). A failed run, or one skipped because a manual backup holds the lock, exits 1, which reaches Sentry and is not retried ([13-monitoring.md](13-monitoring.md#error-tracking-sentry)); the next run is the next night. What tells you backups have stopped, for any reason, is the uptime monitor on `/health/backup`, which fails once the newest backup on S3 is more than 26 hours old ([13-monitoring.md](13-monitoring.md#backup-check)).

### 4.2 Manual Backup

```bash
docker compose $LIVE exec -T messenger php bin/console app:backup-database --local-copy=/backups/latest.sql.gz   # S3 + local copy
docker compose $LIVE exec -T messenger php bin/console app:backup-database                                       # S3 only
docker compose $LIVE exec -T messenger php bin/console app:backup-database --dry-run
docker compose $LIVE exec -T messenger php bin/console app:backup-database --retention-days=60
```

Only one backup runs at a time. A run that starts while another is going does not wait: it prints a warning and exits with a failure, without dumping anything. If that happens to the 02:00 run, `/backups/latest.sql.gz` is whatever the run holding the lock leaves there, or the previous night's copy, and the 02:15 reset restores that.

The lock (`LOCK_DSN=flock`, `LockableTrait` in `BackupDatabaseCommand`) is a file in the container, so run a manual backup in the `messenger` container, where the scheduler runs, as above. One started in the `php` container takes a different lock and can run at the same time as the nightly one.

Adding a scheduled command of your own, and locking it against manual runs: [06-operations.md](06-operations.md#scheduled-jobs).

### 4.3 Pre-Deploy Backup

Before a risky deploy (a migration that alters or drops columns, a large schema change), take an S3-only backup as in 4.2.

### 4.4 Restore (Production)

`app:restore-database` is dev only (`#[When(env: 'dev')]`), so production uses `mysql` in the database container. No set-up step installs the AWS CLI on the host, and the S3 keys are only in the env files (the CLI is in the playground's `reset` image, for its S3 sync).

**Last rehearsed 2026-10-29**: steps 2–5 on the server against the playground, and steps 3–5 locally with two live backups (2026-04-28 and 2026-10-06), both migrated to the current schema. Steps 0 and 1 were not rehearsed: step 0 is the nightly backup's command. Rehearse again after a change to the backup command, the database image or the entrypoint's migrations ([below](#rehearsing-the-restore)), and fix this section if a step fails.

```bash
# 0. Take an S3-only backup of the current state (4.2), so a wrong restore can be undone
docker compose $LIVE exec -T messenger php bin/console app:backup-database

# 1. Get the dump.
#    Last night's: the local copy, readable only by www-data, so use sudo in step 4:
#      sudo gunzip -c /home/ubuntu/supplymars-backups/latest.sql.gz | docker compose $LIVE exec -T database ...
#    Another one: download it on your machine (the S3 console, or `symfony console app:restore-database --from-s3`,
#    which saves the newest to var/backups/; answer "no" to the restore prompt), then:
scp var/backups/supplymars-2026-10-20-020001.sql.gz ubuntu@<server-ip>:/tmp/

# 2. On the server: stop everything that writes to the database
docker compose $LIVE stop php messenger

# 3. Empty the database. The dump drops and recreates only its own tables (see below)
mysql_live -e "DROP DATABASE app; CREATE DATABASE app"

# 4. Restore into MYSQL_DATABASE (app). The container has it and the root password in its environment
gunzip < /tmp/supplymars-2026-10-20-020001.sql.gz | \
    docker compose $LIVE exec -T database sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'

# 5. Start the app again (php runs any migrations newer than the backup), then check it
make up-prod-live
make verify-prod-live
rm /tmp/supplymars-2026-10-20-020001.sql.gz
```

Gotchas:

- **Restoring over a database that was not emptied can stop php from starting.** `app:backup-database` runs `mysqldump` without `--add-drop-database`, so loading a dump drops and recreates only the tables in it. Restore a backup older than a migration that created a table, and that table stays while `doctrine_migration_versions` loses the migration's row. On start, php runs the migration again, it fails on the existing table, and the entrypoint stops (`set -euo pipefail` in `docker/php/docker-entrypoint.sh`), so php never comes up. Step 3 prevents it.
- **Dropping the database keeps the app's access.** The `mysql` image grants `app` its rights on the database by name (`GRANT ALL ON app.*`, in the image's entrypoint), and MySQL keeps a database-level grant when the database is dropped, so the recreated `app` is usable by the app straight away.
- **The dump is MariaDB's.** The php image's client (`default-mysql-client` in `docker/Dockerfile`) is MariaDB's `mysqldump`, so a backup starts with a `/*M!999999\- enable the sandbox mode */` line. MySQL 8.4's `mysql` loads it as it is (rehearsed with both backups above).
- **The queue is a table in the same database**, so the restore brings back the emails that were waiting when the backup was taken, and the worker sends them when it starts. If they were already sent, delete them before step 5: `mysql_live -e "DELETE FROM messenger_messages WHERE queue_name = 'default'"` (`messenger:stats` shows how many there are).

#### Rehearsing the restore

This rehearses the procedure above. [4.6](#46-restore-rehearsal) is the monthly check that a backup is good.

**On the server, use the playground and a dump of its own database.** Not live's `latest.sql.gz`: until the nightly reset redacts them, the playground would have live's staff credentials, and its worker would send live's queued emails. Not `app:backup-database` for step 0 either: the playground's backups go to the same S3 folder as live's, so its dump would become "the newest backup". Keep clear of 01:55–02:30 UTC (the 02:00 backup and the 02:15 reset), and paste one line at a time:

```bash
mysql_pg() { docker compose $PLAYGROUND exec -T database sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" "$@"' sh "$@"; }
umask 077   # The dump holds customer data

# 2. Stop the writers first, so the counts and the dump match
docker compose $PLAYGROUND stop php messenger
mysql_pg -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE(); SELECT COUNT(*), MAX(id) FROM customer_order; SELECT MAX(version) FROM doctrine_migration_versions"

# 0. The playground's own dump. Go on only if the test passes and the size is plausible (about 110 MB)
docker compose $PLAYGROUND exec -T database sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --quick --routines "$MYSQL_DATABASE"' | gzip > /tmp/pg-rehearsal.sql.gz
gunzip -t /tmp/pg-rehearsal.sql.gz && ls -lh /tmp/pg-rehearsal.sql.gz

# 3–5 as above, with $PLAYGROUND and mysql_pg. Step 3 runs only on the playground's container
if [ "$(docker compose $PLAYGROUND ps --format '{{.Name}}' database)" = "supplymars-playground-database-1" ]; then mysql_pg -e "DROP DATABASE app; CREATE DATABASE app"; else echo "NOT the playground database: stopped"; fi
gunzip < /tmp/pg-rehearsal.sql.gz | docker compose $PLAYGROUND exec -T database sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
mysql_pg -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE(); SELECT COUNT(*), MAX(id) FROM customer_order; SELECT MAX(version) FROM doctrine_migration_versions"
make up-prod-playground
make verify-prod-playground
rm /tmp/pg-rehearsal.sql.gz
```

**Locally, use a scratch container, not `make up-prod-local`.** That stack passes no `-p`, so it shares the development stack's project (`supplymars`) and `db-data` volume: restoring into it overwrites your local database. A container with production's database environment and settings, and a volume of its own:

```bash
docker run -d --name restore-rehearsal -e MYSQL_ROOT_PASSWORD=rootpw -e MYSQL_USER=app -e MYSQL_PASSWORD=apppw \
    -e MYSQL_DATABASE=app -p 127.0.0.1:3399:3306 \
    -v "$PWD/docker/mysql/production.cnf:/etc/mysql/conf.d/production.cnf:ro" mysql:8.4
# Wait for the real server: the image first runs a socket-only one to create app and its grant
until docker exec restore-rehearsal mysql -h127.0.0.1 -uroot -prootpw -e 'SELECT 1' >/dev/null 2>&1; do sleep 2; done

# 3 and 4, as above. The DROP on an empty database is there to show app keeps its grant (step 5 runs as app)
docker exec -i restore-rehearsal sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" "$@"' sh -e "DROP DATABASE app; CREATE DATABASE app"
gunzip < var/backups/supplymars-2026-10-20-020001.sql.gz | \
    docker exec -i restore-rehearsal sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'

# 5. The entrypoint's migrate, as the app user. php bin/console, not symfony console, which can
#    swap in the dev database's URL: check the hostname is the container's ID before migrating
export DATABASE_URL='mysql://app:apppw@127.0.0.1:3399/app?serverVersion=8.4&charset=utf8mb4'
php bin/console dbal:run-sql "SELECT @@hostname"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console doctrine:schema:validate --skip-mapping

docker rm -f -v restore-rehearsal
unset DATABASE_URL
rm var/backups/supplymars-2026-10-20-020001.sql.gz   # A full copy of production
```

### 4.5 Verify a Backup

```bash
ls -lh /home/ubuntu/supplymars-backups/latest.sql.gz      # today's date, a plausible size
```

The newest S3 backup's age is what `/health/backup` checks. Only a restore shows a backup is good (4.6).

### 4.6 Restore Rehearsal

Once a month, and after a release that changes the schema a lot, restore last night's backup on your own machine:

1. `make up-dev-tools`, then `symfony console app:restore-database --from-s3`. It asks for the production S3 keys (from `../supplymars-secrets/live.env`), downloads the newest backup to `var/backups/` and replaces the dev database with it. Then `symfony console doctrine:migrations:migrate` if your checkout is ahead of production.
2. `symfony serve -d`, log in, and open the dashboard, an order, a product and a purchase order. The newest orders should be from the previous evening.
3. Delete the file from `var/backups/`: it is a full copy of production.

If any step fails, treat it as a broken backup and fix it before the next one is needed.

## 5. Maintenance Mode

```bash
# Take live offline. Check first that the 02:00 backup is not running (docker compose $LIVE top messenger)
docker compose $LIVE stop messenger
docker compose $LIVE stop php            # Caddy now returns 502

# Bring it back: php first (it runs pending migrations), then messenger once php is healthy
make up-prod-live && make verify-prod-live

# Remove a stack's containers and network. Volumes (database, Redis) are kept
make down-prod-live
make down-prod-playground
```

Stopping the worker takes up to 30 seconds; what happens to a job cut short is in [06-operations.md](06-operations.md#messenger-worker).

## 6. Troubleshooting

### 6.1 App Returns 502/504

```bash
docker compose $LIVE ps php
docker compose $LIVE logs --tail=100 php    # the entrypoint's lines (migrations), FrankenPHP's, then the app's
```

Usual causes: the php container is restarting (OOM or a fatal error), a migration failed on startup, or the database is down. Restart the database first if it is the cause, wait until it is healthy, then `docker compose $LIVE restart php`.

### 6.2 Live Updates or Toasts Not Arriving

An order page does not change while open, or a lock-release toast never shows ([ADR 017](adr/017-live-updates-with-mercure.md)). Both are published by the `messenger` worker from the `async` queue, up to a few seconds after the change, and after any scheduled job it is running.

```bash
docker compose $LIVE exec -T php php bin/console messenger:stats              # async backlog: see 6.3
docker compose $LIVE logs --tail=100 messenger | grep 'Live update not published'   # the hub refused or was down
curl -sN -o /dev/null -w "%{http_code}\n" --max-time 3 https://live.supplymars.com/.well-known/mercure   # 401 without a cookie: the hub answers through Caddy
```

If the browser console shows `Connecting to '…/.well-known/mercure…' violates the following Content Security Policy directive: "connect-src 'self' …"`, the page was loaded from a host other than the one in the stack's `DEFAULT_URI`, where its hub is. Every other host must redirect to it in the host Caddy ([section 1](#1-how-it-fits-together)).

The host Caddy streams `text/event-stream` responses without buffering by default. If updates arrive only in bursts, or when the page is left, something between the browser and the `php` container is buffering `/.well-known/mercure`. Check once after the first deploy with live updates, with an order page open while the hourly shipping job runs.

### 6.3 Queue Backlog or Failed Messages

The `async` count in `messenger:stats` keeps growing and emails are late. The same worker runs the scheduled jobs, so a long job (the 02:00 backup) delays emails until it ends.

```bash
docker compose $LIVE exec -T php php bin/console messenger:stats
docker compose $LIVE logs --tail=100 messenger
docker compose $LIVE restart messenger

docker compose $LIVE exec -T php php bin/console messenger:failed:show
docker compose $LIVE exec -T php php bin/console messenger:failed:retry [<id>]
docker compose $LIVE exec -T php php bin/console messenger:failed:remove <id>
```

### 6.4 Scheduled Jobs Not Running

No new orders, reports not updating.

```bash
# The worker must consume scheduler_default as well as async
docker compose $LIVE exec -T messenger ps aux | grep messenger:consume
docker compose $LIVE exec -T messenger php bin/console debug:scheduler
docker compose $LIVE logs --tail=100 messenger
docker compose $LIVE restart messenger
```

Simulation jobs failing with a user-not-found error: the `DEFAULT_STAFF_EMAIL` user is missing ([09-cli-reference.md](09-cli-reference.md#simulation-behaviour); creating it: [Fresh Server Setup](#fresh-server-setup), step 7).

### 6.5 Deploy Timed Out

The deploy job fails with `Run Command Timeout`; the site may return 502. When the SSH session ends, a `docker compose up` already started keeps running on the server, but nothing after it does (the other stack, the verify steps). It can leave containers `Created` and never started: the old ones removed, the new ones not running.

```bash
pgrep -af "docker compose"           # a compose run from the deploy still going? Let it finish, or Ctrl-C it
docker compose $LIVE ps -a           # Created = replaced but never started
make up-prod-live && make up-prod-playground
make verify-prod-live && make verify-prod-playground
```

Compose waits up to `stop_grace_period` for each container it replaces, and the messenger worker always uses all of it ([06-operations.md](06-operations.md#messenger-worker)). A longer grace period could push a deploy past the job's 10-minute timeout.

### 6.6 Signed-In Users Get a 500 After a Deploy

Every page fails for users who were signed in before the deploy; new visitors are fine. The log shows a `TypeError` from `unserialize()` on a `User` property.

The session in Redis holds the signed-in `User` object, serialized by the previous code. When a property of `User` changes type, PHP cannot restore the old session, and Symfony does not catch the error. Clear the sessions on both stacks; everyone is signed out once, and remember-me signs them back in.

```bash
# Session keys start with sf_s (the session handler's prefix); cache keys are left alone
docker compose $LIVE exec redis sh -c 'export REDISCLI_AUTH="$REDIS_PASSWORD"; redis-cli --scan --pattern "sf_s*" | xargs -r redis-cli unlink'
docker compose $PLAYGROUND exec redis sh -c 'export REDISCLI_AUTH="$REDIS_PASSWORD"; redis-cli --scan --pattern "sf_s*" | xargs -r redis-cli unlink'
```

### 6.7 Slow Pages

```bash
mysql_live -e "SHOW FULL PROCESSLIST"
docker stats --no-stream
docker compose $LIVE exec redis sh -c 'REDISCLI_AUTH="$REDIS_PASSWORD" redis-cli info stats' | grep instantaneous_ops_per_sec
```

Look for a report calculation or the backup running, a missing index after a schema change, or swap use (3.2).

### 6.8 Disk Space

```bash
df -h && docker system df
docker system prune -f
docker image prune -a -f --filter "until=720h"     # images not used by a container, older than 30 days
```

Every deploy leaves its images on the server, so a rollback starts without a download. A pruned version can still be rolled back to: `make up-prod-live` pulls it again from GHCR.

On 2026-10-26 most of the used space was MySQL's binary logs, 24 GB in the playground's volume (#143). `production.cnf` now turns them off (`log_bin` is `OFF` in the 3.2 settings check). If a database volume grows well past its data, look for `binlog.*` files. MySQL does not delete the ones left from before logging was turned off:

```bash
docker system df -v | grep db-data
docker compose $LIVE exec database sh -c 'ls -lh /var/lib/mysql/binlog.*'
docker compose $LIVE exec database sh -c 'rm /var/lib/mysql/binlog.*'   # only once log_bin is OFF
# The same two with $PLAYGROUND
```

### 6.9 Playground Reset Failed

See [infrastructure/playground.md](infrastructure/playground.md#troubleshooting).

## 7. SSL Certificates

Caddy gets and renews the Let's Encrypt certificates; nothing is done by hand.

```bash
sudo systemctl status caddy
sudo journalctl -u caddy --no-pager -n 100 | grep -i "error\|fail\|challenge"
curl -vI https://live.supplymars.com 2>&1 | grep "subject:"
```

If a certificate is not issued: check that ports 80 and 443 are open in the Lightsail firewall and the DNS A records point at the instance, then `sudo systemctl restart caddy`.

After a change to `Docs/infrastructure/Caddyfile`, copy it over and reload; a reload keeps the certificates and open connections:

```bash
sudo cp /home/ubuntu/supplymars/Docs/infrastructure/Caddyfile /etc/caddy/Caddyfile
sudo caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile && sudo systemctl reload caddy
```

## 8. Database Maintenance

### 8.1 Table Sizes

```bash
mysql_live -e "
    SELECT table_name, ROUND(data_length/1024/1024, 2) AS data_mb, ROUND(index_length/1024/1024, 2) AS index_mb, table_rows
    FROM information_schema.tables WHERE table_schema = DATABASE()
    ORDER BY data_length DESC LIMIT 20"
```

### 8.2 Refresh Index Statistics

Monthly, for the tables with heavy insert and delete activity. `ANALYZE TABLE` only refreshes the optimizer's statistics:

```bash
mysql_live -e "ANALYZE TABLE purchase_order_item, customer_order_item, product_sales, order_sales, customer_sales"
```

**After a large delete.** InnoDB keeps the space of deleted rows for reuse and does not hand it back to the disk. After the first run of `app:prune-status-change-log` on a database (2.4 million rows on live), or after its retention is shortened, rebuild the table once to shrink it:

```bash
mysql_live -e "OPTIMIZE TABLE status_change_log"
```

On a copy of live this took 12 seconds and took the table from 1,100 MB to 561 MB; InnoDB allows writes while it runs. The nightly prune that follows deletes only a day's rows, which InnoDB reuses, so it needs no rebuild. The playground needs none: it is restored from live's backup each night.

### 8.3 Rebuild Reporting Data

If the reporting data is inconsistent, recompute the daily records, then rebuild the summaries. The daily commands with offset 0 run only the incremental summary; the `-summary 1` commands rebuild the full range, as the nightly jobs do:

```bash
docker compose $LIVE exec -T messenger php bin/console app:calculate-product-sales 90 --skip-summary
docker compose $LIVE exec -T messenger php bin/console app:calculate-product-sales-summary 1
docker compose $LIVE exec -T messenger php bin/console app:calculate-order-sales 90 --skip-summary
docker compose $LIVE exec -T messenger php bin/console app:calculate-order-sales-summary 1
docker compose $LIVE exec -T messenger php bin/console app:calculate-customer-sales 90 --skip-summary
docker compose $LIVE exec -T messenger php bin/console app:calculate-customer-sales-summary 1
```

## Related

- [06-operations.md](06-operations.md): scheduled jobs (and adding one), the worker, MySQL settings
- [09-cli-reference.md](09-cli-reference.md): what each command does and when it runs
- [13-monitoring.md](13-monitoring.md): Sentry, `/health`, `/health/backup`, the uptime monitor
- [infrastructure/playground.md](infrastructure/playground.md): what the playground changes, the nightly reset
- [ADR 015](adr/015-symfony-scheduler-in-place-of-cron.md), [ADR 016](adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md), [ADR 017](adr/017-live-updates-with-mercure.md)
