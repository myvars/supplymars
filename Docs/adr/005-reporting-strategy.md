# ADR 005: Two-Layer Reporting Aggregation

## Status

Accepted

## Context

The reporting dashboard needed to show:

- Today's sales metrics vs. 7 days ago
- Trends over last 7, 30 days, or month-to-date
- Breakdowns by product, category, subcategory, manufacturer, supplier
- Quick page loads (< 1 second)

Running complex aggregation queries on every dashboard load would:
- Create database load
- Result in slow page loads
- Scale poorly as data grows

## Decision

We implemented a **two-layer aggregation** strategy:

### Layer 1: Daily Granular Records

**ProductSales** - One record per (product, supplier, date):
- salesQty
- salesCost
- salesValue
- salesDate

**OrderSales** - One record per date:
- orderCount
- orderValue
- averageOrderValue
- salesDate

These are computed from transactional data (PurchaseOrderItems, CustomerOrders) via scheduled commands.

### Layer 2: Pre-Computed Summaries

**ProductSalesSummary** - Aggregated across time dimensions:
- Grouped by: SalesType (product, category, subcategory, manufacturer, supplier, all)
- Grouped by: SalesDuration (today, last 7, last 30, MTD)

**OrderSalesSummary** - Aggregated order metrics:
- Grouped by: SalesDuration

These summaries are refreshed after daily data is computed.

### Query Strategy

**Dashboard KPIs**: Read from summary tables (single row per metric)

**Charts**: Query daily records with date range filtering

**Drill-downs**: Join daily records with dimension tables (product, category, etc.)

### Refresh Schedule

```cron
# Daily at midnight: Full calculation
3  0 * * * app:calculate-product-sales 1
7  0 * * * app:calculate-order-sales 1

# Summaries after daily calc
5  0 * * * app:calculate-product-sales-summary
9  0 * * * app:calculate-order-sales-summary

# Hourly: Incremental for today
10 * * * * app:calculate-product-sales 1 0
10 * * * * app:calculate-order-sales 1 0
```

## Consequences

### Positive

- **Fast dashboards**: KPIs read single rows, not aggregate millions
- **Predictable performance**: Query time independent of data volume
- **Flexible analysis**: Daily records support ad-hoc queries
- **Backfill capability**: Can regenerate summaries from daily records

### Negative

- **Data staleness**: Summaries lag behind real-time (hourly refresh)
- **Storage overhead**: Duplicated data (raw + summaries)
- **Consistency risk**: Summaries must stay in sync with daily records
- **ETL complexity**: Multiple commands to coordinate

### Implementation Notes

Key files:
- `src/Reporting/Domain/Model/SalesType/ProductSales.php` - Daily record
- `src/Reporting/Domain/Model/SalesType/ProductSalesSummary.php` - Aggregated
- `src/Reporting/Application/Handler/CalculateProductSalesHandler.php` - ETL logic
- `src/Reporting/Infrastructure/Persistence/Doctrine/ProductSalesDoctrineRepository.php` - Queries

The calculation handler uses delete-then-insert pattern:
```php
// Delete existing records for this date
$this->productSales->deleteByDate($date);

// Insert fresh calculations
foreach ($salesData as $row) {
    $productSales = ProductSales::create(...);
    $this->productSales->add($productSales);
}
```

This ensures idempotent execution - running the command twice produces the same result.
