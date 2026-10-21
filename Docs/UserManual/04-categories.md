# Categories and Subcategories

Categories and subcategories group the catalog in two levels and set each product's VAT rate, markup and price model.

Press `?` on this screen for a short summary.

**Where:** **Catalog > Categories**, and **Catalog > Subcategories**

## Tasks

### Find a category

1. Open **Catalog > Categories**. Each card shows the name, Active/Inactive badge, manager ("Managed by …") and markup.
2. Sort by **Id**, **Category**, **Markup** or **Status**.
3. To filter, click the funnel icon, set **Vat Rate**, **Price Model** or **Category Manager**, and click **Apply Filter**.

### Create a category

1. Click **Create Category**.
2. Fill in **Category Name**, **VAT Rate**, **Category Markup %**, **Price Model**, **Category Manager** and **Active**.
3. Click **Create Category**.

Defaults: markup 5.000%, price model Default (Cost+), Inactive.

### Find a subcategory

1. Open **Catalog > Subcategories**. Each card shows the name, parent category, manager and markup.
2. Sort by **Id**, **Category**, **Subcategory**, **Markup** or **Status**.
3. To filter, click the funnel icon, set **Category**, **Price Model** or **Subcategory Manager**, and click **Apply Filter**.

### Create a subcategory

1. Click **Create Subcategory**.
2. Fill in **Category**, **Subcategory Name**, **Subcategory Markup %**, **Price Model**, **Subcategory Manager** (optional) and **Active**.
3. Click **Create Subcategory**.

Defaults: markup 0.000%, price model None, Inactive.

### View the detail page

Click a category or subcategory. The card shows markup, price model, VAT rate (categories), manager and a link to its products. A category page also lists its subcategories. Both pages show a sales report for their products.

### Edit

1. Click the pencil icon on the card.
2. Change the fields and click **Update Category** or **Update Subcategory**.

The name can also be changed in place on the detail page ([inline editing](02-navigation.md#inline-editing)).

### Delete

1. Click the pencil icon to open the edit modal.
2. Click **Delete Category** or **Delete Subcategory**, then confirm.

## Rules and limits

| Action | Blocked when | Message |
|---|---|---|
| Delete a category | It still has subcategories | "Has subcategories — remove them first." |
| Delete a subcategory | It still has products | "Has products — move or remove them first." |
| Save a category | Price Model is None | "A category must have a price model" |
| Save a category | No Category Manager chosen | "Please choose a category owner" |

- **Markup and price model** cascade from product to subcategory to category, and a lower level replaces a higher one; the rules are in [Pricing and VAT](12-pricing.md).
- **Inactive** category or subcategory: its products are incomplete (see [Products](03-products.md#rules-and-limits)).
- **Changing VAT rate, markup or price model** reprices products straight away: a VAT rate change every active product in the category; a markup or price model change only the active products that take their markup or price model from this level ([Pricing and VAT](12-pricing.md#rules-and-limits)).
- Names take up to 255 characters; markups must be zero or more.

## Related

- [Products](03-products.md)
- [Pricing and VAT](12-pricing.md)
- [Reports and Dashboard](13-reports.md)
