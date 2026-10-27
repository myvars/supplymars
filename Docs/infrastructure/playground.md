# Playground

What makes the public playground (`www.supplymars.com`; `supplymars.com` redirects to it) differ from the live site (`live.supplymars.com`), and how its nightly reset works. Read it before changing anything gated on `PLAYGROUND_MODE`, the reset scripts, or the redaction. Running both stacks (deploy, env files, set-up, backups) is in [11-runbook.md](../11-runbook.md).

The names read the wrong way round: `www` is the playground, the demo visitors are given; `live` is the source of truth that the playground is restored from each night.

## What differs from live

Both stacks run the same images on the same server, from the same `compose.yaml` + `compose.prod.yaml`. They differ only in their env files and in the `playground` Compose profile:

| | Live | Playground |
|---|---|---|
| Compose project, port | `supplymars-live`, 8001 | `supplymars-playground`, 8002 (`--profile playground`) |
| Extra container | none | `reset` (cron, MySQL client, Redis client, AWS CLI; no PHP) |
| Data | the real history | live's last backup, restored at 02:15 UTC, staff redacted |
| Uploads | S3 root | S3 prefix `playground/`, re-synced from live nightly |
| Backups directory | written by `messenger` | read-only in `reset` |
| Sentry environment | `live` | `playground` |

Each stack has its own database, Redis and queue, its own Mercure hub (in its `php` container), and its own Docker network. The only thing they share on the host is `/home/ubuntu/supplymars-backups`.

**Live updates.** The order page updates while open on both stacks ([ADR 017](../adr/017-live-updates-with-mercure.md)). The lock-release toast never shows on the playground: everyone shares the `demo@supplymars.com` login, so whoever releases a lock is its holder, and releasing your own lock sends nothing. That is correct, not a fault.

## What PLAYGROUND_MODE changes

`PLAYGROUND_MODE=true` in `playground.env`:

| Where | Effect |
|-------|--------|
| `/login` (`templates/customer/security/login.html.twig`) | "Welcome to the Playground" banner and an "Explore the Platform" button, which signs in as `demo@supplymars.com` after a Turnstile check (`PlaygroundLoginController`, route `app_playground_login`, POST only; 404 on live). Sign-up notes "Accounts reset nightly" |
| `templates/base.html.twig` | Hides the amber "This is the live site." strip, which live shows on every page |
| `UploadHelper` | File uploads throw: the playground's uploads are replaced each night |
| `MainSchedule` | Leaves out the live-only jobs: the 02:00 backup, the 01:30 refund and the 03:30 `app:prune-status-change-log` (why: [06-operations.md](../06-operations.md#scheduled-jobs)) |

The flag reaches PHP as `%env(bool:PLAYGROUND_MODE)%` and Twig as `playground_mode` (`config/packages/twig.yaml`).

## Nightly reset

1. **02:00 UTC, live:** the backup job writes `/home/ubuntu/supplymars-backups/latest.sql.gz` ([11-runbook.md](../11-runbook.md#41-automated-daily-backup)).
2. **02:15 UTC, playground:** the `reset` container's cron (`docker/php/cron/reset-crontab`) runs `scripts/playground-reset.sh`:
   1. Restores `latest.sql.gz` into the `app` database, leaving out the rows of `messenger_messages`: they are live's queue, and the playground worker would send live's pending emails. The table is recreated empty, which also clears the playground's own queue.
   2. Runs `scripts/playground-redact-staff.sql` straight after, as raw SQL, so there is no window where real staff logins work:
      - every staff user's email and password are scrambled, except `demo@supplymars.com` and `admin@supplymars.com`
      - `admin@supplymars.com` keeps its password and gets `ROLE_ADMIN` + `ROLE_SUPER_ADMIN`. It is kept because it is the `DEFAULT_STAFF_EMAIL` user the simulation commands need ([09-cli-reference.md](../09-cli-reference.md#simulation-behaviour)), and the playground's scheduled jobs would fail without it
      - `demo@supplymars.com` is created or reset with `ROLE_ADMIN` and the public password `demo`
   3. Syncs S3 `uploads/` and `media/` from live's root to the `playground/` prefix (`--delete`).
   4. `FLUSHALL` on the playground Redis: the cache, and every playground session, so everyone is signed out.

The script restores into `DATABASE_NAME`, which defaults to `app` as the database container's does (`compose.yaml`). The env files leave it unset ([11-runbook.md](../11-runbook.md#production-env-files)).

Run a reset by hand (with `$PLAYGROUND` from [11-runbook.md](../11-runbook.md#compose-command-prefix)):

```bash
docker compose $PLAYGROUND exec -T reset /usr/local/bin/playground-reset.sh
```

## Troubleshooting

`reset` exists only with `--profile playground`, which `$PLAYGROUND` includes; without it, `ps` and `logs` leave it out.

```bash
docker compose $PLAYGROUND ps reset              # healthy while cron runs
docker compose $PLAYGROUND logs --tail=100 reset # the script's output goes to the container log
ls -l /home/ubuntu/supplymars-backups/           # latest.sql.gz from today, owned by uid 33
```

| Symptom | Cause and fix |
|---------|---------------|
| `ERROR: Backup file not found at /backups/latest.sql.gz` | Live's backup has not run yet on a new server, or the mount is wrong. Run the backup by hand ([11-runbook.md](../11-runbook.md#42-manual-backup)), then the reset |
| Playground data is days old | The local copy fails: the backups directory is not owned by uid 33, so live's backup fails after the S3 upload and the reset keeps restoring the old file. `sudo chown -R 33:33 /home/ubuntu/supplymars-backups` |
| Demo login says "Demo account is not available." | The redaction did not run (reset failed partway). Run the reset by hand and read its log |
| Demo login says "Verification failed." | Turnstile keys missing or wrong in `playground.env` |
| Uploads fail on the playground | Expected: uploads are off in playground mode |

## Related

- [11-runbook.md](../11-runbook.md): both stacks, env files, fresh-server set-up, backups
- `Docs/infrastructure/Caddyfile`: the host Caddy config (`www` to 8002, the bare domain redirected to `www`, `live` to 8001)
- [08-security.md](../08-security.md): Turnstile
- [ADR 015](../adr/015-symfony-scheduler-in-place-of-cron.md): the schedule, and why `reset` keeps its own cron
