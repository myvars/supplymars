# CLI Reference

This document lists all custom console commands in SupplyMars. Most of these run on cron to simulate e-commerce activity - creating orders, processing shipments, and fluctuating stock levels.

## Order Context

### app:create-customer-orders

**Purpose:** Create simulated customer orders with random products and customers.

**File:** `src/Order/UI/Console/createCustomerOrdersCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `orderCount` | int | Yes | - | Number of orders to create |

**Options:**
| Name | Description |
|------|-------------|
| `--random` | Randomize the order count (0 to orderCount) |

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

**File:** `src/Purchasing/UI/Console/buildPOsCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `orderCount` | int | No | 50 | Number of orders to process |

**Example:**
```bash
# Process up to 50 orders
symfony console app:build-purchase-orders

# Process up to 100 orders
symfony console app:build-purchase-orders 100

# Verbose output
symfony console app:build-purchase-orders 20 -v
```

**Side Effects:**
- Creates PurchaseOrder entities (one per supplier per order)
- Creates PurchaseOrderItem entities
- Updates CustomerOrderItem status to PROCESSING
- Triggers domain events

---

### app:accept-purchase-orders

**Purpose:** Simulate supplier acceptance/rejection of purchase orders.

**File:** `src/Purchasing/UI/Console/acceptPOsCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `poCount` | int | No | 50 | Number of POs to process |

**Example:**
```bash
# Accept/reject up to 50 POs
symfony console app:accept-purchase-orders

# Process more POs
symfony console app:accept-purchase-orders 100
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

**File:** `src/Purchasing/UI/Console/shipPOItemsCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `poItemCount` | int | No | 50 | Number of items to process |

**Example:**
```bash
symfony console app:ship-purchase-order-items 100
```

**Side Effects:**
- Updates PurchaseOrderItem status to SHIPPED
- Cascades status updates to parent entities

**Simulation Logic:**
- Item must be in ACCEPTED status
- Status change timestamp must be 2+ hours old
- Current time must be between 09:00-18:00
- 95% probability of shipping when conditions met

---

### app:deliver-purchase-order-items

**Purpose:** Simulate delivery of shipped purchase order items.

**File:** `src/Purchasing/UI/Console/deliverPOItemsCommand.php`

**Arguments:**
| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `poItemCount` | int | No | 50 | Number of items to process |

**Example:**
```bash
symfony console app:deliver-purchase-order-items 100
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

---

### app:refund-purchase-orders

**Purpose:** Process refunds for rejected purchase orders and re-allocate.

**File:** `src/Purchasing/UI/Console/refundPOsCommand.php`

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

**File:** `src/Purchasing/UI/Console/updateSupplierStockCommand.php`

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

**File:** `src/Purchasing/UI/Console/Setup/createSupplierProductsCommand.php`

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

**File:** `src/Purchasing/UI/Console/Setup/createWarehouseProductsCommand.php`

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

**File:** `src/Purchasing/UI/Console/Utilities/activateSupplierProductsCommand.php`

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

**File:** `src/Purchasing/UI/Console/Utilities/resetSupplierStockCommand.php`

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

## Reporting Context

### app:calculate-product-sales

**Purpose:** Calculate and store product sales data for reporting.

**File:** `src/Reporting/UI/Console/calculateProductSalesCommand.php`

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

**File:** `src/Reporting/UI/Console/calculateProductSalesSummaryCommand.php`

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

**File:** `src/Reporting/UI/Console/calculateOrderSalesCommand.php`

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

**File:** `src/Reporting/UI/Console/calculateOrderSalesSummaryCommand.php`

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

## Shared Context

### app:backfill-ulids

**Purpose:** Backfill missing ULID public IDs for entities.

**File:** `src/Shared/UI/Console/Utilities/backfillUlidsCommand.php`

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
```
