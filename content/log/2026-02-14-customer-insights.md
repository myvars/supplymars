---
title: Customer Insights
period: February 2026
summary: The two-layer reporting design from 2024, extended from products and orders to customers. Segments by lifetime orders, geography by where orders ship, refreshed twice an hour.
refs:
  - label: ADR 010 · Customer insights reporting
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/010-customer-insights-reporting.md
  - label: ADR 005 · Reporting strategy
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/005-reporting-strategy.md
---

The reports could tell you which products sold and how orders were trending. They couldn't tell you anything about the people placing those orders: how many were new, how many came back, where they were, or what a typical customer was worth. For an e-commerce business those are the first questions, so in February I built Customer Insights.

## The same two layers

Reporting in SupplyMars has worked the same way since 2024 (ADR 005). A first layer holds small daily records, one row per thing per day. A second layer holds summaries computed from them for each reporting period. The dashboard reads summaries, so it never aggregates raw orders while someone waits.

Customers fitted the same shape, with one addition: a second, platform-wide daily record.

**Layer 1** has two daily record types:

- `CustomerSales`, one row per customer per day: orders, order value and items.
- `CustomerActivitySales`, one row per day for the whole platform: total, active, new and returning customers.

Two types, because they answer different questions. Per-customer rows support drill-downs and top-customer lists. Platform-wide rows support trend charts without re-aggregating every customer's rows on each page load.

**Layer 2** has three summaries, each with its own dimension, for each reporting period:

- **Sales**: customers, revenue, average lifetime value, average order value, repeat rate, review rate.
- **Geography**: customers, orders and order value by city.
- **Segments**: customers, orders, order value, average order value and items per order for each segment.

Like the other reports, each calculation deletes and re-inserts its rows for the day or period it covers, so a rerun or a backfill gives the same answer.

## Segments by lifetime

The segments are **new** (one lifetime order), **returning** (two or three) and **loyal** (four or more). They count lifetime orders, not orders in the period. A customer with ten orders behind them who hasn't ordered this month is loyal, not new; their relationship with the business didn't change because the month did.

**Lapsed**, no order for more than sixty days, is separate. It measures time, not count, so by definition a loyal customer can also be lapsed, which is exactly the customer a business wants to win back.

## Geography by shipping city

Geography uses the shipping address, not the billing address. Where goods go is where the demand is, and that is what logistics and marketing act on.

## Why it lives in Reporting

Customer Insights reads across orders, customers and reviews, and everything it stores is derived, never the source of truth. That makes it reporting, not customer management, and it keeps the heavy aggregation queries away from the code that registers and edits customers.

## Refresh

Every night shortly after midnight, the previous day is calculated in full and the summaries are rebuilt. During the day, today's figures are recalculated at quarter past and quarter to each hour, so the dashboard is never more than half an hour behind.

## Indexes

Later in the month I added composite indexes for the queries the reports run most: orders by status and date, purchase orders by supplier, status and date, and two on purchase order items.

ADR 010 records the model, the metric definitions and the reasons above.
