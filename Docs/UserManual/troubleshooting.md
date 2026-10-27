# Troubleshooting

What a problem usually means and how to fix it, by area. Each row links to the page that explains the rule. Quoted text is the message you see on screen.

Press `?` on any screen for a short summary of that screen.

## Signing in

| Symptom | Cause | Fix | See |
|---------|-------|-----|-----|
| "Invalid credentials." | Wrong email or password. | Check the email and Caps Lock, or reset the password. | [Getting Started](01-getting-started.md#reset-a-forgotten-password) |
| "Too many failed login attempts, please try again in N minutes." | 5 failed attempts for this email in 15 minutes. | Wait the minutes shown, or reset the password. | [Getting Started](01-getting-started.md#rules-and-limits) |
| A "Too Many Requests" page (429) | More than 10 requests a minute to the sign-in page from your connection (page loads and sign-in attempts both count), or more than 3 quick sign-up or password reset submissions (then 1 every 30 seconds). | Wait a minute and try again. | [Getting Started](01-getting-started.md#rules-and-limits) |
| No reset email arrives | No account has that email, or a reset email was already sent in the last hour (one per hour; the page looks the same either way). | Check spam, use the earlier email, or wait an hour. | [Getting Started](01-getting-started.md#reset-a-forgotten-password) |
| "This reset link is invalid or has expired." | Reset links last 1 hour and work once. | Request a new link. | [Getting Started](01-getting-started.md#reset-a-forgotten-password) |
| "Unauthorized Access to Mars Colony Systems" after signing in | Your account has no staff access. | Ask a staff member to tick **Member of Staff** on your customer record. | [Customers](07-customers.md) |
| "Too many attempts. Please try again later." when registering | 5 submitted sign-ups from your connection in the last hour, failed ones included. | Try again later. | [Getting Started](01-getting-started.md#register-an-account) |
| Signed out unexpectedly | 7 days passed without a visit, or someone used **Sign Out** in this browser. | Sign in again. | [Getting Started](01-getting-started.md#rules-and-limits) |

Email verification never blocks sign-in, so an unverified account is not the cause of a sign-in problem.

## Screens and forms

| Symptom | Cause | Fix | See |
|---------|-------|-----|-----|
| A menu badge count is out of date | Badges are counted when the whole page loads, not when you move between screens; **Overdue Orders** and **My Queue** can lag 5 more minutes. | Reload the browser. | [Navigation](02-navigation.md#badges) |
| The form does not close when you submit | A field has an error. Required fields are not marked. | Read the message under each field, correct it, submit again. | [Navigation](02-navigation.md#forms) |
| No create button on a list | Customers and Purchase Orders have none; **Create Order** is super admin only. | Customers come from registration and the simulator; POs from allocation. | [Navigation](02-navigation.md#list-pages) |
| No filter icon on a list | Suppliers, Manufacturers, VAT Rates, Customers and Pools have no filter. | Use the search box. | [Navigation](02-navigation.md#list-pages) |
| A list shows fewer results than expected | A filter is still applied: the funnel is yellow, or you came from the **Pending Orders**, **Moderation Queue** or **Rejected POs** menu link (these filter without turning the funnel yellow). | Open the filter, clear the fields, click **Apply Filter**; or open the full list from the menu. | [Navigation](02-navigation.md#list-pages) |
| Help says "No help is available for this page." | That screen has no help topic. | Use this manual. | [Navigation](02-navigation.md#help) |
| "Deleting is disabled for this user." | Deleting products, suppliers, supplier products, VAT rates and pools is super admin only. | Ask a super admin, or deactivate the record instead. | [Getting Started](01-getting-started.md#roles) |
| A delete ends on "System Malfunction in the Mars Colony" | The record is still in use: a product with images, supplier products or order items; a supplier with supplier products or purchase orders; a VAT rate in use; a pool with tickets. Nothing was deleted. | Deactivate it instead, or remove what uses it first. | The record's page |

## Catalog

| Symptom | Cause | Fix | See |
|---------|-------|-----|-----|
| **Subcategory** list is empty on the product form | It lists only the chosen category's subcategories. | Choose the **Category** first; add a subcategory to it if it has none. | [Products](03-products.md) |
| The Stock or Cost tab shows "Incomplete Product" | The product is not complete: it must be Active, have an active supplier product, and sit in an Active category and subcategory. Staff can still add it to an order; demo and simulated orders skip it unless it is active, in stock and has an active supplier product. | Read the line under the heading ("No active supplier" or "Inactive category, subcategory or product") and fix that. | [Products](03-products.md#rules-and-limits), [Stock](11-stock.md) |
| "Has subcategories — remove them first." | A category with subcategories can't be deleted. | Delete or move its subcategories. | [Categories and Subcategories](04-categories.md) |
| "Has products or supplier mappings — move or remove them first." | A subcategory with products, or mapped from a supplier subcategory, can't be deleted. | Move its products to another subcategory. | [Categories and Subcategories](04-categories.md) |
| "Has products or supplier mappings — reassign or remove them first." | A manufacturer with products, or mapped from a supplier manufacturer, can't be deleted. | Give the products another manufacturer. | [Manufacturers](16-manufacturers.md) |
| "Has images, supplier products or orders — deactivate it instead." | A product in use can't be deleted. | Deactivate the product. | [Products](03-products.md) |
| "Used by categories — move them to another rate first." | A VAT rate a category uses can't be deleted. | Give those categories another rate. | [Pricing](12-pricing.md) |

## Suppliers and stock

| Symptom | Cause | Fix | See |
|---------|-------|-----|-----|
| A supplier product is never the active source | It, or its supplier, is inactive; it has no stock; its cost is 0; or another source is cheaper (on equal cost, the one with more stock wins). | Activate it, or correct stock and cost. | [Stock](11-stock.md) |
| Product stock or price doesn't match its supplier product | Simulated stock and cost changes don't update the product. Saving a supplier product with no changed value doesn't either. | On the product's Stock tab, click a source's toggle off and on again; or change a value on the supplier product and save. | [Stock](11-stock.md), [Simulations](14-simulations.md) |
| "Used on purchase orders — deactivate it instead." | A supplier product on a purchase order can't be deleted. | Deactivate it on the product's Stock tab. | [Supplier Products](06-supplier-products.md) |
| "Has products, mappings or purchase orders — deactivate it instead." | A supplier in use can't be deleted. | Deactivate the supplier. | [Suppliers](05-suppliers.md) |
| A supplier product has no Stock tab entry | It isn't mapped to a catalog product. | Use **Map Product** on the supplier product. | [Supplier Products](06-supplier-products.md) |

## Orders

| Symptom | Cause | Fix | See |
|---------|-------|-----|-----|
| No **Create Order** button | Creating orders by hand is super admin only. | Ask a super admin, or use **Create Demo Order**. | [Orders](08-orders.md) |
| "Customer has no default shipping or billing address." | The customer has no default shipping or billing address (newly registered customers have none). | Choose a customer with addresses. | [Orders](08-orders.md), [Customers](07-customers.md) |
| The demo dialog says "You have reached the daily limit of 10 demo orders." | 10 demo orders a day in total, for everyone. | Try again tomorrow. | [Orders](08-orders.md#create-a-demo-order) |
| "Cannot allocate all items" | Automatic allocation needs one supplier that can supply a line's whole outstanding quantity; lines without one are skipped. | Choose suppliers for those lines by hand from the item's supplier options. | [Order Items](09-order-items.md), [Stock](11-stock.md) |
| "No items to allocate." | Every line is already on a purchase order and every purchase order item has been sent to its supplier. | Nothing to do. | [Orders](08-orders.md#allocate-an-order) |
| "Order cannot be cancelled" | Only Pending orders can be cancelled. The X icon also shows on Processing orders, but the cancel always fails there. | Work on the individual items instead. | [Orders](08-orders.md), [Order Items](09-order-items.md) |
| "Items cannot be cancelled" | At least one item has gone too far to cancel. | Check each item's status. | [Order Items](09-order-items.md) |
| "minimum quantity is N" | An item's quantity can't go below what is already on purchase orders (N), so it can't be set to 0 either. | Reduce the PO items first. | [Order Items](09-order-items.md#rules-and-limits) |
| Someone else edited a locked order | The lock is a "someone is working on this" note; it blocks no one, and anyone can unlock it. Only the scheduled jobs skip locked orders. | Agree who is working on the order. | [Orders](08-orders.md#rules-and-limits) |
| A locked order never moves on its own | Allocation, supplier acceptance, shipping and delivery jobs skip locked orders. | Unlock it. | [Orders](08-orders.md#rules-and-limits) |

## Purchase orders

| Symptom | Cause | Fix | See |
|---------|-------|-----|-----|
| "Purchase order item cannot be edited" | Quantities can change only while the PO item is Pending. | Rewind the PO to Pending with the arrow icon on its card. | [Purchase Orders](10-purchase-orders.md) |
| "Quantity cannot be greater than N" | A PO item can't exceed what the order item still needs. | Enter N or less. | [Purchase Orders](10-purchase-orders.md) |
| "Status can not be set to X." | The status list shows every status, but only some moves are allowed; Refunded, Delivered and Cancelled are final. | Choose the next valid status. | [Purchase Orders](10-purchase-orders.md) |

## Pricing

| Symptom | Cause | Fix | See |
|---------|-------|-----|-----|
| A markup change has no effect | The product uses the first markup above zero: product, then subcategory, then category. A lower level replaces a higher one; they never add up. | Change the level that is in use, or set the lower levels to 0. | [Pricing and VAT](12-pricing.md) |
| The sell price is not cost plus markup plus VAT | The price model rounds the price including VAT. | Check the price model in use. | [Pricing and VAT](12-pricing.md) |

## Reports and dashboard

| Symptom | Cause | Fix | See |
|---------|-------|-----|-----|
| Today's figures lag recent orders | Today's sales figures are recalculated every 30 minutes; earlier days are finalised just after midnight. | Check again after the next half hour. | [Reports and Dashboard](13-reports.md) |
| A report is empty | No sales in the chosen period. | Choose a longer period. | [Reports and Dashboard](13-reports.md) |

## Customers

| Symptom | Cause | Fix | See |
|---------|-------|-----|-----|
| No edit button on a customer, and the name can't be clicked | The customer is staff. Editing a staff account, your own included, is super admin only, in every environment. | Ask a super admin. | [Customers](07-customers.md) |
| The delete dialog says "You cannot delete an admin user." or "You cannot delete a customer with order history." and offers only **Close** | Staff accounts and customers with orders can't be deleted. | Leave the account in place. | [Customers](07-customers.md) |

## Notes and tickets

| Symptom | Cause | Fix | See |
|---------|-------|-----|-----|
| No reply box on a ticket | The ticket is Closed. | Reopen it with the reopen icon on the ticket card. | [Notes and Tickets](17-notes.md) |
| My Queue is empty | It shows only tickets that are not closed or snoozed, in pools you subscribe to. | Subscribe on the pool's page (**Notes > Pools**). | [Notes and Tickets](17-notes.md) |
