# Pricing and VAT

Sell prices are worked out from the active supplier's cost, a markup, the category's VAT rate and a price model that rounds the price. You set markups and price models at three levels: category, subcategory and product.

Press `?` on this screen for a short summary.

**Where:** **Catalog > Products**, then open a product and its **Cost** tab. VAT rates: **Admin > VAT Rates**.

## The Cost tab

- The **inventory card** at the top shows cost, stock, sell price, sell price inc VAT and the effective mark-up (see [Stock](11-stock.md)). Under the mark-up it says which level the price model and the mark-up come from, and the VAT rate name.
- The **Pricing Cascade** below has three cards: **Category**, **Subcategory** and **Product**. Each shows that level's mark-up % and price model. A dash means the level is not set and the next level up applies. A tick marks the level whose mark-up, and the level whose price model, is in use.

## Tasks

### Change a markup or price model

1. On the Cost tab, click the pencil icon on the **Category**, **Subcategory** or **Product** card.
2. Change **Category / Subcategory / Product Markup %**, **Price Model** or **Active**.
3. Click **Update Category Cost** (or **Update Subcategory Cost**, **Update Product Cost**).

The category and subcategory cards edit the whole category or subcategory, so every product in it is affected. The same fields are in the edit form under **Catalog > Categories** and **Catalog > Subcategories** ([Categories and Subcategories](04-categories.md)). A product's markup and price model can be edited only here.

### Manage VAT rates

1. Go to **Admin > VAT Rates** and click **Create VAT Rate**.
2. Enter **VAT Rate Name** and **VAT Rate %** (0 or more).
3. Click **Create VAT Rate**.

To edit, click the pencil icon on the row (or on the detail page) and click **Update VAT Rate**. On the detail page you can also click the name to rename it in place, and follow the link to the categories using the rate. A category's VAT rate is chosen in the category form.

To delete, open the edit modal and click **Delete VAT Rate**. Super admin only; other users see "Deleting is disabled for this user." A rate still used by a category can't be deleted: the delete fails with an error.

## How a price is worked out

1. **Markup.** The product's markup if it is above 0, otherwise the subcategory's if above 0, otherwise the category's. The first level that is set **replaces** the others. Markups are never added together.
2. **Price model.** The product's if it is not None, otherwise the subcategory's if not None, otherwise the category's.
3. Sell price ex VAT = cost × (1 + markup %).
4. Add VAT at the category's rate.
5. The price model rounds the **price inc VAT**.
6. The sell price ex VAT and the mark-up are then worked back from the rounded price. So the mark-up shown on the inventory card is what the rounded price earns, not the markup you set.

**Example:** cost £10.00, category markup 50%, subcategory markup 10%, product markup 0, VAT 20%, category price model Pretty 99.

- Subcategory markup 10% applies (the product's is 0). £10.00 × 1.10 = £11.00.
- With VAT: £11.00 × 1.20 = £13.20. Pretty 99 gives **£13.99** inc VAT.
- Worked back: sell price £11.66 ex VAT, mark-up 16.600%.

## Price models

Rounding is applied to the price inc VAT and never goes down.

| Model | Rule | £13.20 → | £13.96 → |
|---|---|---|---|
| None | No rounding. On a product or subcategory it means "use the level above" | £13.20 | £13.96 |
| Default (Cost+) | No rounding | £13.20 | £13.96 |
| Pretty 00 | Up to the next whole pound | £14.00 | £14.00 |
| Pretty 10 | Up to the next 10p | £13.20 | £14.00 |
| Pretty 49 | .49 below 50p, otherwise .99 | £13.49 | £13.99 |
| Pretty 95 | .95, or the next pound's .95 above .95 | £13.95 | £14.95 |
| Pretty 99 | Always .99 | £13.99 | £13.99 |

## Rules and limits

| Level | Default markup | Default price model |
|---|---|---|
| Category | 5.000% | Default (Cost+) |
| Subcategory | 0.000% (not set) | None (use category) |
| Product | 0.000% (not set) | None (use subcategory or category) |

- A category must have a price model: None gives "A category must have a price model".
- Markups can't be negative ("Please enter a positive or zero … markup %").
- Keep the category markup above 0. With an effective markup of 0 and no rounding, the price can't be worked out and saving the product, category or VAT rate fails with an error.
- Changes apply straight away to the affected products: editing a VAT rate to every product in the categories using it; choosing another VAT rate for a category to its active products; a category or subcategory markup or price model change to its active products that take their markup or price model from that level; a product change to that product; a supplier product edit that changes a value to the product it is mapped to.
- Simulated stock and cost changes do not reprice products ([Stock](11-stock.md#rules-and-limits)).
- Prices on existing orders never change. An order item keeps the price it was added at.
- The **Active** box on the cost forms turns the category, subcategory or product on or off. A product is complete only when all three are active and it has an active supplier product ([Products](03-products.md#rules-and-limits)).

The technical rules are in [Sourcing and Pricing](../04-sourcing-and-pricing.md).

## Related

- [Categories and Subcategories](04-categories.md): categories, subcategories and their VAT rate
- [Products](03-products.md)
- [Stock](11-stock.md): where the cost comes from
