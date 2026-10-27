# Notes and Tickets

Internal tickets: support cases and team conversations, grouped into pools that staff subscribe to.

Press `?` on this screen for a short summary.

**Where:** **Notes > My Queue**, **Notes > Tickets**, **Notes > Pools**.

## Tasks

### Work your queue

**Notes > My Queue** lists tickets that are not closed (Open and Replied) in the pools you subscribe to, leaving out snoozed ones. The blue badge next to it counts the same tickets.

To get a pool's tickets into your queue, subscribe to it (below).

### Find a ticket

1. Go to **Notes > Tickets**.
2. Sort by Id, Status, Last Message or Created.
3. Click the filter icon in the search bar to filter by **Pool**, **Status** or **Show Snoozed**. Snoozed tickets are hidden from every list unless Show Snoozed is ticked.

### Create a ticket

1. On **Notes > Tickets**, click **Create Ticket**.
2. Choose a **Pool** (active pools only), enter **Customer ID**, **Subject** (max 255 characters) and **Message** (max 10,000). Customer ID is required ("Please choose a customer"); an unknown id gives "Customer not found."
3. Click **Create Ticket**. The ticket starts as Open.

The ticket's first message is always credited to the customer and is Public, even though a staff user wrote it.

### Reply

1. Open the ticket. Messages are listed oldest first, with system events (closed, reopened, snoozed, unsnoozed, reassigned, and by whom) between them.
2. Under **Reply**, write the **Message** and choose **Visibility**: Public, or Internal (staff only).
3. Click **Send Reply**. Any reply sent from the app, Internal included, sets the ticket to Replied.

The reply form is not shown on a closed ticket. Reopen it first.

### Close, reopen, snooze or reassign

The icon buttons at the top right of the ticket card:

| Button | Shown when | Result |
|---|---|---|
| Close | Not closed | Closed |
| Reopen | Closed | Open |
| Snooze / Unsnooze | Not closed, or snoozed | Snooze hides the ticket for 24 hours and adds a Snoozed label; Unsnooze brings it back now |

To reassign, click **Reassign** next to the pool name, choose a **New Pool** (active pools only) and click **Reassign**. Moving to the pool it is already in fails with "Ticket is already in this pool."

### Delete a message

Click the trash icon on a message, then **Delete**. Deletion is permanent.

### Manage pools

1. Go to **Notes > Pools** and click **Create Pool**.
2. Enter **Pool Name** (max 255 characters) and **Description** (optional). Tick **Active** to offer the pool for new tickets (off by default). **Customer Visible** (on by default) is a label only: it shows on the pool card, and nothing else uses it.
3. Click **Create Pool**.
4. To change it, open the pool and click the pencil icon, then **Update Pool**. **Delete Pool** is in the same dialog.

### Subscribe to a pool

Open the pool and click **Subscribe** on the Subscription card (or **Unsubscribe**). The pool page shows the pool's details and your own subscription. It does not list other subscribers.

## Rules and limits

- **Messages you cannot delete:** the original message of a ticket ("The original message cannot be deleted.") and system messages ("System messages cannot be deleted."). Any other message can be deleted, whoever wrote it.
- **Deleting a pool is super admin only.** Others see "Deleting is disabled for this user." A pool that still has tickets cannot be deleted: "Has tickets — deactivate it instead."
- An inactive pool is not offered when creating a ticket, in the Pool filter or in the Reassign list, but its existing tickets stay.
- The My Queue badge can lag by up to 5 minutes.

## Statuses

| Status | Badge | Meaning | Moves to |
|---|---|---|---|
| Open | Blue | New, or the customer replied; needs attention | Replied (staff reply), Closed (Close) |
| Replied | Yellow | Staff replied; waiting for the customer | Open (customer reply), Closed (Close) |
| Closed | Grey | Resolved | Open (Reopen) |

Customers cannot reply from this app today, so a ticket moves from Replied back to Open only when it is closed and reopened.

**Messages:**

| Author | Meaning |
|---|---|
| Staff | Written by a staff user in the app |
| Customer | Written by the customer |
| System | Recorded automatically on close, reopen, snooze, unsnooze and reassign |

## Related

- [Customers](07-customers.md): the customer a ticket links to.
- [Navigation](02-navigation.md#badges): menu badges.
