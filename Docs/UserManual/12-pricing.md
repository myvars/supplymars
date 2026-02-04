# Pricing & VAT

## What Pricing Is For

The pricing system calculates sell prices from supplier costs using:

- **Markup percentages** - Added at category, subcategory, and product levels
- **VAT rates** - Tax rates assigned to categories
- **Price models** - Rounding rules for final prices

## What You Can Do

- Manage VAT rates
- Set category markup and pricing
- Set subcategory markup and pricing
- Set product-specific markup and pricing
- View pricing breakdowns

## Screens and Actions

### VAT Rates

**Navigate to:** Admin > VAT Rates

The VAT rate list shows all configured rates.

#### Create a VAT Rate

1. Click **Create VAT Rate**
2. Enter:
   - **VAT Rate Name** - Display name (e.g., "Standard Rate")
   - **VAT Rate %** - Percentage (e.g., 20.00)
3. Click **Save**

#### Edit a VAT Rate

1. Click on a VAT rate
2. Click **Edit**
3. Update name or rate
4. Click **Save**

**Note:** Changing a VAT rate affects all products in categories using that rate.

#### Delete a VAT Rate

1. Click on a VAT rate
2. Click **Edit** > **Delete**
3. Confirm deletion

**Warning:** Cannot delete VAT rates in use by categories.

### Product Pricing

**Navigate to:** Product detail > **Cost** tab

View the pricing breakdown:
- Current cost (from active supplier)
- Markup percentage and source
- Price model
- Sell price (exc and inc VAT)

#### Edit Product Pricing

1. Click **Edit Product Cost**
2. Update:
   - **Product Markup %** - Product-specific markup
   - **Price Model** - Rounding method
   - **Active** - Whether product is for sale
3. Click **Save**

### Category Pricing

**Navigate to:** Product detail > **Cost** tab > Click **Edit Category Cost**

Or: Catalog > Categories > Select category > Edit

Update:
- **Category Markup %** - Base markup for all products
- **Price Model** - Rounding method
- **Active** - Whether category is active

### Subcategory Pricing

**Navigate to:** Product detail > **Cost** tab > Click **Edit Subcategory Cost**

Or: Catalog > Subcategories > Select subcategory > Edit

Update:
- **Subcategory Markup %** - Additional markup on top of category
- **Price Model** - Override category's model (or "None" to inherit)
- **Active** - Whether subcategory is active

## Pricing Hierarchy

Prices are calculated using a hierarchy:

```
Supplier Cost (from active supplier product)
        ↓
+ Category Markup %
        ↓
+ Subcategory Markup % (if > 0)
        ↓
+ Product Markup % (if > 0)
        ↓
Apply Price Model (rounding)
        ↓
+ VAT (from category's VAT rate)
        ↓
= Final Sell Price Inc VAT
```

### Markup Calculation

Markups are additive percentages applied to cost:

```
Marked Up Price = Cost × (1 + Markup%)
```

**Example:**
- Cost: £10.00
- Category Markup: 50% → £10 × 1.50 = £15.00
- Subcategory Markup: 10% → £15 × 1.10 = £11.50 (takes precedence over category)
- Product Markup: 0% → £11.50 (no change)

### Price Models

Price models apply rounding for psychological pricing:

| Model | Description | Example         |
|-------|-------------|-----------------|
| **None** | No rounding (inherit from parent) | £11.55 → £11.55 |
| **Default (Cost+)** | Standard calculation | £11.55 → £11.55 |
| **Pretty 00** | Round to .00 | £11.55 → £12.00 |
| **Pretty 10** | Round to .10 | £11.55 → £11.60 |
| **Pretty 49** | Round to .49/.99 | £11.55 → £11.99 |
| **Pretty 95** | Round to .95 | £11.55 → £11.95 |
| **Pretty 99** | Round to .99 | £11.55 → £11.99 |

### VAT Calculation

VAT is added after price model rounding:

```
Sell Price Inc VAT = Sell Price × (1 + VAT Rate%)
```

**Example:**
- Sell Price: £16.99
- VAT Rate: 20%
- Sell Price Inc VAT: £16.99 × 1.20 = £20.39

## Fields and Options

### VAT Rate Fields

| Field | Required | Description |
|-------|----------|-------------|
| VAT Rate Name | Yes | Display name |
| VAT Rate % | Yes | Percentage (0 or higher) |

### Pricing Fields (Product/Category/Subcategory)

| Field | Required | Default | Description |
|-------|----------|---------|-------------|
| Markup % | Yes | Varies | Percentage markup on cost |
| Price Model | Yes | Varies | Rounding method |
| Active | No | Varies | Whether available for sale |

### Default Values

| Level | Default Markup | Default Price Model |
|-------|----------------|---------------------|
| Category | 5.000% | Default (Cost+) |
| Subcategory | 0.000% | None (inherit) |
| Product | 0.000% | None (inherit) |

## Pricing Events

When pricing settings change, the system recalculates affected products:

| Change | Products Affected |
|--------|-------------------|
| VAT Rate changed | All products in categories using that rate |
| Category pricing changed | All products in that category |
| Subcategory pricing changed | All products in that subcategory |
| Product pricing changed | That product only |
| Supplier product cost changed | Products using that supplier |

## Cost Tab Overview

The product Cost tab displays:

**Current Pricing:**
- Cost price (from active supplier)
- Effective markup percentage
- Markup source (product, subcategory, or category)
- Price model in use
- Sell price exc VAT
- Sell price inc VAT

**Edit Links:**
- Edit Product Cost
- Edit Category Cost
- Edit Subcategory Cost

**Category/Subcategory Info:**
- Category name, markup, price model
- Subcategory name, markup, price model (if applicable)
- VAT rate name and percentage

## Warnings

- Changing VAT rates affects all products in related categories
- Category markup changes cascade to all products
- Price model "None" at product level uses subcategory's model; if subcategory is also "None", uses category's model
- Categories must have a price model (cannot be "None")
- Pricing changes take effect immediately
- Historical order prices are not affected by pricing changes
