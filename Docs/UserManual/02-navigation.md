# Navigation

## The Main Menu

### Opening the Menu

Click the **menu button** (☰) in the top-left corner of the header to open the navigation menu. The menu slides in from the left side of the screen.

### Closing the Menu

- Click the **X** button in the top-right of the menu
- Click on any menu item (the menu closes automatically)
- Click anywhere outside the menu

### Menu Structure

The menu is organised into sections:

```
Home
├── Products
│   ├── Search (all products)
│   ├── Categories
│   ├── Subcategories
│   └── Manufacturers
├── Orders
├── Purchase Orders
├── Customers
├── Suppliers
│   ├── Search (all suppliers)
│   └── Supplier Products
├── Dashboard
└── Admin
    └── VAT Rates
```

### Expandable Sections

Some menu items have a **▶** arrow indicating they expand to show sub-items:

1. Click on **Products** to expand and see:
   - Search
   - Categories
   - Subcategories
   - Manufacturers

2. Click on **Suppliers** to expand and see:
   - Search
   - Supplier Products

3. Click on **Admin** to expand and see:
   - VAT Rates

Click the section again to collapse it.

## Page Types

### List Pages (Index)

List pages show multiple records in a searchable, sortable format:

**Features:**
- **Search box** - Type to filter results
- **Sort columns** - Click column headers to change sort order
- **Pagination** - Navigate through pages of results
- **Filter button** - Access advanced filtering options
- **Add button** - Create a new record

**Example: Product List**
- Shows product name, stock, price, and status
- Click a product card to view details
- Click the pencil icon to edit

### Detail Pages (Show)

Detail pages display complete information about a single record:

**Features:**
- Full record details displayed in cards
- **Edit button** - Modify the record
- Related records shown below (e.g., order items on an order)
- Status indicators and timestamps

### Form Modals

When you create or edit records, forms appear in a modal (popup) over the current page:

**Using Form Modals:**
1. Click **Add** or **Edit** to open the form
2. Fill in the required fields (marked with *)
3. Click **Save** to submit
4. The modal closes and you see a success message

**Cancelling:**
- Click outside the modal
- Click the X button (if shown)
- Press Escape on your keyboard

## Common UI Elements

### Cards

Information is displayed in cards with:
- Optional title at the top
- Content in the body
- Optional edit button (pencil icon) in the corner

Click a card with a link icon to navigate to the detail page.

### Buttons

| Button Style | Use |
|--------------|-----|
| Blue (Primary) | Main actions like Save, Create |
| Grey (Secondary) | Alternative actions |
| Red (Danger) | Destructive actions like Delete |
| White (Alternative) | Cancel, Back |

### Status Badges

Records show their status as coloured text:
- **Green** - Active, Delivered, Accepted
- **Red** - Inactive, Cancelled, Rejected
- **Yellow/Orange** - Pending, Processing

### Notifications (Toasts)

After actions, notifications appear briefly:
- **Green** - Success message
- **Red** - Error message
- **Yellow** - Warning message

Notifications disappear automatically after 2 seconds, or click the X to dismiss them immediately.

## Searching and Filtering

### Quick Search

Most list pages have a search box at the top:
1. Type your search term
2. Results filter automatically as you type
3. Clear the search box to show all results

### Advanced Filters

Click the **Filter** button (funnel icon) to access advanced filtering:

1. A filter form opens
2. Select filter criteria (varies by page)
3. Click **Search** to apply filters
4. The filter button highlights yellow when filters are active

**To clear filters:**
1. Click the Filter button
2. Clear or reset the filter fields
3. Click Search

### Sorting

Click on column headers to sort:
- First click: Sort ascending (A-Z, 0-9)
- Second click: Sort descending (Z-A, 9-0)

The current sort column is indicated in the header.

### Pagination

For large result sets:
- Page numbers appear at the bottom
- Click a number to jump to that page
- Use Previous/Next links to move one page at a time

## Keyboard Shortcuts

While SupplyMars is primarily mouse-driven, some keyboard shortcuts work:

| Key | Action |
|-----|--------|
| Escape | Close modal/form |
| Enter | Submit form (when in a field) |
| Tab | Move between form fields |

## Mobile Navigation

On smaller screens:
- The menu is hidden by default
- Tap the menu button to open it
- The layout adjusts to a single column
- Cards and tables reflow for smaller displays

## Navigating with Turbo

SupplyMars uses Turbo for fast navigation:
- Clicking links updates the page content without full reloads
- Forms submit and update in place
- Back/forward browser buttons work as expected

If a page seems stuck:
1. Wait a moment - the system may be loading
2. Refresh the page if needed
3. Check your internet connection
