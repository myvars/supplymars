# Customers

## What Customers Are For

Customers are the user accounts that place orders in SupplyMars. Each customer has:

- Account credentials (email and password)
- Profile information (name)
- One or more addresses for shipping and billing
- Order history

## What You Can Do

- View all customers
- Edit customer details
- Delete customers (with restrictions)
- View customer addresses
- View customer order history
- Mark accounts as verified
- Grant or revoke administrator access

## Screens and Actions

### Customer List

**Navigate to:** Customers

The customer list shows all customers with:
- Full name
- Email address
- Verified/Unverified status

**Sorting options:** ID, Name, Email, Verified Status

### View Customer Details

**Navigate to:** Click on any customer

The detail page shows:
- Customer information card (name, email, verification status)
- Customer insights card (revenue, orders, segment)
- All associated addresses with billing/shipping designation
- Order history summary with link to filtered orders
- Order count

### Customer Insights Card

The customer detail page includes an insights card showing key metrics:

| Metric | Description |
|--------|-------------|
| **Lifetime Revenue** | Total amount spent by this customer |
| **Order Count** | Number of orders placed |
| **Average Order Value** | Revenue divided by order count |
| **First Order** | Date of first purchase |
| **Last Order** | Date of most recent purchase |
| **Days Since Last Order** | Recency indicator |
| **Revenue Rank** | Position compared to other customers (e.g., "Top 5%") |
| **Segment** | Customer classification (New, Returning, Loyal, or Lapsed) |
| **Reviews** | Number of product reviews submitted |

**Customer Segments:**
| Segment | Meaning |
|---------|---------|
| **New** | 0-1 lifetime orders |
| **Returning** | 2-3 lifetime orders |
| **Loyal** | 4+ lifetime orders |
| **Lapsed** | No activity in 60+ days |

This information helps you understand customer value and engagement at a glance.

### Edit a Customer

**Navigate to:** Customer detail > Click **Edit**

You can update:
- **Full Name** - Customer's display name
- **Email** - Login email (must be unique)
- **Verified** (checkbox) - Mark email as verified
- **Staff Member** (checkbox) - Grant administrator access

### Delete a Customer

**Navigate to:** Customer detail > Edit > Click **Delete**

A confirmation page shows:
- Customer details
- Order count
- Reasons why deletion may be blocked

**Deletion is blocked if:**
- Customer is an administrator
- Customer has any order history

## Fields and Options

### Customer Fields

| Field | Required | Description |
|-------|----------|-------------|
| Full Name | Yes | Display name (max 50 characters) |
| Email | Yes | Login email (must be unique) |
| Verified | No | Whether email has been verified |
| Staff Member | No | Whether user has administrator access |

### Simulated vs Registered Accounts

The customer detail card indicates whether an account is a **Simulated account** or a **Registered account**:

- **Simulated account** — Created by the simulator (`app:create-customer-orders`). These are fake accounts used for demonstration and testing.
- **Registered account** — Created via the public registration form by a real user.

### Address Fields

Each customer can have multiple addresses:

| Field | Required | Description |
|-------|----------|-------------|
| Full Name | No | Name for this address |
| Company Name | No | Business name |
| Street | Yes | Primary street address |
| Street 2 | No | Apartment, suite, etc. |
| City | Yes | City name |
| County | Yes | County/region |
| Post Code | Yes | Postal code (max 10 characters) |
| Country | Yes | Country name |
| Phone Number | No | Contact phone (max 20 characters) |
| Email | No | Address-specific email |
| Default Shipping | No | Mark as default shipping address |
| Default Billing | No | Mark as default billing address |

## Status and Lifecycle

### Verification Status

| Status | Meaning |
|--------|---------|
| **Verified** (green) | Email has been confirmed |
| **Unverified** (red) | Email not yet confirmed |

New accounts start as unverified. Customers must click a verification link sent to their email, or an administrator can manually mark them as verified.

### Staff/Administrator Status

| Status | Meaning |
|--------|---------|
| **Administrator** | Full access to all SupplyMars features |
| **Standard User** | Can view their own profile only |

Setting "Staff Member" to true grants the customer `ROLE_ADMIN` access. When admin access is granted, the user receives an email notification informing them of their new access level.

## Customer Registration

Customers can register themselves through the public registration page:

1. Enter full name
2. Enter email address
3. Create password (minimum 6 characters)
4. Confirm password
5. Accept Terms & Conditions
6. Submit registration

After registration:
- A verification email is sent
- Account remains unverified until email is confirmed
- Customer can request a new verification email if needed

## Password Reset

Customers can reset forgotten passwords:

1. Click "Forgot your password?" on login page
2. Enter email address
3. Check email for reset link
4. Click link and enter new password (minimum 6 characters)
5. Log in with new password

**Note:** The system does not reveal whether an account exists for security reasons.

## Addresses and Orders

### Default Addresses

- **Default Shipping Address:** Pre-selected when creating orders
- **Default Billing Address:** Pre-selected for billing information

Customers can have one default shipping and one default billing address.

### Order History

From the customer detail page, you can:
- See the total order count
- Click to view all orders from this customer
- Orders are filtered by customer ID

## Warnings

- Customers with order history cannot be deleted
- Administrator accounts cannot be deleted
- Changing a customer's email may affect their ability to log in
- Deleting a customer also deletes all their addresses
- Staff status grants full administrative access - use carefully
- Email addresses must be unique across the entire system
