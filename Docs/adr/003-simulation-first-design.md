# ADR 003: Simulation-First Design

## Status

Accepted

## Context

SupplyMars needed realistic data flowing through the system to:

- Demonstrate the platform's capabilities
- Test complex multi-step workflows
- Generate meaningful reporting data
- Validate business rules under realistic conditions

We could either:

1. **Manual data entry** - Require users to create all test data by hand
2. **Static fixtures** - Load pre-defined datasets that don't evolve
3. **Simulation-driven** - Build commands that generate and progress data through the system

## Decision

We adopted a **simulation-first design** where console commands drive the entire order lifecycle:

### Simulation Commands

| Command | Purpose |
|---------|---------|
| `app:create-customer-orders` | Generate realistic orders with random products |
| `app:build-purchase-orders` | Allocate orders to suppliers |
| `app:accept-purchase-orders` | Simulate supplier responses (98% accept) |
| `app:ship-purchase-order-items` | Progress accepted items to shipped |
| `app:deliver-purchase-order-items` | Complete delivery |
| `app:refund-purchase-orders` | Handle rejections |
| `app:update-supplier-stock` | Fluctuate inventory levels |

### Simulation Realism

Commands include realistic timing and probability:

- **Time gates**: Items don't ship until 2+ hours after acceptance
- **Business hours**: Shipping only between 09:00-18:00
- **Rejection rate**: 2% of items rejected (1 in 50)
- **Stock behavior**: Low stock triggers replenishment

### Cron Integration

Production runs simulation on cron to maintain data flow:
```cron
*/5  * * * * app:create-customer-orders 2 --random
*/15 * * * * app:accept-purchase-orders 20
*/30 * * * * app:build-purchase-orders 20
0    * * * * app:ship-purchase-order-items 100
```

## Consequences

### Positive

- **Always-fresh data**: Reporting dashboards show meaningful trends
- **Realistic testing**: Business rules validated with real workflows
- **Demo-ready**: System can be demonstrated without manual setup
- **Stress testing**: Run commands in bulk to test performance

### Negative

- **Not a real storefront**: Orders aren't from actual customers
- **Database growth**: Simulation creates real records that accumulate
- **Email leakage risk**: Production must handle simulation emails carefully
- **Metrics confusion**: Need to remember data is fabricated

### Implementation Notes

Key design patterns:

1. **Commands use same services as UI** - `OrderAllocator` called from both command and controller ensures consistent behavior

2. **Randomization with bounds** - Commands accept counts but include reasonable limits:
   ```php
   const MAX_ORDER_LINES = 5;
   const MAX_LINE_QTY = 5;
   ```

3. **Idempotent where possible** - Running commands multiple times is safe (processes different records)

4. **Graceful failure** - Commands continue processing even if individual items fail:
   ```php
   try {
       $this->allocator->process($order);
   } catch (\Throwable) {
       continue; // Skip to next order
   }
   ```

The simulation-first approach means the system is designed to handle high-volume automated processing, which makes it more robust for real production use.
