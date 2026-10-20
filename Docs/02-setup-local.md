# Local Development Setup

This guide covers getting SupplyMars running locally. The setup follows standard Symfony conventions.

## Prerequisites

Before setting up SupplyMars locally, ensure you have:

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.5+ | With extensions: intl, pdo_mysql, bcmath, gd, redis, xsl, zip |
| Composer | 2.x | Dependency management |
| Symfony CLI | Latest | For `symfony serve` command |
| Docker | Latest | For infrastructure services |

### PHP Extensions Required

```bash
# Check your PHP extensions
php -m | grep -E "(intl|pdo_mysql|bcmath|gd|redis|xsl|zip)"
```

If missing, install via your package manager or compile from source.

## Environment Configuration

### 1. Clone and Install Dependencies

```bash
git clone <repository-url> supplymars
cd supplymars
composer install
```

### 2. Environment Files

The project uses multiple `.env` files:

| File | Purpose |
|------|---------|
| `.env` | Default configuration (committed) |
| `.env.dev` | Development overrides (APP_SECRET) |
| `.env.local` | Local overrides (not committed) |
| `.env.test` | Test environment settings |

Create your local overrides if needed, for settings only your machine uses. The database name and ports are
not such a setting: they are in the `docker` block of `.env` (see [Development URLs](#development-urls)).

```bash
# .env.local (optional - for custom settings)
DEV_MAIL_RECIPIENT=me@example.com
```

### 3. Key Environment Variables

```bash
# Application
APP_ENV=dev                          # Environment (dev, test, prod)
APP_SECRET=your-secret-here          # Set in .env.dev

# Database name and local ports, read by Docker Compose too (see Development URLs)
DATABASE_NAME=app
DATABASE_PORT=3306
REDIS_PORT=6379
MAILER_PORT=1025

# Database
DATABASE_URL=mysql://root:password@127.0.0.1:${DATABASE_PORT}/${DATABASE_NAME}?serverVersion=8.4&charset=utf8mb4

# Redis: the app cache and sessions. The password must match REDIS_PASSWORD in compose.override.yaml
REDIS_URL=redis://default:password@127.0.0.1:${REDIS_PORT}

# Message queue (a table in the database)
MESSENGER_TRANSPORT_DSN=doctrine://default

# Mail (Mailpit in dev)
MAILER_DSN=smtp://127.0.0.1:${MAILER_PORT}
DEV_MAIL_RECIPIENT=your@email.com
MAILER_FROM=no-reply@supplymars.com  # Sender address of all mail

# File Storage (local in dev)
DEFAULT_URI=https://localhost:8000
```

## First-Time Setup

### Option A: Symfony Server + Docker Services (Recommended)

This approach runs PHP locally for faster iteration while Docker provides infrastructure.

```bash
# 1. Start infrastructure services (MySQL, Redis, Mailpit, the phpMyAdmins). Sessions are in Redis, so logging in needs it
make up-dev-tools

# 2. Create database and run migrations
symfony console doctrine:database:create --if-not-exists    # the MySQL container already creates it
symfony console doctrine:migrations:migrate --no-interaction

# 3. Load fixtures (optional - provides seed data)
symfony console doctrine:fixtures:load --no-interaction

# 4. Build Tailwind CSS
symfony console tailwind:build

# 5. Start Symfony server
symfony serve -d

# 6. Access the application
open https://127.0.0.1:8000
```

### Option B: Full Docker Stack

This approach runs everything in containers for complete isolation.

```bash
# 1. Start all services
make up

# 2. Access the application
open https://localhost
```

## Running Locally

### Symfony Server Mode

```bash
# Start server (background)
symfony serve -d

# Stop server
symfony server:stop

# View logs
symfony server:log

# Start dev tools if not running
make up-dev-tools
```

### Full Docker Mode

```bash
# Start all services
make up

# Stop all services
make down

# Shell into PHP container
make bash

# View logs
make logs

# View specific service logs
make logs-php
make logs-messenger
```

## Running Background Processes

### Message Consumer (Async Events)

For async domain events and email processing:

```bash
# Local (Symfony server mode)
symfony console messenger:consume async --time-limit=3600

# Docker mode - runs automatically via messenger service
docker compose logs -f messenger
```

### Simulation Commands

To populate realistic data, run simulation commands:

```bash
# Create customer orders
symfony console app:create-customer-orders 5

# Allocate orders to suppliers
symfony console app:build-purchase-orders 100

# Progress through fulfillment
symfony console app:accept-purchase-orders 50
symfony console app:ship-purchase-order-items 100
symfony console app:deliver-purchase-order-items 100

# Fluctuate stock levels
symfony console app:update-supplier-stock 100

# Generate reporting data
symfony console app:calculate-product-sales 30
symfony console app:calculate-order-sales 30
symfony console app:calculate-customer-sales 30
```

## Common Setup Failures

### Database Connection Refused

**Symptom:** `SQLSTATE[HY000] [2002] Connection refused`

**Causes:**
1. MySQL container not running
2. Wrong port/host in DATABASE_URL
3. Container not healthy yet

**Fixes:**
```bash
# Check container status
docker compose ps

# Verify MySQL is healthy
docker compose logs database

# Wait and retry
docker compose up -d database && sleep 10
```

### Redis Connection Failed

**Symptom:** `Connection refused [tcp://127.0.0.1:6379]`

**Fixes:**
```bash
# Check Redis container
docker compose ps redis

# Verify connection
docker compose exec redis sh -c 'REDISCLI_AUTH="$REDIS_PASSWORD" redis-cli ping'
# Should return: PONG
```

### Queued Messages Not Processed

**Symptom:** An email never arrives

With `symfony serve` no worker runs unless you start one. With `make up` the `messenger` container is the worker.

**Fixes:**
```bash
symfony console messenger:stats             # How many messages are waiting
symfony console messenger:consume async -vv # Work through them
symfony console messenger:failed:show       # Messages that failed after their retries
```

### Assets Not Loading

**Symptom:** Styles missing, JavaScript errors

**Fixes:**
```bash
# Rebuild assets (importmap first: the Tailwind build imports CSS from assets/vendor/)
symfony console importmap:install
symfony console tailwind:build

# Clear cache
symfony console cache:clear
```

### SSL Certificate Issues (Symfony Server)

**Symptom:** Browser shows certificate warning

**Fixes:**
```bash
# Install Symfony CA
symfony server:ca:install

# Restart server
symfony server:stop
symfony serve -d
```

### Nginx Container Unhealthy

The nginx healthcheck calls `/health`, so nginx is also reported unhealthy when the database, Redis or the queue is down. `curl -k https://localhost/health` shows which check failed, and `docker compose exec php tail var/log/dev.log` shows why.

### Test Database Not Found

**Symptom:** Tests fail with database errors

**Fixes:**
```bash
# Create test database
symfony console doctrine:database:create --env=test
symfony console doctrine:schema:create --env=test
```

## Switching to RabbitMQ

The queue is a database table by default ([ADR 013](adr/013-database-queue-by-default.md)). RabbitMQ is still defined in the compose files, behind the `rabbitmq` profile, so it does not start unless asked for. To use it:

1. **Require the transport package**, and add the PHP `sockets` extension it needs: to `docker-php-ext-install` in `docker/Dockerfile`, and to your local PHP if you use `symfony serve`.

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

   The playground commands pass `--profile playground`, which replaces `COMPOSE_PROFILES`. Add `--profile rabbitmq` to them as well, in the `Makefile` and in the deploy job of `.github/workflows/ci.yml`.

5. **Add `rabbitmq` to the `up-dev-tools` target** in the `Makefile`. It names the services it starts, and a
   named service starts whether or not its profile is on, so it is not listed by default.

`make up-dev-tools`, `make up` and the production targets then start RabbitMQ, and `php` waits for it to be healthy. The management UI is at http://localhost:15672 (guest / guest, port `RABBITMQ_UI_PORT`) in development. Tests are unaffected: the `test` environment always uses `in-memory://`.

Messages already waiting in the database queue are not moved. Let the worker empty it before switching (`symfony console messenger:stats`).

## Development URLs

| Service | URL | Port variable | Credentials |
|---------|-----|---------------|-------------|
| Application | https://localhost:8000 (Symfony) or https://localhost (Docker) | - | - |
| PHPMyAdmin | http://localhost:8080 | `PHPMYADMIN_PORT` | root / password |
| PHPMyAdmin (live) | http://localhost:8081 | `PHPMYADMIN_LIVE_PORT` | the live credentials |
| PHPMyAdmin (playground) | http://localhost:8082 | `PHPMYADMIN_PLAYGROUND_PORT` | the playground credentials |
| Mailpit | http://localhost:8025 | `MAILPIT_PORT` | - |
| MySQL | 127.0.0.1:3306 | `DATABASE_PORT` | root / password |
| Redis | 127.0.0.1:6379 | `REDIS_PORT` | default / password |

The port variables and `DATABASE_NAME` are in the `docker` block of `.env`. Docker Compose reads them from
that file, and `DATABASE_URL`, `REDIS_URL` and `MAILER_DSN` are built from them, so a change is made in one
place. Change them in `.env`, not `.env.local`: Symfony does not load `.env.local` in the `test`
environment, so dev and test would point at different servers. The production targets read the env files
in `../supplymars-secrets/` instead of `.env`, so none of this reaches production.

### One set of services, two ways to start them

Every service is defined once, in `compose.yaml`, with its development settings and published ports in
`compose.override.yaml`. `make up` starts them all. `make up-dev-tools` starts only the services it names
(MySQL, Redis, Mailpit and the three phpMyAdmins), for use with `symfony serve`. Both use the same
containers and volumes, so the database is the same whichever way you start it. `make up-prod-local`
reuses the development phpMyAdmin and Mailpit definitions.

### phpMyAdmin for the production databases

`phpmyadmin-prod-live` (http://localhost:8081) and `phpmyadmin-prod-playground` (http://localhost:8082)
connect through SSH tunnels on local ports 3307 and 3308. Open the tunnel and the page works; without one
it shows a connection error. Each has its own footer (`docker/phpmyadmin/`) so the two are not mixed up.

## Useful Commands

```bash
# Database
symfony console doctrine:migrations:migrate    # Run migrations
symfony console doctrine:schema:validate       # Validate mappings
symfony console doctrine:fixtures:load         # Load fixtures

# Cache
symfony console cache:clear                    # Clear all caches

# Messenger
symfony console messenger:consume async        # Process queue
symfony console messenger:failed:show          # View failed messages
symfony console messenger:failed:retry         # Retry failed messages
