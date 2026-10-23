# Customers

Customers are the user accounts that place orders. Staff accounts are customers too, with Member of Staff ticked.

Press `?` on this screen for a short summary.

**Where:** **Customers**

## Tasks

### Find a customer

1. Open **Customers**. Each card shows the name, email and Verified/Unverified status.
2. Search, or sort by **Id**, **Name**, **Email** or **Verified**. This list has no filter.

There is no Create button: accounts come from the public registration page (see [Getting Started](01-getting-started.md)) or from the simulator.

### View a customer

Click a customer. The page has three cards:

- **Customer:** name (with an Admin badge for staff), email, Verified/Unverified, and "Simulated account" (made by the simulator) or "Registered account" (signed up on the site).
- **Customer Insights:** the segment badge and the figures below.
- **Addresses:** street, city, post code and country, labelled Billing Address and Default Shipping Address. No screen edits addresses.

| Insight | Meaning |
|---|---|
| Total Revenue | Sum of the customer's order totals inc VAT |
| Order Count | Number of orders |
| Avg Order Value | Total Revenue divided by Order Count |
| Customer Rank | Position by revenue among all customers; 1 is the highest spender |
| First Order / Last Order | How long ago the first and latest orders were placed |
| Published Reviews | Number of published reviews; shown only when above 0 |

Cancelled orders are left out of every figure. Total Revenue, Order Count, Avg Order Value, First Order and Last Order link to the customer's orders.

The segment badge follows the customer's order count; press `?` for the rules. **Lapsed** (last order more than 60 days ago) replaces the others. The Customer Insights report counts segments per period instead ([Reports and Dashboard](13-reports.md#read-a-report)).

### Edit a customer

1. Click the pencil icon on the card.
2. Change **Full name**, **Email**, **Verified** or **Member of Staff** ("Grants full admin access.").
3. Click **Update Customer**.

The full name can also be changed in place on the detail page ([inline editing](02-navigation.md#inline-editing)).

### Delete a customer

1. Click the pencil icon to open the edit modal.
2. Click **Delete Customer**. The confirm modal shows the name, "(Administrator)" for staff, and the order count.
3. Click **Delete Customer** to confirm. For a customer who can't be deleted, the modal says why and offers only **Close**.

## Rules and limits

| Action | Blocked when | Message |
|---|---|---|
| Edit | The customer is staff and you are not a super admin | The edit button and inline name edit are hidden |
| Delete | The customer is staff | The confirm modal says "You cannot delete an admin user." |
| Delete | The customer has any orders | The confirm modal says "You cannot delete a customer with order history." |
| Save | The email is already used | "There is already an account with this email" |

- **Editing staff accounts is super admin only**, your own included, full name too. Any staff user can tick Member of Staff on an ordinary customer, but only a super admin can edit that account afterwards.
- **Granting staff access** sends the customer an email telling them so.
- **Customers without Member of Staff can't use the app;** what each role can do is in [Getting Started](01-getting-started.md#roles).
- **Verified** is a label: ticking it marks the email as confirmed. Unverified customers can still sign in.
- **Default addresses:** a new order takes the customer's default shipping and billing addresses automatically.
- Full name takes up to 50 characters; email up to 180.

## Related

- [Getting Started](01-getting-started.md): sign in, registration, password reset
- [Orders](08-orders.md)
- [Reviews](15-reviews.md)
- [Reports and Dashboard](13-reports.md): the Customer Insights report
