# Simulations

## What Simulations Are For

SupplyMars includes simulation commands that generate realistic test data and automate business operations. These are used for:

- Demonstrating the platform's capabilities
- Testing order-to-delivery workflows
- Generating data for reports and dashboards
- Running continuous operations in demo environments

**Note:** Simulations are run via command line only; there is no web interface.

## Available Simulations

### Order Generation

**Command:** `app:create-customer-orders`

Creates simulated customer orders with random products and customers.

```bash
symfony console app:create-customer-orders 10
```

**Arguments:**
- `orderCount` (required) - Number of orders to create

**Options:**
- `--random` - Randomise actual count (0 to orderCount)

**What it creates:**
- New customer accounts flagged as `isSimulated = true` (or reuses existing simulated accounts)
- Addresses for shipping/billing
- Orders with 1-5 line items each
- Random product quantities

**Note:** Simulated users are visually distinguished from registered users on the customer detail page. Staff users are excluded from random selection for new orders.

### Order Allocation

**Command:** `app:build-purchase-orders`

Allocates pending customer orders to supplier purchase orders.

```bash
symfony console app:build-purchase-orders 50
```

**Arguments:**
- `orderCount` (optional, default: 50) - Orders to process

**What it does:**
- Finds orders in PENDING status
- Creates purchase orders for suppliers
- Links order items to PO items
- Updates order status to PROCESSING

### Purchase Order Acceptance

**Command:** `app:accept-purchase-orders`

Simulates suppliers accepting or rejecting purchase orders.

```bash
symfony console app:accept-purchase-orders 50
```

**Arguments:**
- `poCount` (optional, default: 50) - POs to process

**What it does:**
- Processes PROCESSING status PO items
- 98% acceptance rate (2% rejection)
- Updates item and PO status accordingly

### Purchase Order Shipping

**Command:** `app:ship-purchase-order-items`

Simulates shipping of accepted purchase order items.

```bash
symfony console app:ship-purchase-order-items 100
```

**Arguments:**
- `poItemCount` (optional, default: 50) - Items to process

**Realistic constraints:**
- Only processes ACCEPTED items
- Item must be at least 2 hours old
- Only runs during business hours (09:00-18:00)
- 95% probability when conditions met

### Purchase Order Delivery

**Command:** `app:deliver-purchase-order-items`

Simulates delivery of shipped items to customers.

```bash
symfony console app:deliver-purchase-order-items 100
```

**Arguments:**
- `poItemCount` (optional, default: 50) - Items to process

**Realistic constraints:**
- Only processes SHIPPED items
- Item must be shipped at least 12 hours ago
- Only runs during extended hours (07:00-21:00)
- 95% probability when conditions met

### Rejection Refunds

**Command:** `app:refund-purchase-orders`

Processes refunds for rejected items and re-allocates to alternative suppliers.

```bash
symfony console app:refund-purchase-orders 50
```

**Arguments:**
- `poCount` (optional, default: 50) - POs to process

**What it does:**
- Finds REJECTED purchase orders
- Sets items to REFUNDED status
- Re-runs allocation for new suppliers
- Creates new POs with alternatives

### Stock Updates

**Command:** `app:update-supplier-stock`

Simulates real-world stock fluctuations.

```bash
symfony console app:update-supplier-stock 100
```

**Arguments:**
- `productCount` (optional, default: 50) - Products to update

**What it does:**
- Selects random supplier products
- Low stock (≤20): Replenishes 0-100 units, varies cost ±10%
- Higher stock: Decreases by up to 10%

## Setup Commands

### Create Warehouse Products

**Command:** `app:create-warehouse-products`

Creates catalog products from warehouse supplier products.

```bash
symfony console app:create-warehouse-products
```

Use during initial system setup.

### Create Supplier Products

**Command:** `app:create-supplier-products`

Maps dropshipper products to catalog products.

```bash
symfony console app:create-supplier-products
```

Matches products by manufacturer part number.

### Activate Supplier Products

**Command:** `app:activate-supplier-products`

Activates inactive supplier products.

```bash
symfony console app:activate-supplier-products 100
```

### Reset Supplier Stock

**Command:** `app:reset-supplier-stock`

Resets all stock to random levels (for testing).

```bash
symfony console app:reset-supplier-stock 1
```

**Arguments:**
- `supplierId` (required) - Supplier ID to reset

Sets each product's stock to random 0-300 units.

### Review Generation

**Command:** `app:generate-reviews`

Generates sample product reviews for testing.

```bash
symfony console app:generate-reviews 50
```

Creates reviews linked to delivered orders with random ratings, titles, and body text.

## Reporting Commands

### Calculate Product Sales

**Command:** `app:calculate-product-sales`

Calculates product sales data for reports.

```bash
symfony console app:calculate-product-sales 30
```

**Arguments:**
- `dayCount` (required) - Days to process
- `dayOffset` (optional, default: 0) - Days back to start

### Calculate Order Sales

**Command:** `app:calculate-order-sales`

Calculates order sales data for reports.

```bash
symfony console app:calculate-order-sales 30
```

**Arguments:**
- `dayCount` (required) - Days to process
- `dayOffset` (optional, default: 0) - Days back to start

### Calculate Customer Sales

**Command:** `app:calculate-customer-sales`

Calculates customer sales data for customer insight reports.

```bash
symfony console app:calculate-customer-sales 30
```

**Arguments:**
- `dayCount` (required) - Days to process
- `dayOffset` (optional, default: 0) - Days back to start

### Calculate Summaries

**Commands:**
```bash
symfony console app:calculate-product-sales-summary
symfony console app:calculate-order-sales-summary
symfony console app:calculate-customer-sales-summary
```

Pre-aggregates data for dashboard and report performance.

## Typical Workflows

### Initial Data Setup

```bash
# Load fixtures
symfony console doctrine:fixtures:load --no-interaction

# Create catalog from warehouse
symfony console app:create-warehouse-products

# Map dropshipper products
symfony console app:create-supplier-products
```

### Generate Test Data

```bash
# Create orders
symfony console app:create-customer-orders 100

# Process through fulfilment
symfony console app:build-purchase-orders 200
symfony console app:accept-purchase-orders 100
symfony console app:ship-purchase-order-items 200
symfony console app:deliver-purchase-order-items 200

# Generate reports
symfony console app:calculate-product-sales 30
symfony console app:calculate-order-sales 30
```

### Daily Operations (Demo)

```bash
# Stock fluctuation
symfony console app:update-supplier-stock 100

# Order cycle
symfony console app:create-customer-orders 10
symfony console app:build-purchase-orders 50
symfony console app:accept-purchase-orders 30
symfony console app:ship-purchase-order-items 50
symfony console app:deliver-purchase-order-items 50

# Update reports
symfony console app:calculate-product-sales 1
symfony console app:calculate-order-sales 1
```

## Automated Scheduling

In demo/production environments, simulations run on schedules:

| Frequency | Commands |
|-----------|----------|
| Every 5 min | Create orders (small batches) |
| Every 15 min | Build POs, Accept POs, Update stock |
| Hourly | Ship items, Deliver items |
| Daily midnight | Calculate full reports |

## Warnings

- Simulations create real data in the database
- Running in production affects real records
- Time-based constraints mean some commands only work during certain hours
- Large batch sizes may take significant time
- Stock updates can affect pricing calculations
- Use `-v` flag for verbose output to monitor progress
