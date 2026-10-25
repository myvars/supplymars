# Local Development Setup

How to run SupplyMars on your machine, get a user and data, the local URLs and ports, and opt-ins such as RabbitMQ. Read it when setting up a checkout or when a local service will not start.

## Prerequisites

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.5+ | Option A only. Extensions: intl, pdo_mysql, bcmath, gd, redis, xsl, zip |
| Composer | 2.x | |
| Symfony CLI | Latest | `symfony serve` |
| Docker | Latest | MySQL, Redis, Mailpit, the Mercure hub, phpMyAdmin |

Settings only your machine uses go in `.env.local`. The database name and ports are not such a setting: they are in `.env` ([below](#database-name-and-ports)).

## Setup

### Option A: Symfony server + Docker services (recommended)

PHP runs on your machine; Docker provides the services.

1. Install and start the services:

   ```bash
   composer install                     # also runs importmap:install (auto-scripts)
   make up-dev-tools                    # MySQL, Redis, Mailpit, the Mercure hub, the three phpMyAdmins
   ```

   Check: `docker compose ps` shows `database`, `redis` and `mailer` healthy or running.

2. Create the schema and build the CSS:

   ```bash
   symfony console doctrine:database:create --if-not-exists    # the MySQL container already creates it
   symfony console doctrine:migrations:migrate --no-interaction
   symfony console tailwind:build       # needs assets/vendor/ from importmap:install
   ```

3. Start the server: `symfony serve -d`. Check: https://127.0.0.1:8000 shows the home page.

4. Load a user and data ([below](#a-user-and-data)).

- Sessions are in Redis, so logging in needs `make up-dev-tools` running.
- `symfony serve` starts a queue worker for `async` (the `workers` block of `.symfony.local.yaml`), for queued email, live updates on the order page and lock-release toasts. It restarts when `config/`, `src/`, `templates/` or the installed packages change, and stops with the server. `symfony server:status` lists it and `symfony server:log` shows its output. The block is read only when the server starts, and `.env` files are not watched: after changing either, `symfony server:stop` and `symfony serve -d`.
- Live updates need the `mercure` container of `make up-dev-tools`. `.symfony.local.yaml` (committed) sets `proxy_mercure`, so `symfony serve` serves the hub on the site's own origin (`/.well-known/mercure`) and sets `MERCURE_URL` and `MERCURE_PUBLIC_URL` itself. See [Turbo](patterns/Turbo/README.md#live-updates-with-mercure).

### Option B: Full Docker stack

Everything runs in containers. The `php` container runs FrankenPHP and serves `https://localhost`; the `messenger` container is the queue worker.

1. `make up`. The `php` container's entrypoint runs `composer install` (when `vendor/` is missing), `importmap:install`, `tailwind:build` and the migrations. Check: the command returns once `php` is healthy (its healthcheck calls `/health`).
2. Trust FrankenPHP's certificate, once per machine. It comes from its own local certificate authority, kept in the `caddy_data` volume (macOS shown; the volume keeps it across restarts):

   ```bash
   docker compose cp php:/data/caddy/pki/authorities/local/root.crt /tmp/caddy-root.crt
   sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain /tmp/caddy-root.crt
   ```

3. Load a user and data ([below](#a-user-and-data)), running each command in the container: `docker compose exec php bin/console …`.

Both options use the same services and volumes: every service is defined once in `compose.yaml`, with development settings and published ports in `compose.override.yaml`. `make up` starts them all; `make up-dev-tools` starts only the ones it names. The database is the same whichever way you start it.

### A user and data

| Way | What you get |
|-----|--------------|
| `symfony console doctrine:fixtures:load --no-interaction` | Purges the database, then creates the staff user `admin@supplymars.com` / `password`, the VAT rates, a warehouse and three suppliers with products (`tests/Shared/DataFixtures/AppFixtures.php`). No orders |
| The simulation commands, after the fixtures | Orders, purchase orders, reviews and report data. The order to run them in is in [06-operations.md](06-operations.md#simulation-bootstrap); each command is in [09-cli-reference.md](09-cli-reference.md) |
| `symfony console app:restore-database` | A production backup from `var/backups/` (or `--from-s3`). Dev only. See [11-runbook.md](11-runbook.md) |
| `/register` | A customer account, not staff. Staff is the "Member of Staff" box on the customer edit form; super admin is set by hand ([08-security.md](08-security.md)) |

The simulation and setup commands act as the user in `DEFAULT_STAFF_EMAIL` (`admin@supplymars.com` in `.env`, read by `DefaultUserAuthenticator`). They fail when that user does not exist, so load the fixtures, or restore a backup, first.

### Email in dev

In dev every email goes to `DEV_MAIL_RECIPIENT`, whatever its recipient (`config/packages/mailer.yaml`, `when@dev` envelope recipients), and lands in Mailpit (http://localhost:8025). Override `DEV_MAIL_RECIPIENT` in `.env.local` if you want another address. Queued email needs a worker; under Option A it is the one `symfony serve` starts.

## Switching to RabbitMQ

The queue is a database table by default ([ADR 013](adr/013-database-queue-by-default.md)). RabbitMQ is defined in the compose files behind the `rabbitmq` profile, so it does not start unless asked for. To use it:

1. **Require the transport package**, and add the PHP `sockets` extension it needs: to the `install-php-extensions` list in `docker/Dockerfile` (the image does not have it today), and to your local PHP if you use `symfony serve`.

   ```bash
   composer require checkthiscloud/phpamqplib-messenger
   ```

2. **Turn the profile on and change the DSN in `.env`.** Docker Compose reads `COMPOSE_PROFILES` from the same file.

   ```bash
   COMPOSE_PROFILES=rabbitmq
   MESSENGER_TRANSPORT_DSN="phpamqplib://guest:guest@127.0.0.1:${RABBITMQ_PORT}/%2f/messages"
   ```

3. **Change the DSN the containers use**, in `compose.override.yaml`:

   ```yaml
   MESSENGER_TRANSPORT_DSN: "phpamqplib://guest:guest@rabbitmq:5672/%2f/messages"
   ```

4. **Add three lines to each production env file** (`live.env`, `playground.env`, `prod-local.env` in `../supplymars-secrets/`):

   ```bash
   COMPOSE_PROFILES=rabbitmq
   RABBITMQ_PASSWORD=<a strong password>
   MESSENGER_TRANSPORT_DSN="phpamqplib://app:<the same password>@rabbitmq:5672/%2f/messages"
   ```

   The playground commands pass `--profile playground`, which replaces `COMPOSE_PROFILES`. Add `--profile rabbitmq` to the `PLAYGROUND` line of the `Makefile` as well.

5. **Add `rabbitmq` to the `up-dev-tools` target** in the `Makefile`. It names the services it starts, and a named service starts whether or not its profile is on, so it is not listed by default.

`make up-dev-tools`, `make up` and the production targets then start RabbitMQ, and `php` waits for it to be healthy. The management UI is at http://localhost:15672 (guest / guest, port `RABBITMQ_UI_PORT`) in development. Tests are unaffected: the `test` environment always uses `in-memory://`.

Messages already waiting in the database queue are not moved. Let the worker empty it before switching (`symfony console messenger:stats`).

## Development URLs

| Service | URL | Port variable | Credentials |
|---------|-----|---------------|-------------|
| Application | https://127.0.0.1:8000 (Option A) or https://localhost (Option B) | - | see [A user and data](#a-user-and-data) |
| phpMyAdmin | http://localhost:8080 | `PHPMYADMIN_PORT` | root / password |
| phpMyAdmin (live) | http://localhost:8081 | `PHPMYADMIN_LIVE_PORT` | the live credentials |
| phpMyAdmin (playground) | http://localhost:8082 | `PHPMYADMIN_PLAYGROUND_PORT` | the playground credentials |
| Mailpit | http://localhost:8025 | `MAILPIT_PORT` | - |
| Mercure hub (Option A) | 127.0.0.1:3001, where the app publishes; browsers reach it through `symfony serve` | `MERCURE_PORT` | signed with `MERCURE_JWT_SECRET` (`.env`) |
| MySQL | 127.0.0.1:3306 | `DATABASE_PORT` | root / password |
| Redis | 127.0.0.1:6379 | `REDIS_PORT` | default / password |

### Database name and ports

The database name and local ports are in the `docker` block at the top of `.env`. Docker Compose reads them from `.env`, and `DATABASE_URL`, `REDIS_URL` and `MAILER_DSN` are built from them, so a change is made in one place.

| Variable | Default | Used for |
|----------|---------|----------|
| `DATABASE_NAME` | `app` | The database. Tests append `_test` (and a process number under ParaTest) |
| `DATABASE_PORT` | `3306` | MySQL |
| `REDIS_PORT` | `6379` | Redis |
| `MAILER_PORT` | `1025` | Mailpit SMTP |
| `MAILPIT_PORT` | `8025` | Mailpit web UI |
| `PHPMYADMIN_PORT` | `8080` | phpMyAdmin |
| `PHPMYADMIN_LIVE_PORT` | `8081` | phpMyAdmin for the live database |
| `PHPMYADMIN_PLAYGROUND_PORT` | `8082` | phpMyAdmin for the playground database |
| `RABBITMQ_PORT` | `5672` | RabbitMQ, only with the `rabbitmq` profile |
| `RABBITMQ_UI_PORT` | `15672` | RabbitMQ management UI |
| `MERCURE_PORT` | `3001` | The Mercure hub of `make up-dev-tools` (Option B uses FrankenPHP's own) |

- Set them in `.env`, not `.env.local`: Symfony does not load `.env.local` in the `test` environment, so dev and test would point at different servers.
- Production ignores these. The production targets read the env files in `../supplymars-secrets/` instead of `.env`.
- `make up-prod-local` reads its own env file, so it uses the default ports, reuses the development phpMyAdmin and Mailpit, and serves the app on `127.0.0.1:8000`, the port `symfony serve` uses. Stop one before starting the other.

### phpMyAdmin for the production databases

`phpmyadmin-prod-live` (http://localhost:8081) and `phpmyadmin-prod-playground` (http://localhost:8082) start with `make up-dev-tools` and connect through SSH tunnels on local ports 3307 and 3308. Open the tunnel and the page works; without one it shows a connection error. Each has its own footer (`docker/phpmyadmin/`) so the two are not mixed up.

### Network exposure

MySQL, Redis, Mailpit, RabbitMQ and phpMyAdmin are published on `127.0.0.1` only (`compose.override.yaml`). They use default passwords or none, so they must not be reachable from other machines; keep the `127.0.0.1:` prefix when adding a port. One exception today: `compose.prod.local.yaml` publishes the RabbitMQ UI as `15672:15672`, on all interfaces.

The application is different: with `make up`, the php container publishes ports 80 and 443 on all interfaces (`compose.yaml`), so the dev site is reachable from the local network. The production files replace those with `127.0.0.1:8001` (`compose.prod.yaml`), which Caddy on the server proxies to.

## Troubleshooting

| Symptom | Check |
|---------|-------|
| `SQLSTATE[HY000] [2002] Connection refused` | `docker compose ps`, `docker compose logs database`. MySQL takes a few seconds to accept connections after it starts |
| `Connection refused [tcp://127.0.0.1:6379]` | `docker compose exec redis sh -c 'REDISCLI_AUTH="$REDIS_PASSWORD" redis-cli ping'` should print `PONG` |
| The order page does not update, or a lock toast never shows | Under Option A, `symfony server:status` must list the worker (restart the server if not) and the `mercure` container must run (`make up-dev-tools`); under Option B, the `messenger` container. Updates arrive within 5 seconds |
| An email or async event never arrives | See [Email in dev](#email-in-dev). Otherwise the worker is not running or failed: `symfony server:status`, `symfony server:log`, `symfony console messenger:stats`, `messenger:failed:show`. `symfony console messenger:consume async -vv` runs a second, verbose worker beside it; each message goes to whichever takes it first |
| Browser warns about the certificate (Option A) | `symfony server:ca:install`, then `symfony server:stop` and `symfony serve -d` |
| Styles missing or JavaScript errors | Rerun `importmap:install`, then `tailwind:build` (`assets/vendor/` is not committed) |
| A JS or CSS change never reaches the browser | Old compiled files in `public/assets/` (from `asset-map:compile` or an old checkout) are served before `assets/`. Delete them, clear `var/cache/dev` and hard-reload |
| `make up` fails with `container ...-php-1 is unhealthy` | The healthcheck calls `/health`, so the database, Redis or the queue being down also fails it. `curl -k https://localhost/health` shows which check failed; `docker compose logs php` shows FrankenPHP's errors; `docker compose exec php tail var/log/dev.log` the app's |
| A simulation command fails with `User "admin@supplymars.com" not found.` | The `DEFAULT_STAFF_EMAIL` user is missing ([above](#a-user-and-data)) |
| Test database missing or out of date | See [07-testing.md](07-testing.md#locally) |

## Related

- [06-operations.md](06-operations.md): the worker, the scheduler and the simulator
- [07-testing.md](07-testing.md): running the tests and `make check`
- [11-runbook.md](11-runbook.md): production
