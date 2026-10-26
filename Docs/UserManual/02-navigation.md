# Navigation

How the screens fit together: the header, the menu and its badges, Help, list pages, forms, inline editing, notifications and the keyboard. Every feature page assumes this one.

**Where:** everywhere. The header bar is at the top of every page.

## The header

| Item | What it does |
|------|--------------|
| Menu button (three lines, left) | Opens the menu. |
| Logo | Goes to the home page. |
| **Help** (a `?` icon on small screens) | Opens help for the current screen. |
| Sun / moon icon | Switches between light and dark mode. Your choice is kept in this browser. |
| Avatar (signed in) | User menu: your name and email, **Settings** (your customer record), **Sign Out**. |
| **Sign In** (signed out) | Goes to the sign-in page ([Getting Started](01-getting-started.md)). |

## The menu

The menu slides in from the left. Click a section to expand or collapse it; click a link to go there and close the menu. Click the X or anywhere outside the menu to close it without moving.

| Section | Links |
|---------|-------|
| **Home** | The home page |
| **Dashboard** | Today's figures and action items ([Reports and Dashboard](13-reports.md)) |
| **Catalog** | Products, Categories, Subcategories, Manufacturers |
| **Orders** | Orders, Pending Orders |
| **Purchasing** | Purchase Orders, Suppliers, Supplier Products |
| **Customers** | The customer list |
| **Reviews** | Review Search, Moderation Queue |
| **Reporting** | Product Sales, Order Summary, Customer Insights, PO Performance, Overdue Orders, Rejected POs |
| **Admin** | VAT Rates |
| **Notes** | My Queue, Tickets, Pools |

**Pending Orders**, **Moderation Queue** and **Rejected POs** are the full lists with a status filter already applied. The funnel icon does not turn yellow for these shortcuts, although the list is filtered.

### Badges

A count appears next to some links when it is above zero.

| Link | Colour | Counts |
|------|--------|--------|
| Pending Orders | Yellow | Orders in Pending status |
| Moderation Queue | Red | Reviews waiting for moderation |
| Overdue Orders | Orange | Orders past their due date |
| Rejected POs | Red | Purchase orders a supplier rejected |
| My Queue | Blue | Open tickets in the pools you subscribe to |

Counts are taken when the whole page loads, so moving between screens does not update them; reload the browser to refresh them. Even then, **Overdue Orders** and **My Queue** can be up to 5 minutes behind.

## Help

Click **Help** or press `?` to open the help panel on the right; press `?` again, click the X or click outside to close it. It shows a short summary of the screen you are on, with links to related topics. The back arrow returns to the topic you came from (one step). A few screens have no topic and show "No help is available for this page." Help works for everyone, signed in or not.

## List pages

Most list pages share one layout: a title with the result count, a search box, a filter icon, a create button, sortable column headings, the results as cards, and page numbers.

- **Search:** type in the box; results update as you type. Empty the box to see everything.
- **Filter:** click the funnel icon at the end of the search box, choose values, click **Apply Filter**. The funnel turns yellow while a filter is active. To remove it, clear the fields and click **Apply Filter** again. Suppliers, Manufacturers, VAT Rates, Customers and Pools have no filter.
- **Sort:** click a column heading; click it again to reverse. A caret marks the sorted column and its direction.
- **Create:** the button is labelled **Create {Entity}**, for example **Create Product**; Supplier Products is the exception, with **Add Supplier Product**. Customers and Purchase Orders have no create button, and **Create Order** shows only for super admins.
- **Open a record:** click its card.

## Detail pages

A detail page shows the record in cards, with related records below (for example, an order's items and purchase orders).

- **Edit:** click the pencil icon on the record's card. The form opens in a dialog.
- **Delete:** where a record can be deleted, a **Delete {Entity}** button sits at the bottom of its edit dialog. It asks you to confirm with **Delete {Entity}** or **Cancel**. Some deletes are super admin only ([Getting Started](01-getting-started.md#roles)); each feature page says what blocks a delete.
- **Status history:** on orders, order items, purchase orders and PO items, click the status badge. The log lists when the record was created and each status it reached, with the time and how long after creation. It does not show who made the change. On live, the history of orders, order items, purchase orders and PO items in a final status (Delivered, Cancelled, Refunded) is deleted nightly once it is more than 12 months old.

### Status badge colours

Each feature page lists its own statuses. The colours follow one scheme:

| Colour | Statuses |
|--------|----------|
| Green | Delivered, Active, Verified, Published |
| Emerald | Accepted; the Returning segment; Staff messages |
| Blue | Shipped, Open; the New segment; Customer messages |
| Yellow | Processing, Replied |
| Orange | Rejected |
| Purple | Refunded; the Loyal segment |
| Red | Cancelled, Inactive, Unverified; the Admin badge; the Lapsed segment |
| Grey | Pending, Hidden, Closed |

## Forms

Create and edit forms open in a dialog over the page. The submit button is **Create {Entity}** or **Update {Entity}**. Required fields are not marked; a missing or invalid value shows its message under the field when you submit. Press Escape, or click outside the dialog, to close it without saving.

## Inline editing

On a detail page, a record's name can be edited in place. This works for product, category, subcategory, manufacturer, supplier and VAT rate names, and a customer's full name. It is not available on list pages.

1. Click the name (a pencil appears on hover).
2. Type the new value.
3. Press Enter, or click elsewhere, to save. Press Escape to cancel. If you changed nothing, the field just closes.

A toast confirms the change only when the value actually changed. An invalid value shows its message beside the field.

## Notifications (toasts)

Results of your actions appear as toasts in the top right. Each has a coloured icon and closes by itself; the X closes it at once.

| Type | Icon colour | Stays for |
|------|-------------|-----------|
| Success | Green | 3.5 seconds |
| Info | Blue | 5 seconds |
| Warning | Orange | 6 seconds |
| Error | Red | 6 seconds |

## Keyboard

| Key | Does |
|-----|------|
| `?` | Opens or closes Help (not while typing in a field) |
| Escape | Closes a dialog, the menu or Help; cancels an inline edit |
| Enter | Saves an inline edit |

## Small screens

The layout drops to one column and cards stack. The menu works the same way; **Help** and **Sign In** become icons in the header.

## Related

- [Getting Started](01-getting-started.md): signing in and roles.
- [Troubleshooting](troubleshooting.md#screens-and-forms): screens and forms.
