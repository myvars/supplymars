# Local Development Setup

This guide covers getting SupplyMars running locally. The setup follows standard Symfony conventions.

## Prerequisites

Before setting up SupplyMars locally, ensure you have:

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.5+ | With extensions: intl, pdo_mysql, bcmath, gd, redis |
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

Create your local overrides if needed:

```bash
# .env.local (optional - for custom settings)
DATABASE_URL=mysql://root:mypassword@127.0.0.1:3306/app?serverVersion=8.4&charset=utf8mb4
```

### 3. Key Environment Variables

```bash
# Application
APP_ENV=dev                          # Environment (dev, test, prod)
APP_SECRET=your-secret-here          # Set in .env.dev

# Database
DATABASE_URL=mysql://root:password@127.0.0.1:3306/app?serverVersion=8.4&charset=utf8mb4

# Redis
REDIS_URL=redis://default:password@127.0.0.1:6379

# Message Queue
MESSENGER_TRANSPORT_DSN="phpamqplib://guest:guest@127.0.0.1:5672/%2f/messages"

# Mail (Mailpit in dev)
MAILER_DSN=smtp://127.0.0.1:1025
DEV_MAIL_RECIPIENT=your@email.com

# File Storage (local in dev)
DEFAULT_URI=https://localhost:8000
```

## First-Time Setup

### Option A: Symfony Server + Docker Services (Recommended)

This approach runs PHP locally for faster iteration while Docker provides infrastructure.

```bash
# 1. Start infrastructure services
make up-dev-tools

# 2. Create database and run migrations
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate --no-interaction

# 3. Load fixtures (optional - provides seed data)
symfony console doctrine:fixtures:load --no-interaction

# 4. Build assets
symfony console tailwind:build
symfony console importmap:install
symfony console asset-map:compile

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
docker compose exec redis redis-cli ping
# Should return: PONG
```

### RabbitMQ Connection Failed

**Symptom:** `The connection was refused`

**Fixes:**
```bash
# Check RabbitMQ health
docker compose ps rabbitmq

# Access management UI
open http://localhost:15672
# Credentials: guest/guest
```

### Assets Not Loading

**Symptom:** Styles missing, JavaScript errors

**Fixes:**
```bash
# Rebuild assets
symfony console tailwind:build
symfony console importmap:install
symfony console asset-map:compile

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

### Test Database Not Found

**Symptom:** Tests fail with database errors

**Fixes:**
```bash
# Create test database
symfony console doctrine:database:create --env=test
symfony console doctrine:schema:create --env=test
```

## Development URLs

| Service | URL | Credentials |
|---------|-----|-------------|
| Application | https://localhost:8000 (Symfony) or https://localhost (Docker) | - |
| PHPMyAdmin | http://localhost:8080 | root / password |
| Mailpit | http://localhost:8025 | - |
| RabbitMQ | http://localhost:15672 | guest / guest |

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
