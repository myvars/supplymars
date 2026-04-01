# Reports

## What Reports Are For

The reporting system provides business intelligence through:

- **Product Sales** - Sales performance by product, category, etc.
- **Order Summary** - Order trends and status breakdown
- **PO Performance** - Supplier fulfilment metrics
- **Overdue Orders** - Action items requiring attention
- **Customer Insights** - Customer analytics and segmentation

The operational **Dashboard** (a separate menu item, visible to admins) provides a quick at-a-glance overview with today's KPIs, items needing attention, and recent activity.

## What You Can Do

- View today's KPIs with week-over-week comparison
- Analyse order trends over time
- View product sales by various dimensions
- Track supplier performance and PO metrics
- Identify overdue orders
- Filter reports by date range
- Sort and drill down into data

## Screens and Actions

### Dashboard

**Navigate to:** Dashboard (top-level menu item, admin only)

The operational dashboard provides a quick overview of today's activity and items needing attention.

**Welcome Section:**
- Personalised greeting based on time of day
- Today's date
- Total action items count

**Quick Explore:** Shortcut links to Orders, Customers, Products, and Categories.

**Today at a Glance (KPI Cards):**
- **Orders** - Count of orders created today vs. same day last week
- **Revenue** - Total order value today vs. last week
- **Profit** - Sales profit today vs. last week
- **Margin** - Gross margin percentage today vs. last week

Each KPI card shows a green or red indicator for week-over-week change.

**Needs Attention:** Action items that require follow-up (only shown when items exist):
- Pending Orders (with link to filtered order list)
- Overdue Orders (with link to overdue report)
- Rejected POs (with link to filtered PO list)
- Pending Reviews (with link to moderation queue)

**Recent Activity:**
- Latest 5 orders with customer name, status, and total
- Top 5 products by sales quantity today

### Order Summary Report

**Navigate to:** Reporting > Order Summary

**Filter Options:**
- **Duration:** Today, Last 7 Days, Last 30 Days, Month to Date

**Metrics Displayed:**
- Order Count
- Order Value (£)
- Average Order Value (£)

**Visualisations:**
- **Bar Chart:** Selected metric over time
- **Doughnut Chart:** Orders by status breakdown

Click metric headers to change the chart display.

### Product Sales Report

**Navigate to:** Reporting > Product Sales

**Filter Options:**
- **Duration:** Today, Last 7 Days, Last 30 Days, Month to Date
- **Sales Type:** All Products, by Product, by Category, by Subcategory, by Manufacturer, by Supplier

**Metrics Displayed:**
- Sales Quantity
- Sales Cost (£)
- Sales Profit (£)
- Gross Margin (%)

**Visualisations:**
- **Bar Chart:** Selected metric over time
- **Top Products Table:** Sortable list of products

Click metric headers to change the chart and table sorting.

### Overdue Orders Report

**Navigate to:** Reporting > Overdue Orders

**Filter Options:**
- **Duration:** Today, Last 7 Days, Last 30 Days, Month to Date

**Metrics Displayed:**
- Overdue Order Count
- Overdue Order Value (£)

**Table Columns:**
- Order Number
- Customer Name
- Days Overdue
- Status
- Total Value

Click column headers to sort.

### PO Performance Report

**Navigate to:** Reporting > PO Performance

Analyses supplier performance across your purchase orders.

**Filter Options:**
- **Duration:** Today, Last 7 Days, Last 30 Days, Month to Date

**Metrics Displayed:**
- Purchase order count
- Total PO value
- Supplier delivery performance
- Acceptance and rejection rates

**Visualisations:**
- **Bar Chart:** PO metrics over time
- **Supplier breakdown:** Performance by supplier

---

## Customer Insights

The Customer Insights reports help you understand your customer base, identify top performers, and track customer activity trends.

### Accessing Customer Insights

1. From the menu, click **Reporting** > **Customer Insights**
2. Or click **Reporting** in the sidebar and select **Customer Insights**

### Available Reports

#### Top Customers

**Navigate to:** Reporting > Customer Insights

Shows customers ranked by total revenue with:
- Customer name and email
- Total lifetime revenue
- Order count
- Average order value

Filter by time period: 7 Days, 30 Days, 90 Days, 1 Year

#### Activity Trends

**Navigate to:** Reporting > Customer Insights

Line chart showing over time:
- **Active Customers** - Customers who placed orders
- **New Customers** - First-time buyers
- **Returning Customers** - Repeat purchasers

Helps identify growth patterns and seasonality.

#### Geographic Distribution

**Navigate to:** Reporting > Customer Insights > Geographic tab

Pie chart breaking down sales by customer city:
- Shows top cities by order value
- Percentage of total sales per location
- Useful for regional marketing decisions

#### Customer Segments

**Navigate to:** Reporting > Customer Insights > Segments tab

Categorises your customer base into segments:

| Segment | Definition |
|---------|------------|
| **New** | First-time or single-order customers (0-1 orders) |
| **Returning** | Customers with 2-3 orders placed |
| **Loyal** | Customers with 4+ orders placed |
| **Lapsed** | Any segment with no activity in 60+ days |

Shows:
- Customer count per segment
- Revenue contribution by segment
- Trends over time

### Customer Insights Metrics

| Metric | Calculation |
|--------|-------------|
| Active Customers | Unique customers with orders in period |
| New Customers | First-time buyers in period |
| Returning Customers | Repeat buyers in period |
| Repeat Rate | Returning ÷ Active × 100 |
| Average Customer Value | Total Revenue ÷ Active Customers |

---

## Date Range Options

| Duration | Description |
|----------|-------------|
| **Today** | Current day only |
| **Last 7 Days** | Past 7 calendar days |
| **Last 30 Days** | Past 30 calendar days (default) |
| **Month to Date** | First of current month to today |

## KPI Explanations

### Order Metrics

| Metric | Calculation |
|--------|-------------|
| Order Count | Number of orders created |
| Order Value | Sum of order totals (inc VAT) |
| Average Order Value | Order Value ÷ Order Count |

### Sales Metrics

| Metric | Calculation |
|--------|-------------|
| Sales Quantity | Units sold (from delivered PO items) |
| Sales Cost | Sum of cost prices for sold items |
| Sales Profit | Sales Value - Sales Cost |
| Gross Margin | (Sales Profit ÷ Sales Value) × 100 |

### Comparison Metrics

Dashboard KPIs show comparison to the same day last week:
- Green indicator: Improvement
- Red indicator: Decline
- Percentage change displayed

## Sales Type Breakdown

The Product Sales report can be filtered by:

| Sales Type | Shows |
|------------|-------|
| All Products | Aggregate across all products |
| Product | Individual product performance |
| Category | Performance by category |
| Subcategory | Performance by subcategory |
| Manufacturer | Performance by manufacturer |
| Supplier | Performance by supplier source |

## Chart Types

### Bar Charts

Used for time-series data:
- X-axis: Daily or monthly periods
- Y-axis: Selected metric
- Colour-coded by metric type

### Doughnut Charts

Used for categorical breakdown:
- Segments by status
- Percentage of total
- Colour-coded by status

## Data Refresh

Report data is pre-calculated for performance:

- **Daily calculation:** Runs overnight
- **Hourly updates:** For current day
- **Summaries:** Pre-aggregated for fast loading

This means:
- Historical data is accurate
- Today's data updates hourly
- Very recent changes may not appear immediately

## Warnings

- Reports show historical data, not real-time
- Today's figures update approximately hourly
- Large date ranges may take longer to load
- Sales metrics only include delivered items
- Order metrics include all order statuses
- Cancelled orders are included in counts but may be filtered in some views
