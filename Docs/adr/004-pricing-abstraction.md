# ADR 004: Hierarchical Pricing with Pretty-Price Rounding

## Status

Accepted

## Context

Product pricing in e-commerce involves multiple factors:

- **Base cost** from suppliers
- **Markup percentage** for profit margin
- **VAT/tax rates** that vary by product category
- **Price presentation** (customers respond better to £9.99 than £10.00)

We needed a pricing model that:
- Allows bulk management (category-level pricing)
- Supports exceptions (product-level overrides)
- Handles VAT consistently
- Produces customer-friendly prices

## Decision

We implemented a **three-level pricing hierarchy** with **price model rounding**:

### Markup Hierarchy

```
Product.defaultMarkup (if > 0)
    ↓ (inherits if 0)
Subcategory.defaultMarkup (if > 0)
    ↓ (inherits if 0)
Category.defaultMarkup (always set)
```

### Price Model Hierarchy

Same pattern applies to price models (pretty rounding):
- Product can override
- Subcategory can override
- Category is the fallback

### Price Models

| Model | Effect | Example |
|-------|--------|---------|
| NONE | No model (for inheritance) | - |
| DEFAULT | Cost+ (no rounding) | 10.73 → 10.73 |
| PRETTY_00 | Round to .00 | 10.73 → 11.00 |
| PRETTY_99 | Round to .99 | 10.73 → 10.99 |
| PRETTY_49 | Round to .49/.99 | 10.73 → 10.99 |
| PRETTY_95 | Round to .95 | 10.73 → 10.95 |
| PRETTY_10 | Round to .10 | 10.73 → 10.80 |

### Calculation Flow

```
Final Price = PriceModel.getPrettyPrice(
    cost × (1 + markup/100) × (1 + vatRate/100)
)
```

All calculations use `bcmath` for precision.

### Event-Driven Cascade

When pricing inputs change, events trigger selective recalculation:

```
CategoryPricingWasChangedEvent
    → Recalculate products where markup source = CATEGORY
    → Recalculate products where price model source = CATEGORY
    → Always recalculate if VAT rate changed

SubcategoryPricingWasChangedEvent
    → Only recalculate affected products (source = SUBCATEGORY or CATEGORY)
```

## Consequences

### Positive

- **Bulk management**: Change category markup → all products update
- **Granular control**: Override specific products when needed
- **Marketing-friendly**: Price models produce attractive prices
- **Audit trail**: Events show what triggered recalculation

### Negative

- **Derived complexity**: `getActiveMarkup()` requires understanding inheritance
- **Cascade storms**: Category changes may update thousands of products
- **Rounding loss**: Pretty prices mean actual margin varies from target

### Implementation Notes

Key files:
- `src/Shared/Domain/Service/Pricing/MarkupCalculator.php` - All price math
- `src/Shared/Domain/ValueObject/PriceModel.php` - Rounding logic
- `src/Pricing/Application/Listener/CategoryPricingWasChanged.php` - Smart cascade

The cascade is "smart" because it checks `getActiveMarkupTarget()` to avoid recalculating products that have their own overrides. This significantly reduces unnecessary computation.

Example: If a product has its own `defaultMarkup = 30%`, changing the category markup from 20% to 25% won't affect that product (its source is PRODUCT, not CATEGORY).
