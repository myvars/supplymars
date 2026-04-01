# Notes & Tickets

## What Notes Are For

The Notes module provides an internal ticketing and messaging system for managing support requests and team communication. It consists of:

- **Tickets** - Individual support cases or conversations, each containing a threaded message history
- **Pools** - Shared inboxes that group tickets by topic or team, with subscriber management
- **Messages** - Threaded replies within tickets, supporting public and internal visibility

## What You Can Do

### Tickets
- View all tickets in a filterable list
- Create new tickets
- Reply to tickets with messages
- Close and reopen tickets
- Reassign tickets to different pools
- Snooze tickets to temporarily hide them
- Delete individual messages

### Pools
- View all pools
- Create and edit pools
- Delete pools
- Subscribe and unsubscribe from pools
- Control customer visibility per pool

## Screens and Actions

### My Queue

**Navigate to:** Notes > My Queue

Shows open tickets in pools you are subscribed to. This is your personal inbox for tickets that need your attention.

A blue badge on the menu shows the count of open tickets in your subscribed pools.

### Ticket List

**Navigate to:** Notes > Tickets

The ticket list shows all tickets with:
- Subject line
- Current status
- Assigned pool
- Customer name
- Message count
- Last message date

**Sorting options:** ID, Subject, Status, Last Message

**Filtering options:**
- By Status (Open, Replied, Closed)
- By Pool
- By My Pools (show only tickets in your subscribed pools)

### Create a Ticket

**Navigate to:** Notes > Tickets > Click **Create Ticket**

1. Fill in the form:
   - **Subject** (required) - Brief description of the issue (max 255 characters)
   - **Pool** (required) - Assign to a shared inbox
   - **Customer** (optional) - Link to a customer account
   - **Message Body** (required) - Initial message content

2. Click **Save**

The ticket is created with **Open** status.

### View Ticket Details

**Navigate to:** Click on any ticket

The detail page shows:
- Ticket information card (subject, status, pool, customer)
- Message thread in chronological order
- Reply form (when ticket is not closed)
- Action buttons based on current status

Each message displays:
- Author name and type (Staff, Customer, or System)
- Message body
- Timestamp
- Delete button (for staff messages)

### Reply to a Ticket

**Navigate to:** Ticket detail > Use the reply form at the bottom

1. Enter your message in the **Body** field
2. Click **Reply**

Replying as staff automatically changes the ticket status to **Replied**. Customer replies change the status back to **Open**.

### Close a Ticket

**Navigate to:** Ticket detail > Click **Close**

Closes the ticket, indicating the issue is resolved. Closed tickets cannot receive new replies until reopened.

### Reopen a Ticket

**Navigate to:** Ticket detail > Click **Reopen**

Reopens a closed ticket, changing the status back to **Open** and allowing new replies.

### Reassign a Ticket

**Navigate to:** Ticket detail > Click **Reassign**

1. Select a new pool from the dropdown
2. Click **Save**

Use this to move a ticket to a different team or topic area.

### Snooze a Ticket

**Navigate to:** Ticket detail > Click **Snooze**

Temporarily hides the ticket from your queue. The ticket reappears when the snooze period expires.

### Delete a Message

**Navigate to:** Ticket detail > Click **Delete** on a message

1. A confirmation dialog appears
2. Click **Delete** to confirm

Message deletion is permanent. The ticket's message count updates automatically.

---

### Pool List

**Navigate to:** Notes > Pools

The pool list shows all pools with:
- Pool name
- Active/Inactive status
- Description

### Create a Pool

**Navigate to:** Notes > Pools > Click **Create Pool**

1. Fill in the form:
   - **Name** (required) - Pool name (max 255 characters)
   - **Description** (optional) - Purpose of this pool
   - **Active** (checkbox) - Whether the pool is available for use
   - **Customer Visible** (checkbox) - Whether customers can see this pool

2. Click **Save**

### View Pool Details

**Navigate to:** Click on any pool

The detail page shows:
- Pool information card
- List of subscribed team members
- Your subscription status
- Subscribe/Unsubscribe button

### Edit a Pool

**Navigate to:** Pool detail > Click **Edit**

You can update:
- Pool name
- Description
- Active status
- Customer visibility

### Delete a Pool

**Navigate to:** Pool detail > Edit > Click **Delete**

1. A confirmation dialog appears
2. Click **Delete** to confirm

### Subscribe to a Pool

**Navigate to:** Pool detail > Click **Subscribe** or **Unsubscribe**

When subscribed to a pool:
- Tickets in that pool appear in your **My Queue**
- The blue badge count includes tickets from your subscribed pools

## Fields and Options

### Ticket Fields

| Field | Required | Description |
|-------|----------|-------------|
| Subject | Yes | Brief description (max 255 characters) |
| Pool | Yes | Shared inbox to assign the ticket to |
| Customer | No | Linked customer account |
| Message Body | Yes | Initial message content |

### Pool Fields

| Field | Required | Default | Description |
|-------|----------|---------|-------------|
| Name | Yes | - | Pool name (max 255 characters) |
| Description | No | - | Purpose of this pool |
| Active | No | Inactive | Whether pool is available for use |
| Customer Visible | No | No | Whether customers can see this pool |

## Status and Lifecycle

### Ticket Status

| Status | Meaning |
|--------|---------|
| **Open** (green) | New or customer-replied, needs attention |
| **Replied** (blue) | Staff has replied, awaiting customer |
| **Closed** (grey) | Issue resolved |

### Status Transitions

| From | To | Trigger |
|------|----|---------|
| Open | Replied | Staff replies |
| Open | Closed | Click Close |
| Replied | Open | Customer replies |
| Replied | Closed | Click Close |
| Closed | Open | Click Reopen |

### Message Visibility

| Type | Meaning |
|------|---------|
| **Public** | Visible to both staff and customers |
| **Internal** | Visible to staff only |

### Author Types

| Type | Meaning |
|------|---------|
| **Staff** | Message from an administrator |
| **Customer** | Message from the customer |
| **System** | Automated system message |

## Warnings

- Closed tickets cannot receive replies until reopened
- Deleting a message is permanent and cannot be undone
- Deleting a pool may affect tickets assigned to it
- Unsubscribing from a pool removes its tickets from your My Queue count
- The My Queue badge count is cached and updates periodically
