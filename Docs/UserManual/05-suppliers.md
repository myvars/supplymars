# Suppliers

## What Suppliers Are For

Suppliers are the companies that provide products to fulfil customer orders. SupplyMars supports two types of suppliers:

- **Warehouse** - Your internal inventory/warehouse
- **Dropshippers** - External suppliers who ship directly to customers

When a customer places an order, the system can source items from multiple suppliers based on availability and cost.

## What You Can Do

- View all suppliers
- Create new suppliers
- Edit supplier details
- Delete suppliers
- View supplier's products
- View supplier sales analytics

## Screens and Actions

### Supplier List

**Navigate to:** Suppliers > Search

The supplier list shows all suppliers with:
- Supplier name
- Active/Inactive status
- Colour-coded cards for visual distinction

**Sorting options:** ID, Name, Status

### Create a Supplier

**Navigate to:** Suppliers > Search > Click **Create Supplier**

1. Fill in the form:
   - **Name** (required) - Supplier company name
   - **Active** (checkbox) - Whether supplier can receive orders

2. Click **Save**

### View Supplier Details

**Navigate to:** Click on any supplier

The detail page shows:
- Supplier information card
- Product count with link to supplier products
- Sales dashboard showing analytics for this supplier's products

### Edit a Supplier

**Navigate to:** Supplier detail > Click **Edit**

You can update:
- Supplier name
- Active status

### Delete a Supplier

**Navigate to:** Supplier detail > Edit > Click **Delete**

1. A confirmation dialog appears
2. Click **Delete** to confirm

**Warning:** Ensure the supplier has no active purchase orders before deleting.

## Fields and Options

| Field | Required | Default | Description |
|-------|----------|---------|-------------|
| Name | Yes | - | Supplier company name (max 255 characters) |
| Active | No | Inactive | Whether supplier can receive purchase orders |

## Status and Lifecycle

### Active Status

| Status | Meaning |
|--------|---------|
| **Active** (green) | Supplier can receive purchase orders |
| **Inactive** (red) | Supplier cannot receive new orders |

### Impact of Inactive Status

When a supplier is set to inactive:
- No new purchase orders will be created for this supplier
- Existing purchase orders continue to be processed
- Products from this supplier won't be selected for new orders
- The system will choose alternative suppliers if available

## Warehouse vs Dropshipper

### Warehouse Supplier

One supplier is designated as the "warehouse" - your internal inventory:
- Products ship from your own stock
- You control stock levels directly
- Typically faster delivery times
- Named "Turtle Inc" by default

### Dropship Suppliers

External suppliers that ship directly to customers:
- You don't hold inventory
- Supplier ships on your behalf
- May have longer lead times
- Cost and availability varies by supplier

## Supplier Selection Logic

When allocating orders, the system selects suppliers based on:

1. **Stock availability** - Must have units in stock
2. **Cost** - Lowest cost supplier preferred
3. **Active status** - Both supplier and supplier product must be active

If no single supplier can fulfil an entire order, items may be split across multiple suppliers.

## Warnings

- Setting a supplier to inactive affects all products sourced from them
- Products may need alternative suppliers assigned if primary becomes inactive
- Deleting a supplier with linked products may cause pricing issues
- The warehouse supplier should generally remain active
- Inactive suppliers cannot fulfil existing pending orders
