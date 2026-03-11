# Operations Guide

This document covers deployment, runtime operations, and system maintenance for SupplyMars.

## Environments

### Development

**Characteristics:**
- `APP_ENV=dev`
- Debug mode enabled
- Local PHP server or Docker
- Mailpit captures all emails
- Redis with default credentials

**Setup:**
```bash
make up-dev-tools    # Infrastructure only
symfony serve -d     # Local PHP server
```

### Test

**Characteristics:**
- `APP_ENV=test`
- Isolated database (`app_test`)
- DAMA transaction rollback between tests
- Mock file storage

**Setup:**
```bash
symfony console doctrine:database:create --env=test
symfony console doctrine:schema:create --env=test
```

### Production

**Characteristics:**
- `APP_ENV=prod`
- OPcache enabled
- Assets pre-compiled
- Redis caching
- S3 file storage
- Secure credentials via environment variables

**Required Environment Variables:**
```bash
APP_SECRET=<32-char-secret>
DEFAULT_DOMAIN=supplymars.com
DEFAULT_URI=https://supplymars.com
DATABASE_URL=mysql://app:password@localhost:3306/supplymars
REDIS_URL=redis://user:password@localhost:6379
MESSENGER_TRANSPORT_DSN=amqp://user:password@localhost:5672/%2f/messages
MAILER_DSN=smtp://user:password@smtp.provider.com:587
AWS_S3_REGION=<region>
AWS_S3_BUCKET=<bucket-name>
AWS_S3_ACCESS_ID=<access-key>
AWS_S3_SECRET_ACCESS_KEY=<secret-key>
```

## Deployment Model

### Docker Production Stack

The production Docker stack includes:

| Service | Image | Purpose |
|---------|-------|---------|
| nginx | Custom (nginx-prod) | Reverse proxy, SSL termination |
| php | Custom (php-prod) | Application server |
| messenger | Custom (php-prod) | Async message consumer |
| cron | Custom (cron-prod) | Scheduled tasks |
| database | mysql:8.4 | Primary data store |
| redis | redis:8.4-alpine | Cache, sessions |
| rabbitmq | rabbitmq:4.2-management | Message queue |

**Deployment command:**
```bash
make up-prod
```

### Build Process

Production images are built in stages:

```dockerfile
# Stage 1: php-prod-builder
# - Installs production dependencies
# - Builds Tailwind CSS
# - Compiles asset map
# - Dumps optimized autoloader

# Stage 2: php-prod
# - Copies built assets from builder
# - Enables OPcache
# - Runs as www-data user
```

### Migration Strategy

Migrations run automatically on container start:

```bash
# docker/php/docker-entrypoint.sh
if [ "$RUN_MIGRATIONS" = "true" ]; then
    php bin/console doctrine:migrations:migrate --no-interaction
fi
```

Only the PHP container has `RUN_MIGRATIONS=true` to prevent race conditions.

## Cron / Scheduled Jobs

### Production Crontab

Located at `docker/php/cron/live-crontab`:

```cron
# Order simulation
*/5  * * * * app:create-customer-orders 2 --random

# Purchase order workflow (build before accept)
*/15       * * * * app:build-purchase-orders 20
1,16,31,46 * * * * app:accept-purchase-orders 20
0    * * * * app:ship-purchase-order-items 100
0    * * * * app:deliver-purchase-order-items 100

# Stock management
*/15 * * * * app:update-supplier-stock 20

# Reporting aggregation
3  0 * * * app:calculate-product-sales 1
5  0 * * * app:calculate-product-sales-summary
10 * * * * app:calculate-product-sales 1 0
40 * * * * app:calculate-product-sales 1 0

7  0 * * * app:calculate-order-sales 1
9  0 * * * app:calculate-order-sales-summary
10 * * * * app:calculate-order-sales 1 0
40 * * * * app:calculate-order-sales 1 0

# Customer insights aggregation
15 0 * * * app:calculate-customer-sales 1
17 0 * * * app:calculate-customer-sales-summary
20 * * * * app:calculate-customer-sales 1 0
50 * * * * app:calculate-customer-sales 1 0

# Log cleanup
0 0 * * 0 truncate -s 0 /var/log/cron.log
```

### Job Descriptions

| Schedule | Command | Purpose |
|----------|---------|---------|
| */5 min | `app:create-customer-orders 2` | Continuous order flow |
| */15 min | `app:build-purchase-orders 20` | Allocate pending orders |
| :01,:16,:31,:46 | `app:accept-purchase-orders 20` | Simulate supplier responses (1 min after build) |
| Hourly | `app:ship-purchase-order-items 100` | Progress to shipped |
| Hourly | `app:deliver-purchase-order-items 100` | Complete deliveries |
| */15 min | `app:update-supplier-stock 20` | Stock level fluctuation |
| Daily 00:03 | `app:calculate-product-sales 1` | Daily product sales ETL |
| Daily 00:07 | `app:calculate-order-sales 1` | Daily order sales ETL |
| */10,40 | `app:calculate-*-sales 1 0` | Hourly incremental updates |
| Daily 00:15 | `app:calculate-customer-sales 1` | Daily customer sales ETL |
| Daily 00:17 | `app:calculate-customer-sales-summary` | Customer summaries |
| */20,50 | `app:calculate-customer-sales 1 0` | Hourly customer updates |
| On demand | `app:generate-reviews {count}` | Generate fake reviews for testing |

### Error Handling

All cron commands use a continue-on-failure pattern (see [ADR-003](adr/003-simulation-first-design.md)). When an individual item fails during processing:

1. The exception is caught and logged with structured context (entity ID, error message)
2. Processing continues with the next item
3. A summary warning is output: `N item(s) failed — see logs for details.`
4. The command exits with code 1 (`Command::FAILURE`)

This means a single database error or entity issue will not halt an entire batch. Operators should monitor for non-zero exit codes in cron output and review application logs for failure details.

## Workers / Consumers

### Messenger Consumer

The messenger service processes async events:

```bash
php bin/console messenger:consume async --time-limit=3600 --sleep=5
```

**Configuration:**
- **Time limit:** 3600 seconds (1 hour) before restart
- **Sleep:** 5 seconds between batches
- **Retry:** 3 attempts with exponential backoff
- **Failure transport:** Doctrine (`doctrine://default?queue_name=failed`)

**Routed messages:**
- `Symfony\Component\Mailer\Messenger\SendEmailMessage`
- `App\Shared\Domain\Event\AsyncDomainEventInterface`

### Monitoring Messenger

```bash
# View queue status
symfony console messenger:stats

# View failed messages
symfony console messenger:failed:show

# Retry failed messages
symfony console messenger:failed:retry

# Process specific message
symfony console messenger:failed:retry <id>
```

### RabbitMQ Management

Access management UI at `http://localhost:15672`:
- View queue depths
- Monitor message rates
- Purge queues if needed

## Processing Simulator

The `ProcessingSimulator` service encapsulates business timing constraints for order fulfillment simulation. This service centralises the logic previously spread across individual console commands.

### Timing Constraints

| Operation | Business Hours | Minimum Wait | Success Rate |
|-----------|----------------|--------------|--------------|
| Shipping | 09:00-18:00 | 2 hours after acceptance | 95% |
| Delivery | 07:00-21:00 | 12 hours after shipping | 95% |

### Service Methods

**Probabilistic checks (for simulation):**
- `canShip()` - Returns true if item can ship (timing + 95% probability)
- `canDeliver()` - Returns true if item can deliver (timing + 95% probability)

**Timing-only checks (for testing):**
- `canShipTimingOnly()` - Returns true if timing constraints met (no probability)
- `canDeliverTimingOnly()` - Returns true if timing constraints met (no probability)

**Key file:** `src/Purchasing/Domain/Service/ProcessingSimulator.php`

### Usage in Commands

The shipping and delivery console commands use `ProcessingSimulator` to determine which items are eligible for status progression:

```php
// In ShipPOItemsCommand
foreach ($items as $item) {
    if ($this->simulator->canShip($item)) {
        // Progress item to SHIPPED
    }
}
```

## Data Reset / Simulation Behaviour

### Full Reset

To reset the database and start fresh:

```bash
# Drop and recreate database
symfony console doctrine:database:drop --force
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate --no-interaction

# Load fixtures (optional)
symfony console doctrine:fixtures:load --no-interaction
```
### Simulation Bootstrap

After fixtures, run simulation commands to build data:

```bash
# Create initial orders
symfony console app:create-customer-orders 100

# Allocate to suppliers
symfony console app:build-purchase-orders 200

# Progress through workflow
symfony console app:accept-purchase-orders 100
symfony console app:ship-purchase-order-items 200
symfony console app:deliver-purchase-order-items 200

# Generate product reviews
symfony console app:generate-reviews 100

# Generate reporting data
symfony console app:calculate-product-sales 30
symfony console app:calculate-order-sales 30
```

This sets all products to random stock between 0-300 units.

## Performance Considerations

### Database Optimization

**Indexes:** Doctrine mappings define indexes on frequently queried columns:
- Public IDs (ULID)
- Foreign keys
- Status fields
- Date ranges

**Query caching:** Production uses Redis for Doctrine query/result cache:
```yaml
# config/packages/doctrine.yaml (prod)
orm:
    query_cache_driver:
        type: pool
        pool: doctrine.system_cache_pool
    result_cache_driver:
        type: pool
        pool: doctrine.result_cache_pool
```

### Caching Strategy

**Application cache:** Redis via SNC Redis bundle
**Sessions:** Redis (configured in PHP INI)
**HTTP cache:** Nginx serves static assets with long TTL

### Reporting Performance

**Two-layer aggregation:**
1. Daily records (ProductSales, OrderSales) - computed once per day
2. Summary records (ProductSalesSummary, OrderSalesSummary) - pre-aggregated for dashboards

This avoids expensive aggregations on every dashboard load.

## Known Operational Risks

### Single Points of Failure

| Component | Risk | Mitigation |
|-----------|------|------------|
| MySQL | Data loss | Regular backups, replication |
| RabbitMQ | Message loss | Durable queues, clustering |
| Redis | Session loss | Persistence, sentinel |

### Race Conditions

**Migration races:** Only PHP container runs migrations (`RUN_MIGRATIONS=true`)

**Order locking:** Orders can be locked by users to prevent concurrent edits

**Stock oversell:** Not fully protected - relies on allocation time stock check

### Simulation Side Effects

**Order generation:** Creates real database records that consume storage

**Email sending:** In production, simulation commands may trigger actual emails

**Reporting data:** Simulation creates realistic but fabricated metrics

### Monitoring Recommendations

1. **Cron exit codes:** Alert on non-zero exit codes from simulation/reporting commands (indicates partial failures — see [Error Handling](#error-handling) above)
2. **Queue depth:** Alert if RabbitMQ queue exceeds threshold
3. **Failed messages:** Alert on any failed messenger messages
4. **Database size:** Monitor growth from simulation data
5. **Response times:** Track slow queries and API latency
6. **API error rates:** Monitor 4xx/5xx responses on `/api/` routes

### Backup Strategy

**Database:** The `app:backup-database` command runs daily at 02:00 UTC via the cron container. It creates a gzipped mysqldump, uploads to S3 (`unicorn-bucket-two/backups/`), and retains 30 days by default. The `--local-copy` option also saves the backup to a host-mounted directory for the playground reset. See [09-cli-reference.md](09-cli-reference.md) and [11-runbook.md](11-runbook.md) for details.

**Redis:** RDB snapshots enabled by default in the Redis image.

**File storage:** S3 for all uploads and media.

### Disaster Recovery

1. **Database restore:** See [11-runbook.md](11-runbook.md) Section 4.4 for full restore procedure.

2. **Reprocess failed messages:**
   ```bash
   symfony console messenger:failed:retry --all
   ```

3. **Regenerate reporting:**
   ```bash
   symfony console app:calculate-product-sales 90
   symfony console app:calculate-order-sales 90
   symfony console app:calculate-customer-sales 90
   ```
