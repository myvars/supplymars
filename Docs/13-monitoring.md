# Monitoring

What tells you a production stack is in trouble: error tracking, the health endpoints, and an external uptime monitor that calls them. Read before changing Sentry, adding a dependency to check, or setting up the uptime monitor.

## Error tracking (Sentry)

`sentry/sentry-symfony`, registered for `prod` only (`config/bundles.php`), so `dev` and `test` never load it. Configuration is in `config/packages/sentry.yaml`.

It is switched on by `SENTRY_DSN` in each stack's production env file (see [11-runbook.md](11-runbook.md)), which `compose.prod.yaml` passes to the php and messenger containers. Empty (the default in `.env`), nothing is sent. Both stacks report to the same Sentry project; `SENTRY_ENVIRONMENT` tells them apart (`live` by default, `playground` in `playground.env`).

With a DSN set, an uncaught exception is reported from:

| Where | Example |
|-------|---------|
| Web requests | A controller or handler throws and the user sees a 500 page |
| Console commands | A command run by hand throws an exception it does not catch |
| Scheduled jobs | A job in `MainSchedule` throws, or exits with a failure code (`RunCommandFailedException`). Not retried: the next run is the next scheduled time |
| Messenger workers | A queued message fails in `messenger:consume`. Each failed attempt is reported, including ones that will be retried |

Expected responses are left out: anything thrown as an HTTP exception (a 404, a failed API validation) and the "access denied" that sends an anonymous visitor to the login page (`ignore_exceptions` in `config/packages/sentry.yaml`). Not enabled: performance tracing (`tracing.enabled: false`) and sending log records to Sentry; the commented block in `sentry.yaml` shows how to add the Monolog handler.

To check a stack reports, run `sentry:test` in it. The command exists only in `prod`, because the bundle is registered there only. `$LIVE` and `$PLAYGROUND` are defined in [11-runbook.md](11-runbook.md#compose-command-prefix):

```bash
docker compose $LIVE exec -T php php bin/console sentry:test
docker compose $PLAYGROUND exec -T php php bin/console sentry:test
```

## Health endpoint

`GET /health` is public and returns JSON: 200 when every check passes, 503 when one fails (`status` is `fail`, and so is the failed check).

```json
{"status": "ok", "checks": {"database": "ok", "redis": "ok", "queue": "ok"}}
```

| Check | What it does |
|-------|--------------|
| `database` | Runs `SELECT 1` on the default connection |
| `redis` | Pings the Redis client used for the cache and sessions. Fails when `REDIS_URL`'s password does not match `REDIS_PASSWORD` |
| `queue` | Asks the `async` transport how many messages are waiting. With the database queue ([ADR 013](adr/013-database-queue-by-default.md)) this counts rows in `messenger_messages`, so it fails whenever the database does. A transport with nothing to count (`sync://`, `in-memory://` in tests) always passes |

The response names the failed check only; the exception goes to the application log at `error` level, because the endpoint is public.

To add a check, add a class implementing `HealthCheckInterface` in `src/Shared/Infrastructure/Health/`. `check()` throws when the dependency cannot be reached; the class is picked up automatically and appears under its `name()`.

The php container's healthcheck calls `/health` (HTTPS in `compose.yaml`, HTTP in `compose.prod.yaml`), so `make up` and `make up-prod-*` wait until the whole stack answers, and `docker compose ps` shows php unhealthy when a dependency is down.

## Backup check

`GET /health/backup` is public, with the same JSON shape and one check, `backup`. It passes when the newest `.sql.gz` on the backups filesystem (S3 `backups/` in production) is less than 26 hours old: the 02:00 run plus a margin. It fails with 503 when the newest is older, or there is none.

It catches every way backups stop: a failed run (also reported to Sentry), and a run that never happened (messenger down or not consuming `scheduler_default`, `PLAYGROUND_MODE` wrongly on for live, server off at 02:00, every run skipped because a hung one holds the lock).

## Uptime monitoring

Nothing in the code. Point an external uptime monitor (Sentry Uptime) at these and alert on a timeout or any status other than 200:

- `https://live.supplymars.com/health`
- `https://www.supplymars.com/health` (playground)
- `https://live.supplymars.com/health/backup`: hourly is enough, with a failure tolerance of 2. Both stacks use the same bucket, so monitor it on live only

## Gotchas

- **Most scheduled commands do not throw.** `app:backup-database` and the purchasing and reporting commands catch errors per item, log them and exit with a failure code. Run by the schedule, that is one Sentry event per run (the table above), with the details in the messenger container's log. Run by hand, it does not reach Sentry. A scheduled command that throws is reported twice: as the console error, and wrapped as above.
- **An HTTP exception with a 5xx status is not reported.** Sentry matches `ignore_exceptions` against every exception in the chain.
- **The `queue` check does not notice a stopped worker.** Messages wait in the table and the check still passes. Watch the backlog instead ([11-runbook.md](11-runbook.md)).
- **A hung dependency holds the check.** A stopped service fails at once; one that accepts the connection but never answers holds the check until the client's connection timeout, 30 seconds or more.
- **Both endpoints are rate-limited per client IP** (numbers, and why a client cannot fake its IP: [08-security.md](08-security.md#rate-limiting)). `/health/backup` has the lower limit because each call lists the S3 backups. Alert on a 429 like any non-200, and read it as "throttled", not "down". Per IP, so one caller cannot use up the uptime monitor's allowance. It is not a bound on threads: FrankenPHP starts two per CPU, so a few callers whose checks hang can occupy them all, but by then the site's own pages are down too.
- **The container healthcheck is not counted.** Calls from loopback are exempt on `/health`, so outside callers cannot make the healthcheck or `up --wait` fail. `make verify-prod-*` calls through the published port, so it has a count of its own; its curl retries a 429.
- **With Redis down, `/health` still answers.** The rate-limit counts are in Redis, so the call goes through and `/health` answers 503 naming the failed check.
- **`/health/backup` is not a `/health` check on purpose.** `/health` gates the php container's healthcheck and the post-deploy check; a missed backup must not mark the stack down or fail a deploy.

## Related

- [08-security.md](08-security.md): the rate-limit rules and why `/health` is public
- [11-runbook.md](11-runbook.md): post-deploy checks, backups, production env files
- [06-operations.md](06-operations.md): the scheduled jobs and the worker
