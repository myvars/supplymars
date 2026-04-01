# Manufacturers

## What Manufacturers Are For

Manufacturers represent the companies that produce the products in your catalog. Each product must be assigned to a manufacturer, which helps with:

- **Product organisation** - Grouping products by who makes them
- **Filtering and search** - Finding products by manufacturer in lists and reports
- **Supplier mapping** - Matching supplier products to catalog products via manufacturer part numbers

## What You Can Do

- View all manufacturers in a searchable list
- Create new manufacturers
- Edit manufacturer details (including inline name editing)
- Delete manufacturers (when they have no products)
- View products linked to a manufacturer

## Screens and Actions

### Manufacturer List

**Navigate to:** Catalog > Manufacturers

The manufacturer list shows all manufacturers with:
- Manufacturer name
- Active/Inactive status

**Sorting options:** ID, Name, Status

### Create a Manufacturer

**Navigate to:** Catalog > Manufacturers > Click **Create Manufacturer**

1. Fill in the form:
   - **Manufacturer Name** (required) - Display name (max 255 characters)
   - **Active** (checkbox) - Whether the manufacturer is available for use

2. Click **Save**

### View Manufacturer Details

**Navigate to:** Click on any manufacturer

The detail page shows:
- Manufacturer information card
- Product count with link to filtered product list

### Edit a Manufacturer

**Navigate to:** Manufacturer detail > Click **Edit**

You can update:
- Manufacturer name
- Active status

You can also edit the manufacturer name directly from the list or detail page by clicking the name (inline editing). Press **Enter** or click away to save, or press **Escape** to cancel.

### Delete a Manufacturer

**Navigate to:** Manufacturer detail > Edit > Click **Delete**

1. A confirmation dialog appears
2. Click **Delete** to confirm

**Requirements:** The manufacturer must have no products assigned. Reassign or remove all products first.

## Fields and Options

| Field | Required | Default | Description |
|-------|----------|---------|-------------|
| Manufacturer Name | Yes | - | Display name (max 255 characters) |
| Active | No | Inactive | Whether manufacturer is available for use |

## Status and Lifecycle

### Active Status

| Status | Meaning |
|--------|---------|
| **Active** (green) | Manufacturer is available for product assignment |
| **Inactive** (red) | Manufacturer is not available for new products |

## Warnings

- Manufacturers with linked products cannot be deleted
- Deactivating a manufacturer does not affect existing products
- Manufacturer names should be unique for clarity, though this is not enforced by the system
