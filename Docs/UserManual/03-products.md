# Products

## What Products Are For

Products are the items in your catalog that customers can order. Each product represents a single item with its name, description, pricing, and stock information. Products are linked to categories, subcategories, and manufacturers, which determine their pricing structure and organisation.

## What You Can Do

- View all products in a searchable, sortable list
- Create new products
- Edit product details
- Delete products
- Manage product images
- View product sales analytics
- Activate or deactivate products

## Screens and Actions

### Product List

**Navigate to:** Catalog > Products

The product list displays all products in card format with:

- Product image (thumbnail)
- Product name
- Product number (#000001 format)
- Sell price (including VAT)
- Stock level
- Category and subcategory
- Manufacturer and part number
- Active/Inactive status

**Sorting options:**
- ID
- Name
- Stock
- Price
- Status

**Filtering options:**
- By Category (with dependent Subcategory filter)
- By Manufacturer
- By Manufacturer Part Number
- By Stock Level (Any, In Stock, Out of Stock)

### Create a Product

**Navigate to:** Catalog > Products > Click **Create Product**

1. Fill in the form:
   - **Product Name** (required) - The display name
   - **Product Description** (optional) - Brief description
   - **Category** (required) - Select from dropdown
   - **Subcategory** (required) - Options update based on selected category
   - **Manufacturer** (required) - Select from dropdown
   - **Manufacturer Part Number** (required) - The manufacturer's SKU
   - **Product Manager** (optional) - Assign a staff member
   - **Active** (checkbox) - Whether product is available for sale

2. Click **Save**

New products are created as **Inactive** by default. You must activate them and link supplier products before they can be sold.

### Edit a Product

**Navigate to:** Product detail page > Click **Edit** (pencil icon)

You can update:
- Product name and description
- Category and subcategory
- Manufacturer and part number
- Product manager
- Active status

### Delete a Product

**Navigate to:** Product detail page > Click **Delete**

1. A confirmation dialog appears
2. Click **Delete** to confirm

**Warning:** Deleting a product is permanent and cannot be undone.

### View Product Details

**Navigate to:** Click on any product in the list

The detail page shows:
- Full product information card
- Navigation tabs for related screens

**Available tabs:**
- **Details** - Product information
- **Stock** - Current stock and supplier options
- **Cost** - Pricing and markup settings
- **Images** - Product image gallery
- **Sales** - Sales analytics for this product
- **History** - Stock and cost change history with charts (filterable by time period)
- **Reviews** - Review summary with average rating, distribution, and latest published reviews

### Manage Product Images

**Navigate to:** Product detail > **Images** tab

The Images tab shows all images for a product in a responsive grid with drag-and-drop management.

**Uploading images:**
- Drag files onto the upload area, or click to browse
- Upload up to 10 images at a time
- Maximum file size: 2 MB per image
- Accepted formats: all image types (JPG, PNG, GIF, WebP, etc.)

**Managing images:**
- **Reorder:** Drag and drop images to change their display order
- **Delete:** Hover over an image and click the delete button
- **Primary image:** The first image (position 1) is marked with a "Primary" badge and becomes the product thumbnail shown in lists and cards

Each image card shows the filename, upload date, and file size.

## Fields and Options

### Product Information

| Field | Required | Description |
|-------|----------|-------------|
| Product Name | Yes | Display name (max 255 characters) |
| Product Description | No | Brief description (max 255 characters) |
| Category | Yes | Parent category for organisation and VAT |
| Subcategory | Yes | Must belong to selected category |
| Manufacturer | Yes | Product manufacturer |
| Manufacturer Part Number | Yes | Manufacturer's SKU/code |
| Product Manager | No | Staff member responsible for product |
| Active | No | Whether product is available for sale |

### Calculated Fields (Read-Only)

These fields are calculated automatically:

| Field | Description |
|-------|-------------|
| Stock Level | From active supplier product |
| Lead Time | Days until stock available |
| Cost | Unit cost from active supplier |
| Sell Price (exc VAT) | Calculated from cost + markup |
| Sell Price (inc VAT) | Sell price + VAT |
| Current Markup % | Effective markup percentage |

## Status and Lifecycle

### Active Status

| Status | Meaning |
|--------|---------|
| **Active** (green) | Product is published and available for sale |
| **Inactive** (red) | Product is not available for sale |

### Valid for Sale

A product can only be sold when ALL of these are true:
- Product is Active
- Has an active supplier product linked
- Category is active
- Subcategory is active

### Stock Status

| Indicator | Meaning |
|-----------|---------|
| **X in stock** | Product has available inventory |
| **Out of stock** | No units available, shows lead time |

## Pricing Hierarchy

Product pricing follows a hierarchy:

1. **Category** - Sets base markup and VAT rate
2. **Subcategory** - Can add additional markup
3. **Product** - Can override with product-specific markup

The system automatically selects the best supplier based on cost and availability.

## Warnings

- Products cannot be sold until they have an active supplier product linked
- Changing a product's category may affect its pricing and VAT rate
- Deleting a product is permanent and removes all associated data
- Products with orders in progress should not be deactivated
