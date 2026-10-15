# Monitoring

Three things tell you a production stack is in trouble: error tracking, the health endpoint, and an external uptime monitor that calls it.

## Error tracking (Sentry)

`sentry/sentry-symfony` is installed and registered for the `prod` environment only (`config/bundles.php`), so `dev` and `test` never load it. Configuration is in `config/packages/sentry.yaml`.

It is switched on by one environment variable, set in each stack's env file in `../supplymars-secrets/` (see [infrastructure/playground.md](infrastructure/playground.md#environment-files)):

```bash
SENTRY_DSN=https://<key>@<org>.ingest.sentry.io/<project>
```

With `SENTRY_DSN` empty (the default in `.env`) the bundle sends nothing. `compose.prod.yaml` passes the variable to the php, messenger and cron containers.

Both stacks run with `APP_ENV=prod` and report to the same Sentry project. `SENTRY_ENVIRONMENT` tells them apart: `live` by default, `playground` in `playground.env`. Filter by environment in Sentry to see one stack.

With a DSN set, an uncaught exception is reported from:

| Where | Example |
|-------|---------|
| Web requests | A controller or handler throws and the user sees a 500 page |
| Console commands | A command run by cron or by hand throws an exception it does not catch |
| Messenger workers | A queued message fails in `messenger:consume`. Each failed attempt is reported, including ones that will be retried |

Only uncaught exceptions are reported. Most cron commands (`app:backup-database`, the purchasing and reporting commands) catch their errors, log them and exit with a failure code, so those failures are in the cron container's log, not in Sentry. Sending logged errors to Sentry needs the Monolog handler (below).

Expected responses are left out so they do not bury real faults: anything thrown as an HTTP exception (a 404, a failed API validation) and the "access denied" that sends an anonymous visitor to the login page. The list is `ignore_exceptions` in `config/packages/sentry.yaml`. Sentry matches every exception in the chain, so an HTTP exception thrown on purpose with a 5xx status is not reported either.

Not enabled: performance tracing (`tracing.enabled: false`), and sending log records to Sentry. The commented block in `config/packages/sentry.yaml` shows how to add the Monolog handler.

To check a stack reports, run `sentry:test` in it (`$LIVE` and `$PLAYGROUND` are defined in [11-runbook.md](11-runbook.md#compose-command-prefix)); the event appears in Sentry under that stack's environment:

```bash
docker compose $LIVE exec -T php php bin/console sentry:test
docker compose $PLAYGROUND exec -T php php bin/console sentry:test
```

## Health endpoint

`GET /health` is public and returns JSON.

```json
{"status": "ok", "checks": {"database": "ok", "redis": "ok", "queue": "ok"}}
```

| Status | Meaning |
|--------|---------|
| 200 | Every check passed |
| 503 | At least one check failed; `status` is `fail` and the failed check shows `fail` |

| Check | What it does |
|-------|--------------|
| `database` | Runs `SELECT 1` on the default connection |
| `redis` | Pings the Redis client used for the cache |
| `queue` | Asks the `async` Messenger transport how many messages are waiting. With the database queue ([ADR 013](adr/013-database-queue-by-default.md)) this counts rows in `messenger_messages`, so it fails whenever the database does. It does not notice a stopped `messenger` worker: messages wait in the table and the check still passes. A transport with nothing to connect to (`sync://`, `in-memory://` in tests) always passes |

A stopped service fails its check at once. A host that accepts the connection but never answers (a hung database) holds the check until nginx gives up after 60 seconds and returns 504. Set the uptime monitor to treat a timeout or any status other than 200 as down.

Because a slow answer holds a PHP worker, `docker/nginx/conf.d/prod.conf` lets only two outside `/health` requests run at once (`limit_conn`); any others get an immediate 429. That stops repeated calls from piling up behind a slow check, but it is not a hard bound on workers: nginx frees its slot after 60 seconds while PHP may still be waiting on a hung database. The container's own healthcheck comes from loopback and is not counted, so outside calls cannot make it fail. Because the cap is shared, a burst of outside calls can give the uptime monitor a 429; alert on it like any other non-200, and read 429 as "throttled", not "down".

The response says only which check failed. The reason (the exception) is written to the application log at `error` level, because the endpoint is public.

### Adding a check

Add a class implementing `App\Shared\Infrastructure\Health\HealthCheckInterface` next to the existing ones in `src/Shared/Infrastructure/Health/`. `check()` throws when the dependency cannot be reached. It is picked up automatically and appears in the response under its `name()`.

### Docker

The nginx container's healthcheck calls `/health` (over HTTPS in `compose.yaml`, over HTTP in `compose.prod.yaml`), so `make up` and the `make up-prod-*` targets wait until the whole stack answers, and `docker compose ps` shows nginx as unhealthy when the database, Redis or the queue is down.

## Uptime monitoring

Nothing in the code. Point an external uptime monitor at both stacks and alert on any response other than 200:

- `https://live.supplymars.com/health`
- `https://supplymars.com/health` (playground)
