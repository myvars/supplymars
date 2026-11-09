---
title: From a live query to a sales warehouse
period: November – December 2024
summary: Sales reporting started as a live GROUP BY over delivered purchase order items. The next day it became a daily table, then pre-computed summaries, the two layers the reports still use. Then charts, order sales, an overdue orders report and the first KPI dashboard.
refs:
  - label: ADR 005 · Reporting strategy
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/005-reporting-strategy.md
---

With orders flowing through the simulation, the next question was the one any shop asks: what's selling? In November 2024 I built the answer, and rebuilt how it was stored twice within a week.

## A live query

The first version, in early November, was a best-sellers report: one query over delivered purchase order items, joined to the order lines and products, summing quantity, value and profit per product, filterable by category, subcategory, manufacturer or supplier and by date range. It was right, and it proved the report. It also read every delivered item each time the page loaded, and the simulation keeps adding more.

## A daily table

Within a day the report moved onto a table of its own. `ProductSales` holds one row per product, per supplier, per day: quantity, cost and value. A command calculates a day, and it starts by deleting that day's rows, so running it again, or backfilling a month, always gives the same answer. The report reads the daily rows instead of the raw items.

Before the week was out came the second layer. `ProductSalesSummary` holds the totals for each reporting period, for products and for each of the groupings, pre-computed from the daily rows. The page's totals come from a handful of summary rows; the ranked list still sums the daily rows, never the raw items.

Small daily records and summary tables built from them are how I ran reporting at Ebuyer, and they are still how every report in SupplyMars works. ADR 005, written later, records them, and the [Customer Insights](/log/customer-insights) reports of February 2026 were built the same way.

## Durations, twice

The reports needed periods: today, the last 7 and 30 days, month to date, and by day or month for charts. The first version made each a service implementing a `DurationInterface`, auto-tagged so the summary calculator could loop over them all. Within a week I replaced the six classes with one `Duration` enum, with a `match` for each period's start date, its date format and its chart label. A period is a value, not a service, and the enum says so with less code.

A `SalesType` enum named the five ways to group sales: product, category, subcategory, manufacturer and supplier.

## Charts, orders and a dashboard

The rest of the two months built on those layers:

- **Charts** of sales over time, with colours per series and tooltips that format money.
- **Order sales**, on the same pattern: `OrderSales` per day, and summaries of order count, value and average order value, with an order progress chart.
- **An overdue orders report**, of orders past their due date and by how many days.
- **The first dashboard**, in mid-December: eight KPI cards (order count, order value, average order value, sales cost, sales profit, gross margin, overdue orders and rejected purchase orders), six of them comparing today with the same day a week earlier, with an arrow for which way it moved, and overdue orders and rejected purchase orders counted over the last 30 days. The card is a Twig component, `KpiCard`, that formats a count, a currency or a percentage.

