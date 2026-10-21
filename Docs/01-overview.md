# Domain Map

Which context owns which entities, and which doc holds each rule. Read it when you are new to the codebase or need to find where a business rule is written down. SupplyMars is about what happens after checkout: sourcing each order line from competing suppliers, pricing through a category cascade, and following each purchase order through its own lifecycle. The rules between contexts are in [03-architecture.md](03-architecture.md#rules-between-contexts).

## Contexts

| Context | Main entities (`Domain/Model/`) | Rules documented in |
|---------|---------------------------------|---------------------|
| Audit | `StatusChangeLog`, `SupplierStockChangeLog` | [05-features.md](05-features.md#audit-logging) |
| Catalog | `Product`, `ProductImage`, `Category`, `Subcategory`, `Manufacturer` | [04-sourcing-and-pricing.md](04-sourcing-and-pricing.md), [05-features.md](05-features.md) |
| Customer | `User`, `Address`, `ResetPasswordRequest` | [05-features.md](05-features.md), [08-security.md](08-security.md) |
| Home | none: the dashboard and sidebar badge counts (`src/Home/Service/`) | [03-architecture](03-architecture.md) (Home has no layers); [ADR 014](adr/014-architecture-rules.md) (why the badges live here) |
| Note | `Pool`, `Ticket`, `Message` | [ADR 009](adr/009-support-ticket-system.md) |
| Order | `CustomerOrder`, `CustomerOrderItem` | [04-sourcing-and-pricing.md](04-sourcing-and-pricing.md), [ADR 002](adr/002-order-line-splitting.md) |
| Pricing | `VatRate`; listeners that reprice products (`src/Pricing/Application/Listener/`) | [04-sourcing-and-pricing.md](04-sourcing-and-pricing.md), [ADR 004](adr/004-pricing-abstraction.md) |
| Purchasing | `Supplier`, `SupplierProduct`, `PurchaseOrder`, `PurchaseOrderItem`, supplier category/subcategory/manufacturer mappings | [04-sourcing-and-pricing.md](04-sourcing-and-pricing.md), [ADR 001](adr/001-multi-supplier-sourcing.md) |
| Reporting | `ProductSales`, `OrderSales`, `CustomerSales`, `CustomerActivitySales`, and summaries read by the screens (`ProductSalesSummary`, `CustomerGeographicSummary`, …) | [ADR 005](adr/005-reporting-strategy.md), [ADR 010](adr/010-customer-insights-reporting.md) |
| Review | `ProductReview`, `ProductReviewSummary` | [05-features.md](05-features.md) |

`Shared` is the kernel, not a business context: `MarkupCalculator`, `PriceModel`, `AbstractUlidId`, `Result`, the schedule and health checks. See [03-architecture.md](03-architecture.md).

## Key Concepts

| Concept | Where | Rule in |
|---------|-------|---------|
| A product has many supplier products; one of them is its active source | `Product::recalculateActiveSource()`, `SupplierProduct` | [04](04-sourcing-and-pricing.md), [ADR 001](adr/001-multi-supplier-sourcing.md) |
| Best source: lowest cost among sources with enough stock, ties to higher stock | `Product::getBestSourceWithMinQuantity()` | [04](04-sourcing-and-pricing.md) |
| Product cost, stock and lead time are copies of the active source's, stored on the product | `Product::applyActiveSource()` | [04](04-sourcing-and-pricing.md) |
| Warehouse supplier: a flag, with no preference in sourcing | `Supplier::setAsWarehouse()` (fixtures only) | [04](04-sourcing-and-pricing.md) |
| Markup cascade Product → Subcategory → Category, VAT from the category | `Product::getActiveMarkup()`, `MarkupCalculator` | [04](04-sourcing-and-pricing.md), [ADR 004](adr/004-pricing-abstraction.md) |
| Price models (pretty-price rounding) | `src/Shared/Domain/ValueObject/PriceModel.php` | [04](04-sourcing-and-pricing.md) |
| Order line price is captured when the line is added | `CustomerOrderItem::createFromProduct()` | [04](04-sourcing-and-pricing.md#prices-on-orders) |
| Outstanding quantity: ordered minus quantity on PO items that are not cancelled or refunded | `CustomerOrderItem::getOutstandingQty()` | [04](04-sourcing-and-pricing.md), [ADR 002](adr/002-order-line-splitting.md) |
| Allocation: one supplier per line, for the whole outstanding quantity | `src/Purchasing/Application/Service/OrderAllocator.php` | [04](04-sourcing-and-pricing.md), [ADR 002](adr/002-order-line-splitting.md) |
| Order and item status derive from PO items | `CustomerOrderItem::generateStatus()`, `OrderStatus::getMappedOrderStatusFromPurchaseOrder()` | [04](04-sourcing-and-pricing.md) |
| Purchase order lifecycle | `PurchaseOrderStatus::canTransitionTo()` | [04](04-sourcing-and-pricing.md) |
| Simulation drives the lifecycle | `src/*/UI/Console/`, `src/Purchasing/Application/Service/ProcessingSimulator.php` | [09-cli-reference.md](09-cli-reference.md), [06-operations.md](06-operations.md), [ADR 003](adr/003-simulation-first-design.md) |
| Two-layer reporting: daily records, then summaries | `src/Reporting/Domain/Model/` | [ADR 005](adr/005-reporting-strategy.md) |

## Related

- [03-architecture.md](03-architecture.md): layers, rules between contexts, domain events
- [04-sourcing-and-pricing.md](04-sourcing-and-pricing.md): sourcing, allocation, status and pricing rules
- [05-features.md](05-features.md): business rules per feature
- [UserManual/](UserManual/README.md): the same features from the staff user's side
