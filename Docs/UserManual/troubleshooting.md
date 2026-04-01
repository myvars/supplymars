# Troubleshooting

This guide covers common problems and their solutions.

## Login Issues

### Cannot Log In

**Symptom:** Login fails with "Invalid credentials" error.

**Solutions:**
1. Check that you're using the correct email address
2. Ensure Caps Lock is not on
3. Try the "Forgot your password?" link to reset
4. Contact your administrator if problems persist

### Account Not Verified

**Symptom:** Login fails with "Account not verified" error.

**Solutions:**
1. Check your email for a verification link
2. Click "Resend verification email" on the login page
3. Check your spam/junk folder
4. Contact your administrator to manually verify your account

### Forgot Password Email Not Received

**Symptom:** No email arrives after requesting password reset.

**Solutions:**
1. Wait a few minutes; emails can be delayed
2. Check spam/junk folder
3. Verify you're using the correct email address
4. Contact your administrator

## Navigation Issues

### Page Not Loading

**Symptom:** Page shows spinner but never loads.

**Solutions:**
1. Wait a moment; the system may be processing
2. Click the browser refresh button
3. Check your internet connection
4. Clear browser cache and try again
5. Try a different browser

### Menu Not Opening

**Symptom:** Clicking the menu button does nothing.

**Solutions:**
1. Refresh the page
2. Clear browser cache
3. Disable browser extensions that might interfere
4. Try a different browser

### Back Button Not Working

**Symptom:** Browser back button doesn't navigate as expected.

**Solutions:**
1. This can happen with Turbo navigation; refresh and try again
2. Use the menu to navigate instead
3. Clear browser cache

## Product Issues

### Cannot Create Product

**Symptom:** Product creation fails or form won't submit.

**Solutions:**
1. Ensure all required fields are filled (marked with *)
2. Select a category before selecting a subcategory
3. Check that manufacturer is selected
4. Verify manufacturer part number is entered

### Subcategory Dropdown Empty

**Symptom:** No subcategories appear in the dropdown.

**Solutions:**
1. Select a category first; subcategories depend on category selection
2. Ensure the selected category has subcategories
3. Refresh the page and try again

### Product Not Appearing in Lists

**Symptom:** Created product doesn't show in search results.

**Solutions:**
1. Check if product is set to Active
2. Check your search filters; clear all filters
3. Refresh the page
4. Wait a moment; indexing may be delayed

### Product Cannot Be Sold

**Symptom:** Product shows as unavailable despite being active.

**Solutions:**
1. Ensure the product has a linked supplier product
2. Check that the supplier product is active
3. Verify the supplier is active
4. Check that category and subcategory are active
5. Ensure there is stock available

## Order Issues

### Cannot Create Order

**Symptom:** Order creation fails.

**Solutions:**
1. Verify the customer ID is valid
2. Ensure the customer has a default shipping address
3. Select a shipping method
4. Check for validation errors in the form

### Cannot Add Item to Order

**Symptom:** Adding items fails.

**Solutions:**
1. Verify the product ID is correct
2. Ensure the product is active and has stock
3. Check that quantity is between 1 and 10,000
4. Verify the order status allows editing (PENDING or PROCESSING)

### Cannot Cancel Order

**Symptom:** Cancel button doesn't work or shows error.

**Solutions:**
1. Orders can only be cancelled in PENDING status
2. If items are allocated, cancel individual purchase orders instead
3. Check if order is locked by another user

### Order Locked by Another User

**Symptom:** Cannot edit order; shows locked message.

**Solutions:**
1. Wait for the other user to finish and unlock
2. Contact the user who locked it
3. An administrator can unlock the order if needed

## Purchase Order Issues

### Cannot Edit PO Item Quantity

**Symptom:** Quantity field is disabled or changes don't save.

**Solutions:**
1. PO must be in PENDING status to edit quantities
2. Check that you're not exceeding the original order quantity
3. Refresh the page and try again

### Cannot Change PO Item Status

**Symptom:** Status dropdown doesn't show expected options.

**Solutions:**
1. Only valid status transitions are available
2. DELIVERED and CANCELLED items cannot be changed
3. Review the status lifecycle in the Purchase Orders documentation

### Allocation Fails

**Symptom:** Order allocation returns an error.

**Solutions:**
1. Check that products have active supplier products
2. Verify suppliers have stock available
3. Ensure suppliers are marked as active
4. Review which items failed and their supplier options

## Supplier Issues

### Supplier Products Not Showing

**Symptom:** Product stock dashboard shows no supplier options.

**Solutions:**
1. Ensure supplier products are linked to the catalog product
2. Check supplier product active status
3. Verify supplier is active
4. Use "Map Product" on unlinked supplier products

### Cannot Delete Supplier

**Symptom:** Delete fails or is blocked.

**Solutions:**
1. Check for active purchase orders with this supplier
2. Remove or reassign supplier products first
3. Complete or cancel pending orders

## Pricing Issues

### Prices Not Updating

**Symptom:** Product prices don't reflect recent changes.

**Solutions:**
1. Pricing changes can take a moment to propagate
2. Refresh the page
3. Check that all pricing levels are saved (product, subcategory, category)
4. Verify the active supplier product hasn't changed

### VAT Not Applied Correctly

**Symptom:** VAT amount seems wrong.

**Solutions:**
1. Check the category's VAT rate setting
2. Verify the correct VAT rate is selected
3. Remember that VAT is applied after price model rounding

### Markup Not Applying

**Symptom:** Sell price equals cost (no markup).

**Solutions:**
1. Check product-level markup setting
2. Check subcategory markup setting
3. Check category markup setting
4. Ensure at least one level has a markup > 0

## Report Issues

### Dashboard Shows No Data

**Symptom:** Dashboard KPIs are all zero.

**Solutions:**
1. Ensure there is order and sales data in the system
2. Check date filters; data may not exist for selected period
3. Wait for report calculations to run (hourly/daily)
4. Contact administrator to verify reporting jobs are running

### Charts Not Displaying

**Symptom:** Chart area is blank.

**Solutions:**
1. Refresh the page
2. Ensure JavaScript is enabled
3. Try a different browser
4. Clear browser cache

### Data Seems Stale

**Symptom:** Today's data doesn't reflect recent activity.

**Solutions:**
1. Report data updates approximately hourly
2. Refresh the page
3. Wait and check again later
4. Historical data is calculated overnight

## Ticket Issues

### Cannot Reply to Ticket

**Symptom:** Reply form is not visible or reply fails.

**Solutions:**
1. Check that the ticket is not in Closed status (reopen it first)
2. Refresh the page
3. Verify you have administrator access

### Tickets Not Appearing in My Queue

**Symptom:** My Queue shows no tickets despite open tickets existing.

**Solutions:**
1. Check that you are subscribed to the relevant pools
2. Navigate to Notes > Pools and subscribe to pools you need
3. The badge count is cached; wait a moment and refresh

### Cannot Delete a Pool

**Symptom:** Pool deletion fails.

**Solutions:**
1. Check for tickets still assigned to this pool
2. Reassign or close tickets in the pool first

## General Issues

### Form Won't Submit

**Symptom:** Clicking Save does nothing.

**Solutions:**
1. Check for validation errors (red highlighted fields)
2. Ensure all required fields are filled
3. Check browser console for JavaScript errors
4. Refresh the page and re-enter data

### Changes Not Saving

**Symptom:** Edits revert after leaving the page.

**Solutions:**
1. Ensure you clicked Save before navigating away
2. Check for error messages (notifications)
3. Verify you have permission to make changes
4. Refresh and check if changes actually saved

### Session Expired

**Symptom:** Suddenly logged out or seeing login page.

**Solutions:**
1. Sessions expire after 7 days of inactivity
2. Log in again
3. If happening frequently, contact administrator

### Error Messages

**Symptom:** Red error notification appears.

**Solutions:**
1. Read the error message carefully
2. The message usually explains what went wrong
3. Address the issue mentioned
4. If unclear, note the error and contact administrator

## Getting Help

If these solutions don't resolve your issue:

1. Note the exact error message
2. Note what action you were trying to perform
3. Take a screenshot if possible
4. Contact your system administrator

## Browser Compatibility

SupplyMars works best with:
- Chrome (recommended)
- Firefox
- Safari
- Edge

Ensure your browser is up to date for the best experience.

## Keyboard Shortcuts

| Key | Action |
|-----|--------|
| Escape | Close modal/form |
| Enter | Submit form (when in a field) |
| Tab | Move between form fields |

## Mobile Usage

On mobile devices:
- Tap the menu button (☰) to open navigation
- Forms may require scrolling
- Some features work better in landscape orientation
- For best experience, use a desktop/laptop for administrative tasks
