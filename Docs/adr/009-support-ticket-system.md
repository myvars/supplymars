# ADR 009: Pool-Based Support Ticket System

## Status

Accepted

## Context

The platform needed an internal support system for managing customer communications. Requirements included:

- Categorizing tickets by topic so staff can self-select their workload
- Threaded conversations between staff and customers
- Staff-only internal notes that customers cannot see
- Automatic audit trail of state changes (reassignments, status transitions)
- Snoozing tickets temporarily without losing their status
- Fast ticket listing pages without joining on messages

Alternative approaches considered:

1. **Flat ticket list with manual assignment** - Simple but creates bottlenecks around a dispatcher role and doesn't scale with team size.
2. **Tag-based categorization** - Flexible but lacks the clear ownership boundary that pools provide. Tags don't naturally map to team responsibilities.
3. **Queue-based assignment** - Auto-assigns tickets round-robin. Removes staff autonomy and doesn't account for expertise or availability.

## Decision

We implemented a **pool-based ticket system** with three core entities: Pool, Ticket, and Message.

### Pool-Based Routing

Pools are named categories (e.g., "Billing", "Technical Support", "Returns") that group related tickets. Staff **subscribe** to pools they want to work on, creating a self-directed assignment model rather than top-down dispatching.

```
Pool (category)
├── subscribers: ManyToMany → User (staff who work this pool)
├── isActive: bool (can receive new tickets)
├── isCustomerVisible: bool (shown in ticket creation form)
└── Tickets filed into this pool
```

This gives teams flexibility: a staff member can subscribe to multiple pools, and pools can have overlapping subscribers. Tickets can be reassigned between pools as understanding of the issue evolves.

### Three-Entity Model

```
Pool ──< Ticket ──< Message
```

- **Pool** - Categorization and staff subscription. Controls visibility to customers.
- **Ticket** - A support case with subject, status, and customer reference. Belongs to one pool.
- **Message** - An individual message in the conversation thread. Tracks author, author type, and visibility.

### Status Transitions

Tickets have three statuses with automatic transitions based on who replies:

```
         ┌──────────────────────────┐
         │                          │
         ▼                          │
  ┌──────────┐   staff reply   ┌────────┐
  │   OPEN   │ ──────────────► │ REPLIED│
  │          │ ◄────────────── │        │
  └──────────┘  customer reply └────────┘
       │  ▲                       │
 close │  │ reopen          close │
       ▼  │                       ▼
  ┌──────────┐                ┌────────┐
  │  CLOSED  │ ◄───── close ──│        │
  └──────────┘                └────────┘
```

- **STAFF reply** → status becomes `REPLIED` (customer has been answered)
- **CUSTOMER reply** → status becomes `OPEN` (needs attention)
- **SYSTEM messages** do not change status
- **CLOSED tickets** ignore reply-based transitions

This eliminates manual status management for the common case. Staff close tickets explicitly when resolved.

### Snooze as Orthogonal to Status

Snoozing is a `snoozedUntil` timestamp on the ticket, not a separate status. This means a ticket can be OPEN and snoozed, or REPLIED and snoozed. Snoozed tickets are filtered out of the active queue until their snooze expires. This avoids the complexity of additional status values and the transitions between them.

### Message Visibility

Messages have two visibility levels:

- **PUBLIC** - Visible to both staff and customers
- **INTERNAL** - Visible only to staff (internal notes)

System-generated messages (e.g., "Ticket reassigned to Billing") are always PUBLIC, providing an audit trail visible to the customer.

### Denormalized Listing Fields

Ticket stores `messageCount` and `lastMessageAt` directly, updated when messages are added or removed. This avoids joining or subquerying the messages table for ticket listing pages, which is the most common view.

Database indexes support the primary query patterns:

- `idx_ticket_pool_status_snooze` - Pool inbox filtered by status and snooze state
- `idx_ticket_last_message` - Sorting by most recent activity

## Consequences

### Positive

- **Self-directed workload**: Staff subscribe to pools matching their expertise, no dispatcher bottleneck
- **Automatic status tracking**: Reply-based transitions reduce manual status management
- **Clean audit trail**: System messages record all state changes in the conversation thread
- **Fast listings**: Denormalized fields avoid message table joins on index pages
- **Flexible snoozing**: Orthogonal to status, no extra state machine complexity

### Negative

- **Denormalization maintenance**: `messageCount` and `lastMessageAt` must be kept in sync when messages are added or removed
- **No auto-assignment**: Staff must actively check their pools; tickets could go unnoticed if no one monitors a pool
- **Pool proliferation**: Without governance, teams may create overlapping pools that fragment ticket routing

### Implementation Notes

Key files:
- `src/Note/Domain/Model/Pool/Pool.php` - Pool entity with subscriber management
- `src/Note/Domain/Model/Ticket/Ticket.php` - Ticket entity with status transitions and snooze
- `src/Note/Domain/Model/Message/Message.php` - Message entity with visibility and author type
- `src/Note/Domain/Model/Ticket/TicketStatus.php` - OPEN, REPLIED, CLOSED enum with transition rules
- `src/Note/Domain/Model/Message/AuthorType.php` - CUSTOMER, STAFF, SYSTEM enum
- `src/Note/Domain/Model/Message/MessageVisibility.php` - PUBLIC, INTERNAL enum

The context follows standard project patterns:
- ULID public IDs (`TicketPublicId`, `PoolPublicId`, `MessagePublicId`)
- CQRS commands and handlers in `Application/`
- FormFlow controllers in `UI/Http/`
- Doctrine repositories in `Infrastructure/Persistence/`
- `FlusherInterface` for persistence
- `TimestampableEntity` trait for `createdAt`/`updatedAt`

## Related Documentation

- [ADR 006: FormFlow Controller Pattern](006-formflow-controller-pattern.md)
