# ADR 010: Customer Insights Reporting

## Status

Accepted

## Context

The reporting dashboard (ADR-005) covered product and order analytics but lacked customer-level intelligence. The business needed:

- Customer activity trends (active, new, returning customers over time)
- Geographic distribution of revenue by city
- Customer segmentation (NEW/RETURNING/LOYAL/LAPSED) with revenue breakdowns
- Per-customer lifetime metrics on profile pages (CLV, AOV, segment)
- Fast dashboard loads following the established two-layer pattern

Key design questions:

1. **Where should this live?** Customer context owns identity and authentication. Reporting context owns analytics and aggregation.
2. **How to segment customers?** Segmentation could be based on period activity or lifetime behaviour.
3. **One summary table or many?** Geographic and segment breakdowns have different dimensions and cardinalities.

## Decision

We extended the two-layer reporting strategy from ADR-005 into a **three-domain customer reporting system**, placed in the Reporting context.

### Layer 1: Daily Granular Records

**CustomerSales** - One record per (customer, date):
- orderCount, orderValue, itemCount, salesDate

**CustomerActivitySales** - One record per date (platform-wide):
- totalCustomers, activeCustomers, newCustomers, returningCustomers, salesDate

Two daily record types are needed because per-customer records support drill-downs and top-customer queries, while platform-wide activity records support trend charts without re-aggregating per-customer data on every load.

### Layer 2: Pre-Computed Summaries

Three separate summary entities, each with its own dimension:

**CustomerSalesSummary** - Keyed by (duration, date):
- totalCustomers, activeCustomers, newCustomers, returningCustomers
- totalRevenue, averageClv, averageAov, repeatRate, reviewRate, averageOrdersPerCustomer

**CustomerGeographicSummary** - Keyed by (city, duration, date):
- customerCount, orderCount, orderValue, averageOrderValue

**CustomerSegmentSummary** - Keyed by (segment, duration, date):
- customerCount, orderCount, orderValue, averageOrderValue, averageItemsPerOrder

### Customer Segmentation Model

Segmentation is based on **lifetime** order count, not orders within the reporting period:

| Segment | Criteria |
|---------|----------|
| NEW | 1 lifetime order |
| RETURNING | 2-3 lifetime orders |
| LOYAL | 4+ lifetime orders |
| LAPSED | No orders in the last 60 days (orthogonal to the above) |

Lifetime-based segmentation was chosen over period-based because it reflects the customer's relationship with the platform, not just recent activity. A customer with 10 lifetime orders who hasn't ordered this month is LOYAL, not NEW.

LAPSED is calculated separately from the other segments because it is time-based rather than count-based. A customer can be both LOYAL (by lifetime orders) and LAPSED (by inactivity).

### Geographic Aggregation

Geographic data uses the **shipping address city**, not billing address. Shipping address better represents where demand exists, which is the actionable insight for logistics and marketing.

### Why Reporting Context

Customer Insights lives in the Reporting context, not Customer, because:

1. It aggregates data across Order, Customer, and Review contexts
2. Daily records and summaries are derived data, not source-of-truth
3. Heavy ETL queries are isolated from transactional Customer operations
4. It follows the same pattern as ProductSales and OrderSales

### Metric Definitions

- **Repeat rate**: returning customers / active customers in period x 100
- **Average CLV**: total lifetime revenue / total customers
- **Review rate**: customers who reviewed / active customers in period x 100

### Refresh Schedule

```cron
# Daily at midnight: Full calculation with offset
11 0 * * * app:calculate-customer-sales 1 1
13 0 * * * app:calculate-customer-sales-summary 1

# Incremental: Recalculate today
15,45 * * * * app:calculate-customer-sales 1
```

## Consequences

### Positive

- **Consistent architecture**: Follows the exact two-layer pattern from ADR-005 (ProductSales, OrderSales)
- **Fast dashboards**: All three dashboard views read from pre-computed summaries
- **Actionable segmentation**: Lifetime-based segments are stable and meaningful for marketing
- **Profile integration**: CustomerProfileInsightsHandler provides per-customer metrics on detail pages
- **Idempotent refresh**: Delete-then-insert pattern allows safe re-runs

### Negative

- **Three summary tables**: More storage and ETL complexity than a single denormalized table, but each dimension (overall, geographic, segment) has different cardinality and query patterns
- **Segmentation lag**: Segment assignment reflects the last ETL run, not real-time. Acceptable given hourly incremental refresh
- **LAPSED overlap**: A customer can appear in both their count-based segment and LAPSED, which requires careful handling in UI to avoid double-counting

### Implementation Notes

Key files:
- `src/Reporting/Domain/Model/SalesType/CustomerSales.php` - Per-customer daily record
- `src/Reporting/Domain/Model/SalesType/CustomerActivitySales.php` - Platform-wide daily record
- `src/Reporting/Domain/Model/SalesType/CustomerSalesSummary.php` - Overall summary
- `src/Reporting/Domain/Model/SalesType/CustomerGeographicSummary.php` - Geographic summary
- `src/Reporting/Domain/Model/SalesType/CustomerSegmentSummary.php` - Segment summary
- `src/Reporting/Domain/Metric/CustomerSegment.php` - Segmentation enum
- `src/Reporting/Application/Handler/CalculateCustomerSalesHandler.php` - Daily ETL
- `src/Reporting/Application/Handler/CalculateCustomerSalesSummaryHandler.php` - Summary ETL
- `src/Reporting/Application/Handler/Report/CustomerInsightsReportHandler.php` - Dashboard handler
- `src/Reporting/Application/Handler/Report/CustomerProfileInsightsHandler.php` - Profile card handler

## Related Documentation

- [ADR 005: Two-Layer Reporting Aggregation](005-reporting-strategy.md)
- [Features: Customer Insights](../05-features.md)
