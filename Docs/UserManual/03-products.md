# Products

Products are the items in your catalog that customers can order.

Press `?` on this screen for a short summary.

**Where:** **Catalog > Products**

## Tasks

### Find a product

1. Open **Catalog > Products**. Each card shows the image, name, Active/Inactive badge, product number (`#000123`), stock ("N in stock" or "Out of stock") and the price inc VAT.
2. Type in the search box, or click a column to sort: **Id**, **Product**, **Stock**, **Price**, **Status**.
3. To filter, click the funnel icon, set **Category**, **Subcategory**, **Manufacturer**, **Manufacturer Part Number** or **Stock Level** (Any, In Stock, Out of Stock), and click **Apply Filter**. Choosing a Category reloads the form so that Subcategory lists only that category's subcategories.

### Create a product

1. Click **Create Product**.
2. Fill in **Product Name**, **Product Description** (optional), **Category**, **Subcategory**, **Manufacturer**, **Manufacturer Part Number**, **Product Manager** (optional, staff only) and **Active**.
3. Click **Create Product**.

Active is unticked by default, so a new product is Inactive until you tick it.

### Edit a product

1. Click the pencil icon on the product's card (on the list or the detail page).
2. Change the fields and click **Update Product**.

The name can also be changed in place on the detail page ([inline editing](02-navigation.md#inline-editing)).

### Delete a product (super admin only)

1. Click the pencil icon to open the edit modal.
2. Click **Delete Product**, then **Delete Product** again to confirm.

Deleting is permanent.

### Work with the detail page

Click a product to open it. Its tabs are Details, Stock, Cost, Images, Sales, History and Reviews; the in-app help describes each. Where to read more:

- **Stock:** the supplier sources and the active source, see [Stock](11-stock.md).
- **Cost:** markup and price model, see [Pricing and VAT](12-pricing.md).
- **History:** opens on 30 days.
- **Reviews:** see [Reviews](15-reviews.md).

### Manage images

1. Open the **Images** tab.
2. Drag files onto the upload area, or click it to browse.
3. Drag images to reorder them. The first image gets a "Primary" badge and is the thumbnail on lists and cards.
4. To delete one, hover over it and click its delete button.

## Rules and limits

- **Who can delete:** super admin only. Other staff see "Deleting is disabled for this user."
- **Delete fails** while the product has images, supplier products or order items. Nothing checks first; the delete ends in an error.
- **Complete product:** the Stock and Cost tabs show figures, instead of "Incomplete Product" with "No active supplier" or "Inactive category, subcategory or product", only when the product is Active, it has an active supplier product, and its category and subcategory are both Active.
- **Incomplete products can still be ordered.** Nothing stops staff adding an incomplete product to an order. Demo and simulated orders pick only products that are active, in stock and with an active supplier product.
- **Images:** at most 2 MB each; JPG, PNG, GIF or WebP; up to 10 files per upload. There is no limit per product.
- **Uploads go two at a time.** If one file of a pair is rejected, the other is not uploaded either, and the message names the rejected file. Upload it again on its own.
- **Price:** the markup and price model come from the product, its subcategory or its category; see [Pricing and VAT](12-pricing.md).
- **Cost and stock** come from the active supplier product. You can't pick it by hand; see [Stock](11-stock.md).
- **Field limits:** Product Name, Product Description and Manufacturer Part Number take up to 255 characters.

## Related

- [Categories and Subcategories](04-categories.md)
- [Manufacturers](16-manufacturers.md)
- [Supplier Products](06-supplier-products.md)
- [Stock](11-stock.md)
- [Pricing and VAT](12-pricing.md)
- [Reviews](15-reviews.md)
