# SupplyMars User Manual

## What the System Does

SupplyMars is an e-commerce and supply chain operations platform that enables you to:

- **Manage your product catalog** - Create and organise products into categories and subcategories, set pricing, and track stock levels
- **Work with multiple suppliers** - Source products from your own warehouse and external dropship suppliers, each with their own costs and stock
- **Process customer orders** - Create orders, add items, and track them through to delivery
- **Fulfil through purchase orders** - Automatically or manually allocate orders to suppliers and track fulfilment progress
- **Monitor pricing and margins** - Set markup percentages and VAT rates at category, subcategory, or product level
- **Track business performance** - Use dashboards and reports to monitor sales, orders, and operational metrics

The system handles the complexity of multi-supplier sourcing, where a single customer order might be fulfilled by multiple suppliers based on stock availability and cost.

## Who This Manual Is For

This manual is written for **operations staff and administrators** who use SupplyMars day-to-day to:

- Manage the product catalog
- Process and track orders
- Work with suppliers
- Monitor business performance
- Configure system settings

You should have admin access to the system (staff role) to perform most of the tasks described in this manual.

## How to Use This Manual

### Getting Started

If you're new to SupplyMars, start with:

1. **[Getting Started](01-getting-started.md)** - Learn how to log in and understand your role
2. **[Navigation](02-navigation.md)** - Understand how to navigate the system

### Feature Guides

Once you're familiar with the basics, refer to these guides based on what you need to do:

| I want to... | See... |
|--------------|--------|
| Add or edit products | [Products](03-products.md) |
| Organise products into categories | [Categories & Subcategories](04-categories.md) |
| Manage suppliers | [Suppliers](05-suppliers.md) |
| Link supplier products to the catalog | [Supplier Products](06-supplier-products.md) |
| Manage customer accounts | [Customers](07-customers.md) |
| Create and track orders | [Orders](08-orders.md) |
| Manage order line items | [Order Items](09-order-items.md) |
| Track supplier fulfilment | [Purchase Orders](10-purchase-orders.md) |
| Monitor stock levels | [Stock Management](11-stock.md) |
| Set up pricing and VAT | [Pricing & VAT](12-pricing.md) |
| View reports and KPIs | [Reports & Dashboard](13-reports.md) |
| Run simulations | [Simulations](14-simulations.md) |

### Reference

- **[Troubleshooting](troubleshooting.md)** - Common problems and solutions

## Document Conventions

Throughout this manual:

- **Bold text** indicates buttons, menu items, or field labels you should click or look for
- `Monospace text` indicates values you type or system-generated values like IDs
- ⚠️ **Warning** boxes highlight actions that cannot be undone
- 💡 **Tip** boxes provide helpful suggestions
- Field names match what you see on screen

## Support

If you encounter issues not covered in this manual or the troubleshooting guide, contact your system administrator.

---

## Quick Reference

### Key Concepts

| Term | Meaning |
|------|---------|
| **Product** | An item in your catalog that customers can order |
| **Supplier** | A company that provides products (your warehouse or external dropshippers) |
| **Supplier Product** | A specific product offering from a supplier with its own cost and stock |
| **Customer Order** | A purchase made by a customer |
| **Purchase Order (PO)** | An order placed with a supplier to fulfil a customer order |
| **Allocation** | The process of assigning customer order items to supplier purchase orders |

### Status Meanings

**Order Status:**
| Status | Meaning |
|--------|---------|
| Pending | Order created, not yet allocated to suppliers |
| Processing | Items allocated to purchase orders |
| Shipped | At least one item has shipped |
| Delivered | All items delivered |
| Cancelled | Order cancelled |

**Purchase Order Status:**
| Status | Meaning |
|--------|---------|
| Pending | PO created, awaiting processing |
| Processing | Sent to supplier |
| Accepted | Supplier confirmed they can fulfil |
| Rejected | Supplier cannot fulfil |
| Shipped | Supplier has dispatched |
| Delivered | Items received |
| Refunded | Rejected items refunded |
| Cancelled | PO cancelled |
