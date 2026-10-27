# Suppliers

Suppliers are the companies whose products fulfil customer orders, through purchase orders.

Press `?` on this screen for a short summary.

**Where:** **Purchasing > Suppliers**

## Tasks

### Find a supplier

1. Open **Purchasing > Suppliers**. Each card shows the supplier's colour dot, name and Active/Inactive badge.
2. Search by name, or sort by **Id**, **Supplier** or **Status**. This list has no filter.

### Create a supplier

1. Click **Create Supplier**.
2. Fill in **Supplier Name**, **Colour Scheme** (Violet, Amber, Teal or Rose; default Violet) and **Active**.
3. Click **Create Supplier**.

### View the detail page

Click a supplier. The card shows its colour, status and a link to its supplier products. Below it is a sales report for the supplier's products.

### Edit a supplier

1. Click the pencil icon on the card.
2. Change the fields and click **Update Supplier**.

The name can also be changed in place on the detail page ([inline editing](02-navigation.md#inline-editing)).

### Delete a supplier (super admin only)

1. Click the pencil icon to open the edit modal.
2. Click **Delete Supplier**, then confirm.

## Rules and limits

- **Who can delete:** super admin only. Other staff see "Deleting is disabled for this user."
- **A supplier in use can't be deleted.** While it has supplier products, purchase orders or category, subcategory or manufacturer mappings: "Has products, mappings or purchase orders — deactivate it instead."
- **Inactive supplier:** none of its supplier products is used as a product's source, so its products move to another supplier, or have no source. See [Stock](11-stock.md) for how the source is chosen.
- **An inactive supplier's existing purchase orders carry on:** they can still be accepted, shipped and delivered.
- **Colour scheme** is the dot shown wherever the supplier appears: purchase order cards, supplier options on order items, and the product's Stock tab.
- **Warehouse:** one supplier can be flagged as your own warehouse; no screen shows or changes this, and sourcing gives it no preference.
- Supplier Name takes up to 255 characters. New suppliers are Inactive unless you tick Active.

## Related

- [Supplier Products](06-supplier-products.md)
- [Purchase Orders](10-purchase-orders.md)
- [Stock](11-stock.md)
