# Monitoring

Three things tell you a production stack is in trouble: error tracking, the health endpoint, and an external uptime monitor that calls it.

## Error tracking (Sentry)

`sentry/sentry-symfony` is installed and registered for the `prod` environment only (`config/bundles.php`), so `dev` and `test` never load it. Configuration is in `config/packages/sentry.yaml`.

It is switched on by one environment variable, set in each stack's env file in `../supplymars-secrets/` (see [infrastructure/playground.md](infrastructure/playground.md#environment-files)):

```bash
SENTRY_DSN=https://<key>@<org>.ingest.sentry.io/<project>
```

With `SENTRY_DSN` empty (the default in `.env`) the bundle sends nothing. `compose.prod.yaml` passes the variable to the php, messenger, cron and reset containers.

Both stacks run with `APP_ENV=prod` and report to the same Sentry project. `SENTRY_ENVIRONMENT` tells them apart: `live` by default, `playground` in `playground.env`. Filter by environment in Sentry to see one stack.

With a DSN set, an uncaught exception is reported from:

| Where | Example |
|-------|---------|
| Web requests | A controller or handler throws and the user sees a 500 page |
| Console commands | A cron job such as `app:create-customer-orders` or `app:backup-database` fails |
| Messenger workers | A queued message fails in `messenger:consume`. Each failed attempt is reported, including ones that will be retried |

Expected responses are left out so they do not bury real faults: anything thrown as an HTTP exception (a 404, a failed API validation) and the "access denied" that sends an anonymous visitor to the login page. The list is `ignore_exceptions` in `config/packages/sentry.yaml`.

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
| `queue` | Asks the `async` Messenger transport how many messages are waiting. With the database queue ([ADR 013](adr/013-database-queue-by-default.md)) this counts rows in `messenger_messages`, so it fails whenever the database does. A transport with nothing to connect to (`sync://`, `in-memory://` in tests) always passes |

A stopped service fails its check at once. A host that accepts the connection but never answers (a hung database) holds the check until nginx gives up after 60 seconds and returns 504. Set the uptime monitor to treat a timeout or any status other than 200 as down.

Because a slow answer holds a PHP worker, `docker/nginx/conf.d/prod.conf` lets only two `/health` requests run at once (`limit_conn`); any others get an immediate 503. That keeps repeated calls during an outage from using up every worker.

The response says only which check failed. The reason (the exception) is written to the application log at `error` level, because the endpoint is public.

### Adding a check

Add a class implementing `App\Shared\Infrastructure\Health\HealthCheckInterface` next to the existing ones in `src/Shared/Infrastructure/Health/`. `check()` throws when the dependency cannot be reached. It is picked up automatically and appears in the response under its `name()`.

### Docker

The nginx container's healthcheck calls `/health` (over HTTPS in `compose.yaml`, over HTTP in `compose.prod.yaml`), so `make up` and the `make up-prod-*` targets wait until the whole stack answers, and `docker compose ps` shows nginx as unhealthy when the database, Redis or the queue is down.

## Uptime monitoring

Nothing in the code. Point an external uptime monitor at both stacks and alert on any response other than 200:

- `https://live.supplymars.com/health`
- `https://supplymars.com/health` (playground)
