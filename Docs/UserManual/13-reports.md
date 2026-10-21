# Reports and Dashboard

Charts and tables of sales, orders, customers and supplier items, plus the Dashboard for today at a glance.

Press `?` on this screen for a short summary.

**Where:** **Reporting > Product Sales**, **Order Summary**, **Customer Insights**, **PO Performance**, **Overdue Orders**. The **Dashboard** is its own menu item. Reports and the Dashboard are open to all staff.

## Tasks

### Pick a period

Every report has four buttons at the top right: **Today**, **7 days**, **30 days** (the default) and **MTD** (month to date).

### Read a report

| Report | Shows | Click to |
|---|---|---|
| **Product Sales** | Sales Qty, Sales Cost, Sales Profit, Gross Margin; a bar chart; a Top Products table (Qty, Cost, Profit, Margin) | A metric card re-sorts the table and redraws the chart by that metric |
| **Order Summary** | Order Count, Order Revenue, AOV (average order value); a bar chart; an Order Progress doughnut by status | A metric card redraws the charts by that metric; a bar opens the order list for that bar's dates; a doughnut slice opens the orders in that status and period |
| **Customer Insights** | Three tabs: Overview, Geographic, Segments | See the figures below |
| **PO Item Performance** (menu: PO Performance) | Two KPIs, Total Items and Total Profit; a table of PO items (Product, Supplier, PO, Status, Profit), lowest profit first, 10 per page | Column headings sort by Product, Supplier, Status or Profit |
| **Overdue Orders** | Overdue Order Count, Overdue Order Value; a table (Order No, Customer, Overdue, Status, Total) | Column headings sort |

**Customer Insights figures** (Overview tab). "Active customers" means customers who placed an order in the period.

| Figure | Meaning |
|---|---|
| Total Customers | Every customer account that is not staff. Ignores the period |
| Active Customers | Customers who placed an order in the period |
| New Customers | Active customers whose first order is in the period |
| Repeat Rate | Active customers who had ordered before the period, as a % of active customers |
| Avg CLV | Revenue from all orders ever, divided by Total Customers. Ignores the period |
| Avg AOV | The period's revenue divided by its number of orders |
| Avg Orders/Customer | The period's orders divided by active customers |
| Review Rate | Active customers with a published review, as a % of active customers |

**Segments tab.** New, Returning and Loyal count the customers who ordered in the period. Each one's lifetime order count picks the segment:

| Segment | Customer has |
|---|---|
| New | 1 order in total |
| Returning | 2–3 orders in total |
| Loyal | 4 or more orders in total |
| Lapsed | No order in the period needed: a last order within the year before the period starts, but more than 60 days before it |

### See sales for one product, category, subcategory, manufacturer or supplier

The Product Sales report is embedded, filtered to that record, on:

- the product's **Sales** tab;
- the detail page of a category, subcategory, manufacturer or supplier.

**Reporting > Product Sales** always shows all products. It has no filter of its own.

### Use the Dashboard

Open **Dashboard** from the menu. It shows today's figures against the same weekday last week, a **Needs Attention** card (only when something needs it) linking to the lists, and recent activity. Press `?` there for what each card counts.

## Rules and limits

- **What counts as a sale.** Product Sales counts delivered PO items, on the day they were delivered. An item ordered but not yet delivered is not a sale.
- **Order values are ex VAT.** Order Revenue, AOV, Overdue Order Value, the Overdue Total column and the Dashboard's Revenue are order totals before VAT. Customer Insights revenue figures (Revenue, AOV, Avg CLV) include VAT, so they will not match Order Summary.
- **Cancelled orders are left out** of every order and customer figure. PO Item Performance leaves out cancelled and refunded PO items.
- **Overdue** means the due date has passed and the order is neither delivered nor cancelled. The period picks orders created since its start.
- **PO Item Performance** counts PO items created in the period.
- **How fresh the figures are:**

| Report | Updated |
|---|---|
| Product Sales, Order Summary, Customer Insights | Today's figures every 30 minutes. Yesterday is finalised just after midnight (UTC) |
| Overdue Orders, PO Item Performance | Live, on every page load |

So a sale or order made in the last half hour may not show yet. The commands behind this are in the [CLI Reference](../09-cli-reference.md).

## Related

- [Orders](08-orders.md): the order list the charts link to, and order statuses.
- [Purchase Orders](10-purchase-orders.md): PO item statuses and Rejected POs.
- [Products](03-products.md), [Categories and Subcategories](04-categories.md), [Manufacturers](16-manufacturers.md), [Suppliers](05-suppliers.md): pages with an embedded sales report.
- [Reviews](15-reviews.md): the moderation queue behind Pending Reviews.
- [Customers](07-customers.md): one customer's insights and segment.
