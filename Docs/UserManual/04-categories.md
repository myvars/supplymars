# Categories & Subcategories

## What Categories Are For

Categories and subcategories organise your product catalog into a two-level hierarchy. Beyond organisation, they also control:

- **VAT rates** - Each category has an assigned VAT rate
- **Markup percentages** - Base pricing markup applied to products
- **Price models** - How final prices are calculated and rounded

## What You Can Do

### Categories
- View all categories
- Create new categories
- Edit category details including pricing settings
- Delete categories
- View products in a category
- View category sales analytics

### Subcategories
- View all subcategories
- Create new subcategories within a category
- Edit subcategory details including pricing overrides
- Delete subcategories
- View products in a subcategory

## Screens and Actions

### Category List

**Navigate to:** Catalog > Categories

The category list shows all categories with:
- Category name
- Active/Inactive status

**Sorting options:** ID, Name, Markup, Status

**Filtering options:**
- By VAT Rate
- By Price Model
- By Category Manager

### Create a Category

**Navigate to:** Catalog > Categories > Click **Create Category**

1. Fill in the form:
   - **Category Name** (required)
   - **VAT Rate** (required) - Select from available rates
   - **Category Markup %** (required) - Base markup percentage
   - **Price Model** (required) - How prices are calculated
   - **Category Manager** (required) - Staff member responsible
   - **Active** (checkbox)

2. Click **Save**

### View Category Details

**Navigate to:** Click on any category

The detail page shows:
- Category card with all settings
- List of subcategories in this category
- Product count and link
- Sales dashboard for category products

### Edit a Category

**Navigate to:** Category detail > Click **Edit**

### Delete a Category

**Navigate to:** Category detail > Edit > Click **Delete**

**Warning:** You should remove or reassign all products and subcategories before deleting a category.

---

### Subcategory List

**Navigate to:** Catalog > Subcategories

The subcategory list shows all subcategories with:
- Subcategory name
- Parent category
- Active/Inactive status

**Sorting options:** ID, Name, Category, Markup, Status

**Filtering options:**
- By Category
- By Price Model
- By Manager

### Create a Subcategory

**Navigate to:** Catalog > Subcategories > Click **Create Subcategory**

1. Fill in the form:
   - **Category** (required) - Parent category
   - **Subcategory Name** (required)
   - **Subcategory Markup %** (required) - Additional markup
   - **Price Model** (required) - Can be "None" to use category's model
   - **Subcategory Manager** (optional)
   - **Active** (checkbox)

2. Click **Save**

### View Subcategory Details

**Navigate to:** Click on any subcategory

The detail page shows:
- Subcategory card with settings
- Link to parent category
- Product count and link
- Sales dashboard

## Fields and Options

### Category Fields

| Field | Required | Default | Description |
|-------|----------|---------|-------------|
| Category Name | Yes | - | Display name |
| VAT Rate | Yes | - | Tax rate for all products |
| Category Markup % | Yes | 5.000% | Base markup percentage |
| Price Model | Yes | Default (Cost+) | Pricing calculation method |
| Category Manager | Yes | - | Responsible staff member |
| Active | No | Inactive | Whether category is active |

### Subcategory Fields

| Field | Required | Default | Description |
|-------|----------|---------|-------------|
| Category | Yes | - | Parent category |
| Subcategory Name | Yes | - | Display name |
| Subcategory Markup % | Yes | 0.000% | Additional markup on top of category |
| Price Model | Yes | None | Override category's model (or inherit) |
| Subcategory Manager | No | None | Responsible staff member (optional) |
| Active | No | Inactive | Whether subcategory is active |

### Price Models

| Model | Description |
|-------|-------------|
| **None** | Use parent category's price model (subcategories only) |
| **Default (Cost+)** | Standard cost-plus pricing |
| **Pretty 00** | Round to .00 endings (e.g., 100.00) |
| **Pretty 10** | Round to .10 endings (e.g., 100.10) |
| **Pretty 49** | Round to .49/.99 endings |
| **Pretty 95** | Round to .95 endings |
| **Pretty 99** | Round to .99 endings (psychological pricing) |

## Pricing Inheritance

Pricing is calculated in layers:

```
Product Cost
    ↓
+ Category Markup %
    ↓
+ Subcategory Markup % (if set)
    ↓
+ Product Markup % (if set)
    ↓
Apply Price Model Rounding
    ↓
+ VAT (from category)
    ↓
= Final Sell Price
```

**Example:**
- Cost: £10.00
- Category Markup: 50% → £15.00
- Subcategory Markup: 10% → £16.50
- Price Model (Pretty 99): → £16.99
- VAT (20%): → £20.39

## Status and Lifecycle

### Active Status

| Status | Meaning |
|--------|---------|
| **Active** (green) | Category/subcategory is available for use |
| **Inactive** (red) | Not available; products cannot be sold |

### Impact of Inactive Status

- **Inactive Category:** All products in this category cannot be sold
- **Inactive Subcategory:** All products in this subcategory cannot be sold

## Warnings

- Changing a category's VAT rate affects the sell price of all products in that category
- Changing markup percentages triggers price recalculation for all affected products
- Categories require a price model; subcategories can use "None" to inherit
- Deleting a category with products or subcategories may cause issues
- Setting a category to inactive prevents all its products from being sold
