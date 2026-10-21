# Reviews

Customer reviews of products they have received, and the moderation that decides which ones are published.

Press `?` on this screen for a short summary.

**Where:** **Reviews > Review Search** (all reviews) and **Reviews > Moderation Queue** (Pending reviews only; the red badge is their count).

## Tasks

### Find a review

1. Go to **Reviews > Review Search**.
2. Sort by Id, Rating, Status or Created.
3. Click the filter icon in the search bar to narrow by Status, Product ID, Customer ID or Rating.
4. Click a review to open it. The page shows the review card (title, stars, text, product and order links, customer, dates) and, for a Published or Rejected review, a card with Moderated By, Moderated At, Rejection Reason and Moderation Notes. A Hidden review does not show this card.

### Create a review

1. On **Reviews > Review Search**, click **Create Review**.
2. Fill in **Customer ID**, **Product ID**, **Order ID** and **Rating** (1–5). **Title** (max 255 characters) and **Review** (max 2,000) are optional.
3. Click **Create Review**. The review starts as Pending.

### Moderate a review

The buttons sit at the bottom of the review card and depend on the status:

| Status | Button | Result |
|---|---|---|
| Pending | **Approve** | Published, with a published date |
| Pending | **Reject** | Opens a dialog: choose a **Rejection Reason** (required) and add **Moderation Notes** (optional), then click **Reject Review**. Becomes Rejected |
| Published | **Hide** | Hidden |
| Hidden | **Republish** | Published |

Every one records you as the moderator. Rejection reasons: Spam, Inappropriate content, Off topic, Duplicate review, Misleading content, Other.

### Edit or delete a review

1. Open the review and click the pencil icon on the card (shown only for Pending and Published reviews).
2. Change **Rating**, **Title** or **Review**, then click **Update Review**.
3. To delete instead, click **Delete Review** in the same dialog and confirm. Deletion is permanent.

### See a product's reviews

Open the product and its **Reviews** tab: a summary card (average rating, number of published reviews, rating distribution, pending count) and the 5 latest published reviews.

## Rules and limits

- **Who can review what.** The order must belong to the customer, the whole order must be Delivered, and the product must be in it. Errors you may see: "Order not found.", "Order does not belong to this customer.", "Delivered product not found in this order."
- **One review per customer per product**, across all their orders: "A review already exists for this customer and product."
- Customer, product and order cannot be changed after the review is created.
- **Rejected and Hidden reviews cannot be edited or deleted from the app**, because both edit and delete are reached through the pencil icon. To remove a hidden review, republish it first.
- The product's review summary recalculates on its own when a review is created, changes status, has its rating changed, or is deleted. Only Published reviews count towards the average.

## Statuses

| Status | Badge | Meaning | Can move to |
|---|---|---|---|
| Pending | Grey | Awaiting moderation | Published (Approve), Rejected (Reject) |
| Published | Green | Shown on the product's Reviews tab | Hidden (Hide) |
| Hidden | Grey | Taken off the product, can come back | Published (Republish) |
| Rejected | Orange | Final; cannot be undone | — |

## Related

- [Products](03-products.md): the product page and its Reviews tab.
- [Orders](08-orders.md#statuses): when an order counts as Delivered.
- [Reports and Dashboard](13-reports.md): Review Rate in Customer Insights and Pending Reviews on the Dashboard.
