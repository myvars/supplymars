# ADR 002: Order Line Splitting

## Status

Accepted. Amended: automatic allocation uses one supplier that can supply the whole outstanding quantity; lines split only through manual PO items and refunds.

## Context

When a customer orders a quantity that exceeds any single supplier's available stock, we faced a decision:

1. **Reject the order** - Refuse orders that can't be fulfilled from a single source
2. **Wait for stock** - Hold the order until one supplier can fulfill entirely
3. **Split across suppliers** - Fulfill from multiple suppliers simultaneously

Option 3 is how sophisticated supply chains actually work, but it introduces complexity.

## Decision

We implemented **order line splitting** where a single CustomerOrderItem can be fulfilled by multiple PurchaseOrderItems from different suppliers.

### Mechanism

1. **Outstanding quantity tracking** - CustomerOrderItem tracks:
   ```php
   getOutstandingQty() = quantity - sum(non-cancelled, non-refunded PO item quantities)
   ```

2. **Allocation** - OrderAllocator processes items with outstanding quantity:
   - Find the best source that can supply the whole outstanding quantity
   - If no source can: skip the item; it stays outstanding for the next allocation pass
   - A line is split across suppliers only when staff add or edit PO items by hand, or after a cancel or refund

3. **Multiple PO items per order item** - A CustomerOrderItem can have multiple PurchaseOrderItems, each from a different supplier with different quantities.

4. **Independent status tracking** - Each PurchaseOrderItem has its own status. The CustomerOrderItem status derives from the minimum (worst) status of its PO items.

### Example

A split line, as staff would build it by hand (automatic allocation would skip this line, because no single supplier has 100). Customer orders 100 widgets:
- Supplier A has 60 @ £8.00 (cheapest)
- Supplier B has 50 @ £8.50
- Supplier C has 30 @ £9.00

Result:
- PO to Supplier A: 60 units
- PO to Supplier B: 40 units (fulfills remaining 40)
- Outstanding: 0

## Consequences

### Positive

- **Higher fulfillment rate**: Orders complete even when no single supplier has full stock
- **Cost optimization**: Cheapest sources used first
- **Partial shipment capability**: Different parts of an order can ship at different times
- **Realistic modeling**: Matches real-world supply chain behavior

### Negative

- **Status complexity**: Order item status = min(PO item statuses) adds cognitive load
- **Customer communication**: Customers may receive multiple shipments for one order
- **Tracking complexity**: Need to track which PO items map to which order items
- **Pricing edge cases**: Different suppliers may have different lead times affecting delivery promises

### Implementation Notes

Key files:
- `src/Order/Domain/Model/Order/CustomerOrderItem.php` - `getOutstandingQty()`, `getQtyAddedToPurchaseOrders()`
- `src/Purchasing/Domain/Model/PurchaseOrder/PurchaseOrderItem.php` - Links to CustomerOrderItem
- `src/Purchasing/Application/Service/OrderItemAllocator.php` - Creates or tops up the PO item for an order item

The `getMaxQuantity()` method on PurchaseOrderItem ensures edits don't exceed what the order item still needs:
```php
getMaxQuantity() = currentQty + orderItem.getOutstandingQty()
```

This prevents allocating more than the customer ordered.
