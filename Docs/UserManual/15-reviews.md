# Product Reviews

## What Product Reviews Are For

Product reviews provide a customer feedback and moderation system for delivered orders. Each review is linked to a specific product, customer, and order, allowing you to track what customers think about individual products they have received.

Reviews go through a moderation workflow before being visible on the product page. Once published, the system automatically maintains a summary for each product showing average ratings, rating distribution, and review counts.

## What You Can Do

- View all reviews in a filterable list
- Create a review for a delivered order
- Edit a review's rating, title, and body
- Approve a pending review (publishes it)
- Reject a pending review with a reason
- Hide a published review from the product page
- Republish a hidden review
- Delete a review permanently
- Filter reviews by status, product, customer, or rating
- View product review summaries on the product detail page
- Generate fake reviews for testing via console command

## Screens and Actions

### Review List

Navigate to **Reviews** in the main navigation to see all reviews.

The list displays reviews as cards showing:
- Review title
- Customer name
- Product name
- Rating (1-5 stars)
- Status badge
- Created date

**Sorting options:** ID, Rating, Status, Created Date

**Filtering:** Use the filter form to narrow results by status, product ID, customer ID, or rating.

### Create a Review

Navigate to **Reviews > New** or click the **New Review** button on the review list.

**Required fields:**
- **Customer ID** - The customer who wrote the review
- **Product ID** - The product being reviewed
- **Order ID** - The delivered order containing the product
- **Rating** - Score from 1 to 5

**Optional fields:**
- **Title** - Short summary (max 255 characters)
- **Body** - Full review text (max 2000 characters)

**Eligibility rules:**
- The order must be in DELIVERED status
- The order must belong to the specified customer
- The order must contain the specified product
- Only one review is allowed per customer and product combination

If any rule fails, the form displays a validation error.

### View Review Details

Click any review to see the full detail page. This shows:

- Customer name
- Product (linked to product detail page)
- Order (linked to order detail page)
- Rating displayed as stars
- Title and body text
- Current status
- Moderation information (if moderated):
  - Moderator name
  - Moderation date
  - Rejection reason (if rejected)
  - Moderation notes (if provided)
- Published date (if published)
- Created and updated timestamps

Available actions depend on the review's current status.

### Edit a Review

From the review detail page, click **Edit** to update the review.

**Editable fields:**
- Rating (1-5)
- Title
- Body

**Restrictions:**
- Editing is only available for reviews in PENDING or PUBLISHED status
- Customer, product, and order cannot be changed after creation
- If you change the rating on a published review, the product's review summary recalculates automatically

### Approve a Review

From the review detail page, click **Approve** to publish the review.

- Only available for PENDING reviews
- Transitions the review from PENDING to PUBLISHED
- Sets the published date
- Records you as the moderator
- Triggers a product review summary recalculation

### Reject a Review

From the review detail page, click **Reject** to reject the review.

- Only available for PENDING reviews
- Opens a form with:
  - **Rejection Reason** (required) - Select from predefined reasons
  - **Moderation Notes** (optional) - Free-text notes
- Transitions the review from PENDING to REJECTED
- Records you as the moderator
- Rejection is terminal; rejected reviews cannot be recovered

**Rejection Reasons:**
| Value | Label |
|-------|-------|
| SPAM | Spam |
| INAPPROPRIATE | Inappropriate content |
| OFF_TOPIC | Off topic |
| DUPLICATE | Duplicate review |
| MISLEADING | Misleading content |
| OTHER | Other |

### Hide a Review

From the review detail page, click **Hide** to remove a published review from the product page.

- Only available for PUBLISHED reviews
- Transitions from PUBLISHED to HIDDEN
- Records you as the moderator
- Triggers a product review summary recalculation

### Republish a Review

From the review detail page, click **Republish** to restore a hidden review.

- Only available for HIDDEN reviews
- Transitions from HIDDEN to PUBLISHED
- Records you as the moderator
- Triggers a product review summary recalculation

### Delete a Review

From the review detail page, click **Delete** to permanently remove the review.

- A confirmation dialog appears before deletion
- Available for reviews in any status
- Deletion is permanent and cannot be undone
- Triggers a product review summary recalculation

### Filter Reviews

On the review list page, click **Filter** to open the filter form.

**Filter fields:**
| Field | Type | Description |
|-------|------|-------------|
| Status | Dropdown | Filter by review status (Pending, Published, Rejected, Hidden) |
| Product ID | Number | Filter reviews for a specific product |
| Customer ID | Number | Filter reviews by a specific customer |
| Rating | Dropdown | Filter by star rating (1-5) |

### Product Review Summary

On the product detail page, the **Reviews** tab shows a summary of all published reviews:

- **Average rating** displayed with stars
- **Total published review count**
- **Rating distribution** as a bar chart (1-5 stars)
- **Pending review count** (reviews awaiting moderation)
- **Latest 5 published reviews** with title, rating, and excerpt

The summary updates automatically whenever reviews are created, approved, rejected, hidden, republished, deleted, or have their rating changed.

## Fields and Options

### Create Fields

| Field | Type | Required | Constraints |
|-------|------|----------|-------------|
| Customer ID | Integer | Yes | Must be a valid customer |
| Product ID | Integer | Yes | Must be a valid product |
| Order ID | Integer | Yes | Must be a delivered order belonging to the customer and containing the product |
| Rating | Integer | Yes | 1 to 5 |
| Title | Text | No | Max 255 characters |
| Body | Text | No | Max 2000 characters |

### Edit Fields

| Field | Type | Required | Constraints |
|-------|------|----------|-------------|
| Rating | Integer | Yes | 1 to 5 |
| Title | Text | No | Max 255 characters |
| Body | Text | No | Max 2000 characters |

### Rejection Fields

| Field | Type | Required | Constraints |
|-------|------|----------|-------------|
| Reason | Dropdown | Yes | One of the predefined rejection reasons |
| Notes | Text | No | Free-text moderation notes |

### Filter Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Status | Dropdown | No | Pending, Published, Rejected, Hidden |
| Product ID | Number | No | Range 1-1000000 |
| Customer ID | Number | No | Range 1-1000000 |
| Rating | Dropdown | No | 1 to 5 |

## Status and Lifecycle

### Status Table

| Status | Colour | Meaning |
|--------|--------|---------|
| PENDING | Yellow | Awaiting moderation |
| PUBLISHED | Green | Visible on product page |
| REJECTED | Red | Rejected by moderator (terminal) |
| HIDDEN | Grey | Temporarily removed from product page |

### Status Transitions

| From | To | Action |
|------|----|--------|
| PENDING | PUBLISHED | Approve |
| PENDING | REJECTED | Reject |
| PUBLISHED | HIDDEN | Hide |
| HIDDEN | PUBLISHED | Republish |

### Typical Workflow

```
Customer submits review
        |
        v
    [ PENDING ]
       / \
      /   \
Approve   Reject
    |         |
    v         v
[PUBLISHED] [REJECTED]
    |       (terminal)
    |
   Hide
    |
    v
 [HIDDEN]
    |
 Republish
    |
    v
[PUBLISHED]
```

### Editable States

| Status | Can Edit? |
|--------|-----------|
| PENDING | Yes |
| PUBLISHED | Yes |
| REJECTED | No |
| HIDDEN | No |

### Automatic Summary Updates

Whenever a review changes in a way that affects the product's published review stats, the product review summary recalculates automatically. This happens on:

- Review creation
- Status change (approve, reject, hide, republish)
- Rating change on a published review

## Warnings

- **Rejected is terminal** - Once a review is rejected, it cannot be approved, edited, or recovered
- **Delete is permanent** - Deleting a review removes it from the database entirely
- **One review per customer and product** - A customer can only review a product once; duplicate attempts are rejected
- **Only delivered orders** - Reviews can only be created for orders in DELIVERED status
- **Rating edits update the summary** - Changing the rating on a published review triggers a summary recalculation
- **Customer, product, and order are immutable** - These fields cannot be changed after the review is created
