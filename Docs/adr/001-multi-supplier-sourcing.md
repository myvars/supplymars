# ADR 001: Multi-Supplier Sourcing Model

## Status

Accepted

## Context

E-commerce platforms typically source products from a single warehouse. However, real-world operations often involve:

- Multiple suppliers with varying costs
- Dropship partnerships where inventory is held by third parties
- Need to optimize cost while maintaining fulfillment capability
- Situations where no single supplier can fulfill an entire order

We needed to decide how to model the relationship between products and their sources, and how orders would be fulfilled when multiple suppliers are involved.

## Decision

We implemented a **multi-supplier sourcing model** where:

1. **Products aggregate multiple SupplierProducts** - A catalog Product can have multiple SupplierProducts from different suppliers, each with independent cost, stock, and lead time.

2. **Automatic best-source selection** - Products automatically select the "best" supplier based on:
   - Lowest cost (primary criterion)
   - Highest stock (tie-breaker)
   - Active status (both supplier and supplier product)

3. **Order items source from products, not suppliers** - CustomerOrderItems reference Products, not SupplierProducts. The sourcing decision happens at allocation time, not order time.

4. **Allocation generates per-supplier PurchaseOrders** - The OrderAllocator service examines each order item's outstanding quantity, finds the best available source, and creates PurchaseOrderItems linking back to CustomerOrderItems.

5. **One warehouse, multiple dropshippers** - The system distinguishes between a primary warehouse (own inventory) and EDI-style dropshippers.

## Consequences

### Positive

- **Cost optimization**: Orders automatically route to lowest-cost sources
- **Resilience**: If one supplier runs out, orders can source from alternatives
- **Flexibility**: New suppliers can be added without changing order flow
- **Visibility**: Purchase orders show exactly which supplier fulfills what

### Negative

- **Complexity**: Multiple PurchaseOrders per CustomerOrder increases state management
- **Status derivation**: Order status must cascade through PO items → Order items → Orders
- **No reservation**: Stock is checked at allocation time, not order time (theoretical oversell risk)

### Implementation Notes

Key files:
- `src/Catalog/Domain/Model/Product/Product.php` - `calculateBestActiveSource()`, `getBestSourceWithMinQuantity()`
- `src/Purchasing/Application/Service/OrderAllocator.php` - Allocation orchestration
- `src/Purchasing/Application/Service/OrderItemAllocator.php` - Per-item allocation

The decision to defer sourcing to allocation time (rather than order time) allows the system to:
- React to stock changes between order and fulfillment
- Batch allocations for efficiency
- Handle partial availability gracefully
