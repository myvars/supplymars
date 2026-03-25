# CLI Reference

This document lists all custom console commands in SupplyMars. Most of these run on cron to simulate e-commerce activity - creating orders, processing shipments, and fluctuating stock levels.

## Error Handling

All simulation and reporting commands that process entities in loops follow a consistent error handling pattern (see [ADR-003](adr/003-simulation-first-design.md)):

- **Continue on failure:** If an individual item fails, the command catches the exception, logs it, and continues to the next item. A single failure does not halt the entire batch.
- **Failure logging:** Each failure is logged with structured context (entity ID, error message) via Monolog.
- **Console warning:** After the loop completes, if any items failed, a warning is displayed: `N item(s) failed — see logs for details.`
- **Exit codes:**
  - `0` (`Command::SUCCESS`) — all items processed successfully.
  - `1` (`Command::FAILURE`) — one or more items failed during processing.
  - `2` (`Command::INVALID`) — invalid input arguments.

This applies to: `app:create-customer-orders`, `app:build-purchase-orders`, `app:accept-purchase-orders`, `app:ship-purchase-order-items`, `app:deliver-purchase-order-items`, `app:refund-purchase-orders`, `app:update-supplier-stock`, `app:generate-reviews`, `app:calculate-order-sales`, `app:calculate-customer-sales`, `app:calculate-product-sales`.

---

## Order Context

### app:create-customer-orders

**Purpose:** Create simulated customer orders with random products and customers.

**File:** `src/Order/UI/Console/CreateCustomerOrdersCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `orderCount` | int | Yes | - | Number of orders to create |

**Options:**
| Name | Description |
|------|-------------|
| `--random` | Randomize the order count (0 to orderCount) |
| `--dry-run` | Preview what would be created without persisting |
| `--skip-timing` | Skip timing delays (useful for testing) |

**Example:**
```bash
# Create exactly 10 orders
symfony console app:create-customer-orders 10

# Create 0-10 orders (random)
symfony console app:create-customer-orders 10 --random

# Verbose output shows created order IDs
symfony console app:create-customer-orders 5 -v
```

**Side Effects:**
- Creates User entities (if needed)
- Creates Address entities (if needed)
- Creates CustomerOrder entities
- Creates CustomerOrderItem entities (1-5 per order)

**Constants:**
- `MAX_ORDER_LINES = 5` - Maximum items per order
- `MAX_LINE_QTY = 5` - Maximum quantity per line

---

## Purchasing Context

### app:build-purchase-orders

**Purpose:** Allocate pending customer orders to supplier purchase orders.

**File:** `src/Purchasing/UI/Console/BuildPOsCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `orderCount` | int | No | 50 | Number of orders to process |

**Options:**
| Name | Description |
|------|-------------|
| `--dry-run` | Preview allocations without persisting |

**Example:**
```bash
# Process up to 50 orders
symfony console app:build-purchase-orders

# Process up to 100 orders
symfony console app:build-purchase-orders 100

# Preview what would be created
symfony console app:build-purchase-orders 20 --dry-run -v
```

**Side Effects:**
- Creates PurchaseOrder entities (one per supplier per order)
- Creates PurchaseOrderItem entities
- Updates CustomerOrderItem status to PROCESSING
- Triggers domain events

---

### app:accept-purchase-orders

**Purpose:** Simulate supplier acceptance/rejection of purchase orders.

**File:** `src/Purchasing/UI/Console/AcceptPOsCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `poCount` | int | No | 50 | Number of POs to process |

**Options:**
| Name | Description |
|------|-------------|
| `--dry-run` | Preview without persisting changes |
| `--supplier=ID` | Target a specific supplier by internal ID |

**Example:**
```bash
# Accept/reject up to 50 POs
symfony console app:accept-purchase-orders

# Process more POs
symfony console app:accept-purchase-orders 100

# Target a specific supplier
symfony console app:accept-purchase-orders 50 --supplier=3
```

**Side Effects:**
- Updates PurchaseOrderItem status to ACCEPTED (98%) or REJECTED (2%)
- Updates PurchaseOrder status (derived from items)
- Updates CustomerOrderItem status (derived from PO items)

**Constants:**
- `REJECTION_ODDS = 50` - 1 in 50 chance of rejection per item

---

### app:ship-purchase-order-items

**Purpose:** Simulate shipping of accepted purchase order items.

**File:** `src/Purchasing/UI/Console/ShipPOItemsCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `poItemCount` | int | No | 50 | Number of items to process |

**Options:**
| Name | Description |
|------|-------------|
| `--dry-run` | Preview without persisting changes |
| `--supplier=ID` | Target a specific supplier by internal ID |
| `--skip-timing` | Bypass business hours and wait time checks |

**Example:**
```bash
symfony console app:ship-purchase-order-items 100

# Skip timing constraints (for testing)
symfony console app:ship-purchase-order-items 100 --skip-timing

# Target specific supplier
symfony console app:ship-purchase-order-items 50 --supplier=3
```

**Side Effects:**
- Updates PurchaseOrderItem status to SHIPPED
- Cascades status updates to parent entities

**Simulation Logic:**
- Item must be in ACCEPTED status
- Status change timestamp must be 2+ hours old
- Current time must be between 09:00-18:00
- 95% probability of shipping when conditions met
- Use `--skip-timing` to bypass timing/probability checks

---

### app:deliver-purchase-order-items

**Purpose:** Simulate delivery of shipped purchase order items.

**File:** `src/Purchasing/UI/Console/DeliverPOItemsCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `poItemCount` | int | No | 50 | Number of items to process |

**Options:**
| Name | Description |
|------|-------------|
| `--dry-run` | Preview without persisting changes |
| `--supplier=ID` | Target a specific supplier by internal ID |
| `--skip-timing` | Bypass business hours and wait time checks |

**Example:**
```bash
symfony console app:deliver-purchase-order-items 100

# Skip timing constraints (for testing)
symfony console app:deliver-purchase-order-items 100 --skip-timing

# Target specific supplier
symfony console app:deliver-purchase-order-items 50 --supplier=3
```

**Side Effects:**
- Updates PurchaseOrderItem status to DELIVERED
- Sets `deliveredAt` timestamp
- Cascades status updates to parent entities

**Simulation Logic:**
- Item must be in SHIPPED status
- Status change timestamp must be 12+ hours old
- Current time must be between 07:00-21:00
- 95% probability of delivery when conditions met
- Use `--skip-timing` to bypass timing/probability checks

---

### app:refund-purchase-orders

**Purpose:** Process refunds for rejected purchase orders and re-allocate.

**File:** `src/Purchasing/UI/Console/RefundPOsCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `poCount` | int | No | 50 | Number of POs to process |

**Example:**
```bash
symfony console app:refund-purchase-orders 20
```

**Side Effects:**
- Updates all items in rejected POs to REFUNDED status
- Re-runs allocation on customer order (finds alternative suppliers)
- May create new PurchaseOrders

---

### app:update-supplier-stock

**Purpose:** Simulate real-world supplier stock level and cost fluctuations.

**File:** `src/Purchasing/UI/Console/UpdateSupplierStockCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `productCount` | int | No | 50 | Number of products to update |

**Example:**
```bash
# Update 50 random supplier products
symfony console app:update-supplier-stock

# Update 100 products with verbose output
symfony console app:update-supplier-stock 100 -v
```

**Side Effects:**
- Modifies SupplierProduct stock levels
- Modifies SupplierProduct costs
- Raises `SupplierProductStockWasChangedEvent`
- May trigger product pricing recalculation

**Simulation Logic:**
- If stock ≤ 20: Replenish with 0-100 units
- Otherwise: Decrease by up to 10%
- Cost varies by ±10%

**Constants:**
- `COST_VARIANCE_PERCENT = 10`
- `STOCK_VARIANCE_PERCENT = 10`
- `STOCK_REPLENISH_LEVEL = 20`

---

## Purchasing Setup Commands

### app:create-supplier-products

**Purpose:** Map supplier products to catalog products (initial setup).

**File:** `src/Purchasing/UI/Console/Setup/CreateSupplierProductsCommand.php`

**Arguments:** None

**Example:**
```bash
symfony console app:create-supplier-products
```

**Side Effects:**
- Iterates all non-warehouse suppliers
- Matches SupplierProducts to Products by manufacturer part number
- Creates new Products if no match found
- Links SupplierProducts to matched/created Products

---

### app:create-warehouse-products

**Purpose:** Create catalog products from warehouse supplier products.

**File:** `src/Purchasing/UI/Console/Setup/CreateWarehouseProductsCommand.php`

**Arguments:** None

**Example:**
```bash
symfony console app:create-warehouse-products
```

**Side Effects:**
- Gets warehouse supplier
- Creates Product entities from SupplierProducts
- Maps SupplierProducts to created Products

---

## Purchasing Utilities

### app:activate-supplier-products

**Purpose:** Activate inactive supplier products.

**File:** `src/Purchasing/UI/Console/Utilities/ActivateSupplierProductsCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `productCount` | int | No | 50 | Number of products to activate |

**Example:**
```bash
symfony console app:activate-supplier-products 100
```

**Side Effects:**
- Toggles `isActive` status on supplier products
- Triggers `SupplierProductStatusWasChangedEvent`

---

### app:reset-supplier-stock

**Purpose:** Reset all supplier product stock levels to random values.

**File:** `src/Purchasing/UI/Console/Utilities/ResetSupplierStockCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `supplierId` | string | Yes | - | Supplier internal ID |

**Example:**
```bash
# Reset stock for supplier with ID 1
symfony console app:reset-supplier-stock 1
```

**Side Effects:**
- Sets all supplier's products to random stock (0-300)
- Useful for testing scenarios

**Constants:**
- `MAX_STOCK_LEVEL = 300`

---

### app:rewind-mixed-status-purchase-orders

**Purpose:** Find and rewind purchase orders with inconsistent item statuses back to pending.

**File:** `src/Purchasing/UI/Console/Utilities/RewindMixedStatusPurchaseOrdersCommand.php`

**Arguments:** None

**Options:**
| Name | Description |
|------|-------------|
| `--limit=N` | Maximum POs to process (default: 100) |
| `--days-back=N` | Number of days back to search (default: 30) |
| `--dry-run` | Preview without persisting changes |

**Example:**
```bash
# Find and rewind POs with mixed item statuses
symfony console app:rewind-mixed-status-purchase-orders

# Preview what would be rewound
symfony console app:rewind-mixed-status-purchase-orders --dry-run -v

# Search last 60 days, limit to 50 POs
symfony console app:rewind-mixed-status-purchase-orders --days-back=60 --limit=50
```

**Side Effects:**
- Finds POs where items have inconsistent statuses (e.g., some accepted, some rejected)
- Resets PO and all items to PENDING status
- Clears status change audit logs for the PO
- Regenerates parent customer order status

**Use Cases:**
- Error recovery when POs have mixed accepted/rejected items
- Testing scenarios requiring PO reset
- Recovering from system issues

---

## Reporting Context

### app:calculate-customer-sales

**Purpose:** Calculate daily customer sales and activity metrics for reporting.

**File:** `src/Reporting/UI/Console/CalculateCustomerSalesCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `dayCount` | int | Yes | - | Number of days to process |
| `dayOffset` | int | No | 0 | Day offset from today |

**Options:**
| Name | Description |
|------|-------------|
| `--dry-run` | Preview without persisting changes |
| `--skip-summary` | Skip auto-running the summary command |

**Example:**
```bash
# Calculate last 7 days
symfony console app:calculate-customer-sales 7

# Calculate yesterday only
symfony console app:calculate-customer-sales 1 1

# Preview what would be calculated
symfony console app:calculate-customer-sales 7 --dry-run

# Calculate without running summaries
symfony console app:calculate-customer-sales 7 --skip-summary
```

**Side Effects:**
- Creates/updates CustomerSales entities (per-customer daily records)
- Creates/updates CustomerActivitySales entities (active, new, returning counts)
- Deletes existing data for processed dates
- Automatically runs `app:calculate-customer-sales-summary` if offset is 0 (unless `--skip-summary`)

---

### app:calculate-customer-sales-summary

**Purpose:** Calculate pre-aggregated customer sales summaries for dashboard performance.

**File:** `src/Reporting/UI/Console/CalculateCustomerSalesSummaryCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `rebuild` | int | No | 0 | Full rebuild flag |

**Options:**
| Name | Description |
|------|-------------|
| `--duration` | Duration to aggregate (7d, 30d, 90d, 365d) |

**Example:**
```bash
# Incremental update for all durations
symfony console app:calculate-customer-sales-summary

# Full rebuild
symfony console app:calculate-customer-sales-summary 1

# Specific duration
symfony console app:calculate-customer-sales-summary --duration=30d
```

**Side Effects:**
- Creates/updates CustomerSalesSummary entities (top customers by revenue)
- Creates/updates CustomerGeographicSummary entities (sales by city)
- Creates/updates CustomerSegmentSummary entities (sales by customer segment)

---

### app:calculate-product-sales

**Purpose:** Calculate and store product sales data for reporting.

**File:** `src/Reporting/UI/Console/CalculateProductSalesCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `dayCount` | int | Yes | - | Number of days to process |
| `dayOffset` | int | No | 0 | Day offset from today |

**Example:**
```bash
# Calculate last 7 days
symfony console app:calculate-product-sales 7

# Calculate yesterday only
symfony console app:calculate-product-sales 1 1

# Calculate 30 days starting from 7 days ago
symfony console app:calculate-product-sales 30 7
```

**Side Effects:**
- Creates/updates ProductSales entities
- Deletes existing data for processed dates
- Automatically runs `app:calculate-product-sales-summary` if offset is 0

---

### app:calculate-product-sales-summary

**Purpose:** Calculate pre-aggregated product sales summaries.

**File:** `src/Reporting/UI/Console/CalculateProductSalesSummaryCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `rebuild` | int | No | 0 | Full rebuild flag |

**Example:**
```bash
# Incremental update
symfony console app:calculate-product-sales-summary

# Full rebuild
symfony console app:calculate-product-sales-summary 1
```

**Side Effects:**
- Creates/updates ProductSalesSummary entities
- Aggregates across all SalesType × SalesDuration combinations

---

### app:calculate-order-sales

**Purpose:** Calculate and store order sales data for reporting.

**File:** `src/Reporting/UI/Console/CalculateOrderSalesCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `dayCount` | int | Yes | - | Number of days to process |
| `dayOffset` | int | No | 0 | Day offset from today |

**Example:**
```bash
# Calculate last 7 days
symfony console app:calculate-order-sales 7

# Calculate yesterday only
symfony console app:calculate-order-sales 1 1
```

**Side Effects:**
- Creates/updates OrderSales entities
- Deletes existing data for processed dates
- Automatically runs `app:calculate-order-sales-summary` if offset is 0

---

### app:calculate-order-sales-summary

**Purpose:** Calculate pre-aggregated order sales summaries.

**File:** `src/Reporting/UI/Console/CalculateOrderSalesSummaryCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `rebuild` | int | No | 0 | Full rebuild flag |

**Example:**
```bash
# Incremental update
symfony console app:calculate-order-sales-summary

# Full rebuild
symfony console app:calculate-order-sales-summary 1
```

**Side Effects:**
- Creates/updates OrderSalesSummary entities
- Aggregates across all SalesDuration values

---

## Review Context

### app:generate-reviews

**Purpose:** Generate fake product reviews for eligible delivered purchases.

**File:** `src/Review/UI/Console/GenerateReviewsCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `count` | int | No | 20 | Maximum number of reviews to generate |
| `productId` | int | No | - | Optional product ID to target |

**Example:**
```bash
# Generate up to 20 reviews
symfony console app:generate-reviews

# Generate up to 100 reviews
symfony console app:generate-reviews 100

# Generate reviews for a specific product
symfony console app:generate-reviews 50 42
```

**Side Effects:**
- Creates ProductReview entities in PENDING status
- Auto-generates weighted random ratings
- Creates or updates ProductReviewSummary for affected products

**Simulation Logic:**
- Finds delivered orders without existing reviews
- Applies weighted random rating distribution:
  - 5 stars: 35%
  - 4 stars: 35%
  - 3 stars: 15%
  - 2 stars: 10%
  - 1 star: 5%
- Selects from predefined title and body text per rating level

---

## Shared Context

### app:backup-database

**Purpose:** Backup the MySQL database to the backups filesystem (local disk in dev, S3 in prod).

**File:** `src/Shared/UI/Console/Utilities/BackupDatabaseCommand.php`

**Options:**
| Name | Type | Default | Description |
|------|------|---------|-------------|
| `--retention-days` | int | 30 | Number of days to retain backups (older backups are deleted) |
| `--dry-run` | bool | false | Show what would happen without executing |
| `--local-copy` | string | null | Copy the gzipped backup to this local path |

**Example:**
```bash
# Standard backup (uploads to S3 in prod, saves to var/backups/ in dev)
symfony console app:backup-database

# Backup with local copy (used by live cron for playground reset)
symfony console app:backup-database --local-copy=/backups/latest.sql.gz

# Dry run
symfony console app:backup-database --dry-run

# Custom retention
symfony console app:backup-database --retention-days=60
```

**Side Effects:**
- Creates a gzipped mysqldump (`supplymars-YYYY-MM-DD-HHmmss.sql.gz`)
- Uploads to the configured Flysystem backups filesystem
- Optionally copies the gzipped file to a local path (`--local-copy`)
- Deletes backups older than the retention period

**Cron:** Runs daily at 02:00 UTC on the live stack (see `docker/php/cron/live-crontab`).

---

### app:backfill-ulids

**Purpose:** Backfill missing ULID public IDs for entities.

**File:** `src/Shared/UI/Console/Utilities/BackfillUlidsCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `batchSize` | int | No | 200 | Batch size for flushing |

**Options:**
| Name | Description |
|------|-------------|
| `--limit` | Maximum rows per entity |

**Example:**
```bash
# Backfill all entities
symfony console app:backfill-ulids

# Backfill with smaller batches
symfony console app:backfill-ulids 50

# Backfill with limit
symfony console app:backfill-ulids --limit=1000
```

**Side Effects:**
- Generates ULIDs for entities with null `publicId`
- Uses `initializePublicId()` method on entities
- Processes in batches to manage memory

---

## Customer Context

### app:send-test-emails

**Purpose:** Send all customer email templates to Mailpit for visual preview. Dev environment only.

**File:** `src/Customer/UI/Console/SendTestEmailsCommand.php`

**Arguments:** None

**Example:**
```bash
symfony console app:send-test-emails
```

**Side Effects:**
- Sends three emails to Mailpit: Verify Email, Reset Password, Admin Access Granted
- Uses `MailerHelper` with dummy data (no database interaction)
- Only registered in the `dev` environment (`#[When('dev')]`)

**Emails sent:**
| Email | Template |
|-------|----------|
| Verify Email | `customer/registration/verify-email.html.twig` |
| Reset Password | `customer/reset_password/reset-password.html.twig` |
| Admin Access Granted | `customer/admin-access-granted.html.twig` |

---

### app:setup-playground

**Purpose:** Prepare the playground environment after a database sync from production. Resets staff passwords, scrambles staff emails, and creates or resets the demo user.

**File:** `src/Customer/UI/Console/SetupPlaygroundCommand.php`

**Arguments:** None

**Example:**
```bash
symfony console app:setup-playground
```

**Requires:** `PLAYGROUND_MODE=1` — refuses to run in production.

**What it does:**
1. Finds all staff users (except the demo user)
2. Replaces their passwords with random hashes
3. Replaces their emails with `{random}@redacted.local`
4. Creates or resets a demo user (`demo@supplymars.com` / `demo`) with `ROLE_ADMIN`

**Typical usage:** Run after the nightly database sync from production to playground.

---

## Standard Symfony Commands

### Database

```bash
# Run migrations
symfony console doctrine:migrations:migrate

# Create migration
symfony console doctrine:migrations:diff

# Validate mappings
symfony console doctrine:schema:validate

# Load fixtures
symfony console doctrine:fixtures:load
```

### Cache

```bash
# Clear all caches
symfony console cache:clear

# Clear specific pool
symfony console cache:pool:clear <pool>
```

### Messenger

```bash
# Process async queue
symfony console messenger:consume async

# View queue stats
symfony console messenger:stats

# View failed messages
symfony console messenger:failed:show

# Retry failed messages
symfony console messenger:failed:retry
```

### Debug

```bash
# List all routes
symfony console debug:router

# List all services
symfony console debug:container

# Show autowiring options
symfony console debug:autowiring

# Show event listeners
symfony console debug:event-dispatcher
```

---

## Command Chaining (Typical Usage)

### Initial Data Setup

```bash
# 1. Load fixtures
symfony console doctrine:fixtures:load --no-interaction

# 2. Create products from warehouse
symfony console app:create-warehouse-products

# 3. Map dropshipper products
symfony console app:create-supplier-products
```

### Simulation Bootstrap

```bash
# 1. Create initial orders
symfony console app:create-customer-orders 100

# 2. Allocate to suppliers
symfony console app:build-purchase-orders 200

# 3. Progress through fulfillment
symfony console app:accept-purchase-orders 100
symfony console app:ship-purchase-order-items 200
symfony console app:deliver-purchase-order-items 200

# 4. Generate reporting data
symfony console app:calculate-product-sales 30
symfony console app:calculate-order-sales 30
symfony console app:calculate-customer-sales 30
```

### Daily Operations

```bash
# Stock fluctuation
symfony console app:update-supplier-stock 100

# Order processing
symfony console app:create-customer-orders 10
symfony console app:build-purchase-orders 50
symfony console app:accept-purchase-orders 30
symfony console app:ship-purchase-order-items 50
symfony console app:deliver-purchase-order-items 50

# Reporting refresh
symfony console app:calculate-product-sales 1
symfony console app:calculate-order-sales 1
symfony console app:calculate-customer-sales 1
```
