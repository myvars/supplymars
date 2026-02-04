# Supplier Products

## What Supplier Products Are For

Supplier products link your catalog products to specific suppliers. Each supplier product represents a single product offering from a supplier, including:

- The supplier's cost price
- Stock level at the supplier
- Lead time for delivery
- Whether the offering is active

A single catalog product can have multiple supplier products, allowing you to source from different suppliers based on availability and cost.

## What You Can Do

- View all supplier products
- Create new supplier product offerings
- Edit supplier product details (cost, stock, etc.)
- Delete supplier products
- Map supplier products to catalog products
- Remove supplier products from catalog products
- Toggle active/inactive status

## Screens and Actions

### Supplier Product List

**Navigate to:** Purchasing > Supplier Products

The list shows all supplier products with:
- Product name
- Supplier name and product code
- Cost (in GBP)
- Stock level
- Active/Inactive status

**Sorting options:** ID, Name, Supplier, Cost, Stock, Status

**Filtering options:**
- By Supplier
- By Supplier Category
- By Supplier Subcategory
- By Supplier Manufacturer
- By Product Code
- By Stock Level (Any, In Stock, Out of Stock)
- By Status (Any, Active, Inactive)

### Create a Supplier Product

**Navigate to:** Purchasing > Supplier Products > Click **Add Supplier Product**

1. Fill in the form:
   - **Product Name** (required)
   - **Product Code** (required) - Supplier's SKU
   - **Supplier** (required) - Select supplier
   - **Category** (required) - Supplier's category
   - **Subcategory** (required) - Supplier's subcategory
   - **Manufacturer** (required) - Supplier's manufacturer
   - **Manufacturer Part Number** (required)
   - **Cost** (required) - Unit cost in GBP
   - **Stock Level** (required) - Current stock (0-10,000)
   - **Lead Time** (required) - Days for delivery (0-1,000)
   - **Weight** (required) - Product weight in grams
   - **Mapped Product ID** (optional) - Link to catalog product
   - **Active** (checkbox)

2. Click **Save**

### View Supplier Product Details

**Navigate to:** Click on any supplier product

The detail page shows:
- Full supplier product information
- Supplier name and category hierarchy
- Cost and stock status
- Mapped product link (if linked)
- Edit button

### Edit a Supplier Product

**Navigate to:** Supplier product detail > Click **Edit**

You can update:
- All product information fields
- Cost and stock levels
- Product mapping
- Active status

### Delete a Supplier Product

**Navigate to:** Supplier product detail > Edit > Click **Delete**

**Requirements:** The supplier product must NOT be mapped to a catalog product. Unmap it first using the Remove action.

### Map to Catalog Product

**Navigate to:** Supplier product detail > Click **Map Product**

This automatically:
1. Finds or creates a matching Manufacturer in the catalog
2. Finds or creates a matching Category
3. Finds or creates a matching Subcategory
4. Finds or creates a matching Product
5. Links the supplier product to the catalog product

### Remove from Catalog Product

**Navigate to:** Supplier product detail > Click **Remove**

1. Confirm the removal
2. The supplier product is unlinked but NOT deleted
3. The catalog product recalculates its pricing

### Toggle Status

**Navigate to:** Product Stock page > Click status toggle on supplier product card

Quickly toggle a supplier product between Active and Inactive.

## Fields and Options

| Field | Required | Range | Description |
|-------|----------|-------|-------------|
| Product Name | Yes | - | Supplier's product name |
| Product Code | Yes | - | Supplier's SKU/code |
| Supplier | Yes | - | Which supplier offers this |
| Category | Yes | - | Supplier's category classification |
| Subcategory | Yes | - | Supplier's subcategory |
| Manufacturer | Yes | - | Supplier's manufacturer name |
| Manufacturer Part Number | Yes | - | Manufacturer's part number |
| Cost | Yes | 0+ | Unit cost in GBP |
| Stock Level | Yes | 0-10,000 | Units available |
| Lead Time | Yes | 0-1,000 | Days until available if out of stock |
| Weight | Yes | 0-100,000 | Weight in grams |
| Mapped Product ID | No | - | Link to catalog product |
| Active | No | - | Whether offering is available |

## Status and Lifecycle

### Active Status

| Status | Meaning |
|--------|---------|
| **Active** (green) | Can be selected for purchase orders |
| **Inactive** (red) | Cannot be used for new orders |

### Stock Status Display

| Display | Meaning |
|---------|---------|
| **X in stock** | Has available inventory |
| **Out of stock (Y day lead time)** | No stock, shows expected wait |

## Active Source Selection

When a catalog product has multiple supplier products, the system automatically selects the "active source" based on:

1. **Stock availability** - Must have stock > 0
2. **Cost** - Lowest cost wins
3. **Active status** - Supplier product must be active
4. **Supplier active** - Parent supplier must be active

The active source determines the catalog product's displayed cost and stock level.

## Stock Dashboard

**Navigate to:** Product detail > **Stock** tab

This screen shows:
- Current inventory summary
- All supplier products for this catalog product
- Active source highlighted with green border
- Quick actions to toggle status or remove suppliers

## Warnings

- Changing cost affects the catalog product's sell price
- Setting to inactive may change which supplier is the active source
- Removing from a catalog product recalculates pricing
- Cannot delete supplier products that are mapped to catalog products
- Stock level changes trigger audit logging
- The active source supplier is automatically selected - you cannot manually choose it
