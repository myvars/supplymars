# System Overview

## Purpose and Scope

This document explains the domain model, key concepts, and design decisions that shape SupplyMars. Read this first if you're new to the codebase or need to understand how the pieces fit together.

The platform focuses on post-checkout complexity: what happens when orders need to be sourced from multiple suppliers, prices must cascade through category hierarchies, and fulfillment progresses through independent purchase-order lifecycles.

**Core capabilities:**

- **Multi-supplier sourcing** with automatic cost optimization
- **Dynamic pricing** driven by supplier costs and business rules
- **Intelligent order fulfillment** that splits across suppliers
- **End-to-end simulation** of the complete order lifecycle
- **Operational reporting** with dimensional analysis

## Key Domain Concepts

### Suppliers

Suppliers are the sources of inventory. The system distinguishes between:

| Supplier Type | Description | Characteristics |
|---------------|-------------|-----------------|
| **Warehouse** | Main/local inventory | Direct stock, fastest fulfillment |
| **Dropshippers** | EDI-style partners | Variable stock, may have lower costs |

Each supplier maintains its own:
- Product catalog (SupplierProducts)
- Pricing (cost per item)
- Stock levels
- Lead times

**Key files:**
- `src/Purchasing/Domain/Model/Supplier/Supplier.php`
- `src/Purchasing/Domain/Model/SupplierProduct/SupplierProduct.php`

### Supplier Products

A SupplierProduct represents a specific product variant offered by a supplier. Key characteristics:

- **Unique cost**: Each supplier sets their own price
- **Independent stock**: Stock levels vary by supplier
- **Lead time**: Days required for fulfillment
- **Mapping**: Links to a Product in the main catalog (or unmapped)

When a SupplierProduct is mapped to a Product, it becomes a potential source for fulfillment.

**Relationship:**
```
Supplier (1) ──→ (Many) SupplierProduct ──→ (1) Product
```

### Products

Products are the customer-facing items in the catalog. A Product:

- Aggregates multiple SupplierProducts as potential sources
- Derives its cost from the "best" available supplier
- Calculates sell price using markup hierarchies
- Tracks category, subcategory, and manufacturer

**Key distinction:** Products don't hold inventory directly. Stock is calculated from active SupplierProducts.

**Key files:**
- `src/Catalog/Domain/Model/Product/Product.php`
- `src/Catalog/Domain/Model/Category/Category.php`
- `src/Catalog/Domain/Model/Subcategory/Subcategory.php`

### Pricing

Pricing is determined through a hierarchical system:

```
┌─────────────┐
│   Product   │  ← Optional markup override
├─────────────┤
│ Subcategory │  ← Optional markup override
├─────────────┤
│  Category   │  ← Base markup (always set)
│  + VAT Rate │
└─────────────┘
```

Each level can also specify a **Price Model** (pretty-price rounding):
- `PRETTY_00` - Rounds to .00
- `PRETTY_99` - Rounds to .99
- `PRETTY_49` - Rounds to .49 or .99
- etc.

Final price = (Supplier Cost × Markup) + VAT, rounded per Price Model

**Key files:**
- `src/Shared/Domain/Service/Pricing/MarkupCalculator.php`
- `src/Shared/Domain/ValueObject/PriceModel.php`
- `src/Pricing/Domain/Model/VatRate/VatRate.php`

### Orders

A CustomerOrder represents a customer's purchase. Key characteristics:

- Contains multiple CustomerOrderItems
- Each item references a Product (not a SupplierProduct)
- Items capture price at time of order (may differ from current price)
- Orders progress through status: PENDING → PROCESSING → SHIPPED → DELIVERED

**Important:** The same product can appear on multiple order lines with different prices if added at different times.

**Key files:**
- `src/Order/Domain/Model/Order/CustomerOrder.php`
- `src/Order/Domain/Model/Order/CustomerOrderItem.php`
- `src/Order/Domain/Model/Order/OrderStatus.php`

### Order Items

CustomerOrderItems have sophisticated behavior:

- **Quantity tracking**: Total ordered vs. quantity allocated to suppliers
- **Outstanding quantity**: What remains to be sourced
- **Multiple allocations**: Can be split across multiple PurchaseOrderItems
- **Price capture**: Unit price frozen at order time

```php
$item->getOutstandingQty()  // quantity - sum(allocated to POs)
$item->getQtyAddedToPurchaseOrders()  // sum of non-cancelled PO items
```

### Purchase Orders

PurchaseOrders represent orders placed with suppliers to fulfill customer orders:

```
CustomerOrder (1) ──→ (Many) PurchaseOrder ──→ (1) Supplier
                            │
                            └── (Many) PurchaseOrderItem
```

A single CustomerOrder can generate multiple PurchaseOrders if:
- Different items source from different suppliers
- The same item splits across suppliers (partial availability)

**Purchase Order Status Flow:**
```
PENDING → PROCESSING → ACCEPTED → SHIPPED → DELIVERED
                   └→ REJECTED → REFUNDED
                   └→ CANCELLED
```

**Key files:**
- `src/Purchasing/Domain/Model/PurchaseOrder/PurchaseOrder.php`
- `src/Purchasing/Domain/Model/PurchaseOrder/PurchaseOrderItem.php`
- `src/Purchasing/Domain/Model/PurchaseOrder/PurchaseOrderStatus.php`

### Stock

Stock is not a separate entity but a property of SupplierProducts. The system tracks:

- **Supplier-level stock**: Each SupplierProduct has its own stock count
- **Product availability**: Derived from active suppliers with stock > 0
- **Stock changes**: Logged via `SupplierProductStockWasChangedEvent`

Stock simulation commands fluctuate levels to model real-world behavior.

### Simulations

SupplyMars is **simulation-first** by design. Console commands drive the entire order lifecycle:

| Simulation | Command | What It Does |
|------------|---------|--------------|
| Orders | `app:create-customer-orders` | Creates realistic customer orders |
| Allocation | `app:build-purchase-orders` | Sources orders to suppliers |
| Acceptance | `app:accept-purchase-orders` | Simulates supplier responses |
| Shipping | `app:ship-purchase-order-items` | Transitions to shipped |
| Delivery | `app:deliver-purchase-order-items` | Completes fulfillment |
| Stock | `app:update-supplier-stock` | Fluctuates inventory |
| Refunds | `app:refund-purchase-orders` | Handles rejections |

These simulations run on cron in production to maintain realistic data flow.

## What Makes This Non-Trivial

Beyond basic CRUD, here's where the interesting engineering problems live.

### 1. Multi-Supplier Sourcing

Unlike single-warehouse systems, SupplyMars must:
- Evaluate multiple suppliers for each product
- Select based on cost, availability, and business rules
- Handle partial availability across suppliers
- Generate separate PurchaseOrders per supplier

### 2. Price Independence

Order items capture price at order time, meaning:
- The same product can appear at different prices on one order
- Price changes don't retroactively affect existing orders
- Reporting must handle this complexity

### 3. Quantity Splitting

When a customer orders 10 units but Supplier A only has 6:
- 6 units go to Supplier A's PurchaseOrder
- 4 units allocated to Supplier B (or remain outstanding)
- The original OrderItem tracks both allocations

### 4. Status Derivation

Order and item statuses derive from their children:
- OrderItem status = minimum status of its PurchaseOrderItems
- Order status = minimum status of its OrderItems
- Changes cascade automatically

### 5. Event-Driven Pricing

Price recalculation cascades through events:
```
SupplierProduct cost change
  → SupplierProductPricingWasChangedEvent
    → Product.recalculateActiveSource()
      → Product.recalculatePrice()

Category markup change
  → CategoryPricingWasChangedEvent
    → Selective product recalculation (only affected products)
```

### 6. Two-Layer Reporting

To avoid expensive aggregations on every dashboard load:
- **Layer 1**: Daily granular records (ProductSales, OrderSales)
- **Layer 2**: Pre-computed summaries (ProductSalesSummary, OrderSalesSummary)

Summaries refresh via scheduled commands.

## Bounded Contexts

The system is organized into eight bounded contexts:

| Context | Responsibility | Key Entities |
|---------|---------------|--------------|
| **Catalog** | Product information | Product, Category, Subcategory, Manufacturer |
| **Purchasing** | Supplier relationships | Supplier, SupplierProduct, PurchaseOrder |
| **Order** | Customer orders | CustomerOrder, CustomerOrderItem |
| **Pricing** | Price management | VatRate, pricing listeners |
| **Reporting** | Business intelligence | ProductSales, OrderSales, Dashboards |
| **Customer** | Users and addresses | User, Address |
| **Audit** | Change tracking | StatusChangeLog, SupplierStockChangeLog |
| **Review** | Product reviews | ProductReview, ProductReviewSummary |

Plus a **Shared Kernel** containing cross-cutting concerns.

## Data Flow Example

Here's how a customer order flows through the system:

```
1. Customer places order (PENDING)
   └── CustomerOrder created with CustomerOrderItems

2. Order allocation triggered
   └── For each OrderItem:
       ├── Find best SupplierProduct (lowest cost with stock)
       ├── Get or create PurchaseOrder for that Supplier
       └── Create PurchaseOrderItem (deduct from outstanding qty)

3. Order moves to PROCESSING
   └── All items have at least one PurchaseOrderItem

4. Supplier accepts PurchaseOrder
   └── PO status: PROCESSING → ACCEPTED

5. Supplier ships items
   └── PO item status: ACCEPTED → SHIPPED
   └── OrderItem status derived: PROCESSING → SHIPPED

6. Items delivered
   └── PO item status: SHIPPED → DELIVERED
   └── OrderItem status: SHIPPED → DELIVERED
   └── Order status: PROCESSING → DELIVERED

7. Reporting aggregation (daily)
   └── ProductSales records created from delivered PO items
   └── OrderSales records created from completed orders
```

This flow demonstrates the system's sophisticated handling of multi-party fulfillment with derived status propagation.
