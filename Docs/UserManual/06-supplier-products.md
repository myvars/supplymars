# Supplier Products

A supplier product is one supplier's offer of an item: its cost, stock and lead time. One catalog product can have several, one per supplier.

Press `?` on this screen for a short summary.

**Where:** **Purchasing > Supplier Products**

## Tasks

### Find a supplier product

1. Open **Purchasing > Supplier Products**. Each card shows the name, supplier colour dot, supplier and product code, cost, stock ("N in stock", including "0 in stock") and Active/Inactive badge.
2. Sort by **Id**, **Product**, **Supplier**, **Stock**, **Cost** or **Status**.
3. To filter, click the funnel icon, set any of **Supplier**, the category, subcategory and manufacturer fields, **Product Code**, **Stock Level** or **Status**, then click **Apply Filter**. All the fields always show, but the category list (**Any Category**) is empty until you pick a Supplier, and the subcategory list (**Any Subcategory**) until you pick a category. The manufacturer list (**Any Manufacturer**) shows every supplier manufacturer until you narrow it with a supplier, category or subcategory. These are the supplier's own names, not the catalog's.

### Create a supplier product

1. Click **Add Supplier Product**.
2. Fill in **Product Name**, **Product Code**, **Supplier**, **Category**, **Subcategory**, **Manufacturer**, **Manufacturer Part Number**, **Cost**, **Stock Level**, **Lead Time (days)**, **Weight (grams)**, **Mapped Product Id** (optional) and **Active**. Choosing a Category reloads the form to fill Subcategory.
3. Click **Create Supplier Product**.

**Mapped Product Id** is the catalog product's number (the `#000123` on its card, without the zeros). Leave it empty to map later.

### Edit a supplier product

1. Click the pencil icon on the card.
2. Change the fields and click **Update Supplier Product**.

Changing **Mapped Product Id** moves the supplier product to another catalog product; clearing it unmaps it. Both products pick their active source again.

### Map to a catalog product

1. Open an unmapped supplier product.
2. Click **Map Product** on its card.

The app looks for a catalog manufacturer, category, subcategory and product with the same names as the supplier's, creates any that don't exist, and links the supplier product to the product. Anything it creates is Active; new categories get the default VAT rate and you as manager.

### Remove from a catalog product

1. Open the catalog product's **Stock** tab.
2. Click the Remove (trash) icon on the supplier's row, then **Remove**.

The supplier product is unlinked, not deleted, and the product's source and price are recalculated. The Stock tab also has the toggle that switches a supplier product Active or Inactive; see [Stock](11-stock.md).

### Delete a supplier product (super admin only)

1. Click the pencil icon to open the edit modal.
2. Click **Delete Supplier Product**, then confirm.

If it is mapped, it is unlinked first: the product moves to its next best source and is repriced. With no source left, the product's stock drops to 0.

## Rules and limits

| Action | Blocked when | Message |
|---|---|---|
| Delete | You are not a super admin | "Deleting is disabled for this user." |
| Delete | It appears on any purchase order | "Used on purchase orders — deactivate it instead." |
| Map Product | It is already mapped | "Supplier product already mapped." |

- **Ranges:** Cost 0 or more; Stock Level 0–10,000; Lead Time 0–1,000 days; Weight 0–100,000 grams. Names, codes and part numbers take up to 255 characters.
- **Which supplier product becomes the source** (a cost of 0 never does): see [Stock](11-stock.md#how-the-active-source-is-chosen). You can't choose it by hand.
- **Editing a mapped supplier product** works out the product's active source, cost, stock and price again, but only when you changed the supplier, stock, lead time, cost, mapped product or Active. Saving with none of those changed recalculates nothing ([Stock](11-stock.md)).
- **Out of stock:** only the detail card shows "Out of stock (Y day lead time)"; the list card shows "0 in stock".
- New supplier products are Inactive unless you tick Active.

## Related

- [Suppliers](05-suppliers.md)
- [Products](03-products.md)
- [Stock](11-stock.md)
- [Pricing and VAT](12-pricing.md)
