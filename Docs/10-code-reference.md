# Code Reference

This document provides a navigational map of the SupplyMars codebase, helping developers locate key components quickly.

## Directory Structure

```
src/
├── Audit/                    # Change tracking
├── Catalog/                  # Products and categorization
├── Customer/                 # Users and addresses
├── Home/                     # Homepage
├── Order/                    # Customer orders
├── Pricing/                  # VAT and pricing logic
├── Purchasing/               # Suppliers and purchase orders
├── Reporting/                # Sales reporting
├── Review/                   # Product reviews
└── Shared/                   # Cross-cutting concerns
```

---

## Catalog Context

### Domain Models

| Entity | File | Description |
|--------|------|-------------|
| Product | `src/Catalog/Domain/Model/Product/Product.php` | Main product entity with pricing |
| Category | `src/Catalog/Domain/Model/Category/Category.php` | Product categories with VAT rates |
| Subcategory | `src/Catalog/Domain/Model/Subcategory/Subcategory.php` | Category subdivisions |
| Manufacturer | `src/Catalog/Domain/Model/Manufacturer/Manufacturer.php` | Product manufacturers |
| ProductImage | `src/Catalog/Domain/Model/ProductImage/ProductImage.php` | Product images |

### Value Objects

| Class | File | Purpose |
|-------|------|---------|
| ProductPublicId | `src/Catalog/Domain/Model/Product/ProductPublicId.php` | ULID identifier |
| CategoryPublicId | `src/Catalog/Domain/Model/Category/CategoryPublicId.php` | ULID identifier |
| SubcategoryPublicId | `src/Catalog/Domain/Model/Subcategory/SubcategoryPublicId.php` | ULID identifier |

### Handlers

| Handler | File | Operation |
|---------|------|-----------|
| CreateProductHandler | `src/Catalog/Application/Handler/Product/CreateProductHandler.php` | Create product |
| UpdateProductHandler | `src/Catalog/Application/Handler/Product/UpdateProductHandler.php` | Update product |
| CreateCategoryHandler | `src/Catalog/Application/Handler/Category/CreateCategoryHandler.php` | Create category |
| CreateManufacturerHandler | `src/Catalog/Application/Handler/Manufacturer/CreateManufacturerHandler.php` | Create manufacturer |

### Repositories

| Interface | Implementation |
|-----------|----------------|
| `Domain/Repository/ProductRepository.php` | `Infrastructure/Persistence/Doctrine/ProductDoctrineRepository.php` |
| `Domain/Repository/CategoryRepository.php` | `Infrastructure/Persistence/Doctrine/CategoryDoctrineRepository.php` |
| `Domain/Repository/SubcategoryRepository.php` | `Infrastructure/Persistence/Doctrine/SubcategoryDoctrineRepository.php` |
| `Domain/Repository/ManufacturerRepository.php` | `Infrastructure/Persistence/Doctrine/ManufacturerDoctrineRepository.php` |

---

## Order Context

### Domain Models

| Entity | File | Description |
|--------|------|-------------|
| CustomerOrder | `src/Order/Domain/Model/Order/CustomerOrder.php` | Customer order aggregate |
| CustomerOrderItem | `src/Order/Domain/Model/Order/CustomerOrderItem.php` | Order line items |
| OrderStatus | `src/Order/Domain/Model/Order/OrderStatus.php` | Status enum |

### Key Methods (CustomerOrder)

```php
createFromCustomer()          // Factory method
addCustomerOrderItem()        // Add line item
recalculateTotal()            // Sum items + shipping
generateStatus()              // Derive from items
cancelOrder()                 // Cancel all items
allowEdit()                   // PENDING or PROCESSING
lockOrder(User)               // Prevent concurrent edits
```

### Key Methods (CustomerOrderItem)

```php
createFromProduct()           // Factory with price capture
updateItem()                  // Update qty/price
getOutstandingQty()           // quantity - allocated
getQtyAddedToPurchaseOrders() // Sum of PO item quantities
generateStatus()              // Derive from PO items
```

### Handlers

| Handler | File | Operation |
|---------|------|-----------|
| CreateOrderHandler | `src/Order/Application/Handler/CreateOrderHandler.php` | Create order |
| CreateOrderItemHandler | `src/Order/Application/Handler/CreateOrderItemHandler.php` | Add item |
| AllocateOrderHandler | `src/Order/Application/Handler/AllocateOrderHandler.php` | Trigger allocation |
| CancelOrderHandler | `src/Order/Application/Handler/CancelOrderHandler.php` | Cancel order |

---

## Purchasing Context

### Domain Models

| Entity | File | Description |
|--------|------|-------------|
| Supplier | `src/Purchasing/Domain/Model/Supplier/Supplier.php` | Supplier entity |
| SupplierProduct | `src/Purchasing/Domain/Model/SupplierProduct/SupplierProduct.php` | Supplier's product variant |
| PurchaseOrder | `src/Purchasing/Domain/Model/PurchaseOrder/PurchaseOrder.php` | PO to supplier |
| PurchaseOrderItem | `src/Purchasing/Domain/Model/PurchaseOrder/PurchaseOrderItem.php` | PO line item |
| PurchaseOrderStatus | `src/Purchasing/Domain/Model/PurchaseOrder/PurchaseOrderStatus.php` | Status enum |
| SupplierCategory | `src/Purchasing/Domain/Model/SupplierProduct/SupplierCategory.php` | Supplier's category |
| SupplierSubcategory | `src/Purchasing/Domain/Model/SupplierProduct/SupplierSubcategory.php` | Supplier's subcategory |
| SupplierManufacturer | `src/Purchasing/Domain/Model/SupplierProduct/SupplierManufacturer.php` | Supplier's manufacturer mapping |

### Application Services

| Service | File | Purpose |
|---------|------|---------|
| OrderAllocator | `src/Purchasing/Application/Service/OrderAllocator.php` | Orchestrates order allocation |
| OrderItemAllocator | `src/Purchasing/Application/Service/OrderItemAllocator.php` | Allocates single item to PO |
| EditablePurchaseOrderProvider | `src/Purchasing/Application/Service/EditablePurchaseOrderProvider.php` | Gets/creates PO for supplier |

### Key Methods (Supplier)

```php
create(name, isActive)        // Factory method
setAsWarehouse(bool)          // Mark as warehouse
isWarehouse()                 // Check warehouse flag
getColourScheme()             // UI color by supplier ID
```

### Key Methods (SupplierProduct)

```php
create(...)                   // Factory method
updateStock(int)              // Change stock level
updateCost(string)            // Change cost
assignProduct(?Product)       // Map to catalog product
isMapped()                    // Check if mapped
hasStock()                    // stock > 0
hasPositiveCost()             // cost > 0
```

---

## Pricing Context

### Domain Models

| Entity | File | Description |
|--------|------|-------------|
| VatRate | `src/Pricing/Domain/Model/VatRate/VatRate.php` | VAT rate entity |

### Listeners

| Listener | File | Trigger |
|----------|------|---------|
| VatRateWasChanged | `src/Pricing/Application/Listener/VatRateWasChanged.php` | VAT rate updates |
| CategoryPricingWasChanged | `src/Pricing/Application/Listener/CategoryPricingWasChanged.php` | Category pricing changes |
| SubcategoryPricingWasChanged | `src/Pricing/Application/Listener/SubcategoryPricingWasChanged.php` | Subcategory pricing changes |
| SupplierProductPricingWasChanged | `src/Pricing/Application/Listener/SupplierProductPricingWasChanged.php` | Supplier product changes |
| SupplierProductStatusWasChanged | `src/Pricing/Application/Listener/SupplierProductStatusWasChanged.php` | Supplier product activation |
| SupplierStatusWasChanged | `src/Pricing/Application/Listener/SupplierStatusWasChanged.php` | Supplier activation |

### Handlers

| Handler | File | Operation |
|---------|------|-----------|
| UpdateProductCostHandler | `src/Pricing/Application/Handler/UpdateProductCostHandler.php` | Product pricing |
| UpdateCategoryCostHandler | `src/Pricing/Application/Handler/UpdateCategoryCostHandler.php` | Category pricing |
| UpdateSubcategoryCostHandler | `src/Pricing/Application/Handler/UpdateSubcategoryCostHandler.php` | Subcategory pricing |

---

## Reporting Context

### Domain Models

| Entity | File | Description |
|--------|------|-------------|
| ProductSales | `src/Reporting/Domain/Model/SalesType/ProductSales.php` | Daily product sales |
| OrderSales | `src/Reporting/Domain/Model/SalesType/OrderSales.php` | Daily order sales |
| ProductSalesSummary | `src/Reporting/Domain/Model/SalesType/ProductSalesSummary.php` | Aggregated product sales |
| OrderSalesSummary | `src/Reporting/Domain/Model/SalesType/OrderSalesSummary.php` | Aggregated order sales |

### Metrics (Enums)

| Enum | File | Purpose |
|------|------|---------|
| SalesType | `src/Reporting/Domain/Metric/SalesType.php` | Grouping dimension (product, category, etc.) |
| SalesDuration | `src/Reporting/Domain/Metric/SalesDuration.php` | Time dimension (today, last 7, last 30) |
| ProductSalesMetric | `src/Reporting/Domain/Metric/ProductSalesMetric.php` | Product metrics (qty, cost, value, profit, margin) |
| OrderSalesMetric | `src/Reporting/Domain/Metric/OrderSalesMetric.php` | Order metrics (count, value, AOV) |

### Reports

| Report | File | Route |
|--------|------|-------|
| ProductSalesReport | `src/Reporting/UI/Http/Dashboard/Report/ProductSalesReport.php` | `/dashboard/report/product/sales` |
| OrderSummaryReport | `src/Reporting/UI/Http/Dashboard/Report/OrderSummaryReport.php` | `/dashboard/report/order/summary` |
| OverdueOrdersReport | `src/Reporting/UI/Http/Dashboard/Report/OverdueOrdersReport.php` | `/dashboard/report/overdue/orders` |

### Chart Builders

| Builder | File | Chart Type |
|---------|------|------------|
| BarChartBuilder | `src/Reporting/UI/Http/Dashboard/Chart/BarChartBuilder.php` | Time series bars |
| DoughnutChartBuilder | `src/Reporting/UI/Http/Dashboard/Chart/DoughnutChartBuilder.php` | Status breakdown |

---

## Customer Context

### Domain Models

| Entity | File | Description |
|--------|------|-------------|
| User | `src/Customer/Domain/Model/User/User.php` | User/customer entity |
| Address | `src/Customer/Domain/Model/Address/Address.php` | Shipping/billing address |
| ResetPasswordRequest | `src/Customer/Domain/Model/User/ResetPasswordRequest.php` | Password reset token |
| EmailVerifier | `src/Customer/Domain/Model/User/EmailVerifier.php` | Email verification service |

### Controllers

| Controller | File | Routes |
|------------|------|--------|
| SecurityController | `src/Customer/UI/Http/Controller/SecurityController.php` | `/login`, `/logout` |
| RegistrationController | `src/Customer/UI/Http/Controller/RegistrationController.php` | `/register`, `/verify/email` |
| ResetPasswordController | `src/Customer/UI/Http/Controller/ResetPasswordController.php` | `/reset-password` |
| CustomerController | `src/Customer/UI/Http/Controller/CustomerController.php` | `/customer/*` |

---

## Audit Context

### Domain Models

| Entity | File | Description |
|--------|------|-------------|
| StatusChangeLog | `src/Audit/Domain/Model/StatusChangeLog/StatusChangeLog.php` | Status transition log |
| SupplierStockChangeLog | `src/Audit/Domain/Model/SupplierStockChangeLog/SupplierStockChangeLog.php` | Stock change log |

---

## Review Context

### Domain Models

| Entity | File | Description |
|--------|------|-------------|
| ProductReview | `src/Review/Domain/Model/Review/ProductReview.php` | Customer review linked to product, customer, and order |
| ProductReviewSummary | `src/Review/Domain/Model/ReviewSummary/ProductReviewSummary.php` | Aggregated review stats per product |
| ReviewStatus | `src/Review/Domain/Model/Review/ReviewStatus.php` | Status enum (PENDING, PUBLISHED, REJECTED, HIDDEN) |
| RejectionReason | `src/Review/Domain/Model/Review/RejectionReason.php` | Rejection reason enum |

### Value Objects

| Class | File | Purpose |
|-------|------|---------|
| ReviewPublicId | `src/Review/Domain/Model/Review/ReviewPublicId.php` | ULID identifier for reviews |
| ReviewSummaryPublicId | `src/Review/Domain/Model/ReviewSummary/ReviewSummaryPublicId.php` | ULID identifier for summaries |

### Handlers

| Handler | File | Operation |
|---------|------|-----------|
| CreateReviewHandler | `src/Review/Application/Handler/CreateReviewHandler.php` | Create review |
| UpdateReviewHandler | `src/Review/Application/Handler/UpdateReviewHandler.php` | Update review |
| ApproveReviewHandler | `src/Review/Application/Handler/ApproveReviewHandler.php` | Approve review |
| RejectReviewHandler | `src/Review/Application/Handler/RejectReviewHandler.php` | Reject review |
| HideReviewHandler | `src/Review/Application/Handler/HideReviewHandler.php` | Hide review |
| RepublishReviewHandler | `src/Review/Application/Handler/RepublishReviewHandler.php` | Republish review |
| DeleteReviewHandler | `src/Review/Application/Handler/DeleteReviewHandler.php` | Delete review |

### Listeners

| Listener | File | Trigger |
|----------|------|---------|
| ReviewSummaryUpdater | `src/Review/Application/Listener/ReviewSummaryUpdater.php` | Review created, status changed, or rating changed |

### Repositories

| Interface | Implementation |
|-----------|----------------|
| `Domain/Repository/ReviewRepository.php` | `Infrastructure/Persistence/Doctrine/ReviewDoctrineRepository.php` |
| `Domain/Repository/ReviewSummaryRepository.php` | `Infrastructure/Persistence/Doctrine/ReviewSummaryDoctrineRepository.php` |

### Domain Events

| Event | File | Trigger |
|-------|------|---------|
| ReviewWasCreatedEvent | `src/Review/Domain/Model/Review/Event/ReviewWasCreatedEvent.php` | Review created |
| ReviewStatusWasChangedEvent | `src/Review/Domain/Model/Review/Event/ReviewStatusWasChangedEvent.php` | Status transition |
| ReviewRatingWasChangedEvent | `src/Review/Domain/Model/Review/Event/ReviewRatingWasChangedEvent.php` | Rating updated on published review |

### Validation

| Constraint | File | Purpose |
|------------|------|---------|
| ValidReviewEligibility | `src/Review/UI/Http/Validation/ValidReviewEligibilityValidator.php` | Validates order is delivered, belongs to customer, contains product, no duplicate |

### Console Commands

| Command | File | Purpose |
|---------|------|---------|
| `app:generate-reviews` | `src/Review/UI/Console/GenerateReviewsCommand.php` | Generate fake reviews for testing |

---

## Shared Kernel

### Application Layer

| Class | File | Purpose |
|-------|------|---------|
| Result | `src/Shared/Application/Result.php` | Success/failure result |
| RedirectTarget | `src/Shared/Application/RedirectTarget.php` | Redirect configuration |
| FlusherInterface | `src/Shared/Application/FlusherInterface.php` | Persistence abstraction |
| SearchCriteria | `src/Shared/Application/Search/SearchCriteria.php` | Base search criteria |

### Domain Layer

| Class | File | Purpose |
|-------|------|---------|
| AbstractUlidId | `src/Shared/Domain/ValueObject/AbstractUlidId.php` | Base ULID value object |
| AbstractIntId | `src/Shared/Domain/ValueObject/AbstractIntId.php` | Base int ID value object |
| PriceModel | `src/Shared/Domain/ValueObject/PriceModel.php` | Pretty-price rounding enum |
| ShippingMethod | `src/Shared/Domain/ValueObject/ShippingMethod.php` | Shipping options enum |
| StatusChange | `src/Shared/Domain/ValueObject/StatusChange.php` | Status transition VO |
| StockChange | `src/Shared/Domain/ValueObject/StockChange.php` | Stock change VO |
| CostChange | `src/Shared/Domain/ValueObject/CostChange.php` | Cost change VO |

### Domain Events

| Class | File | Purpose |
|-------|------|---------|
| DomainEventInterface | `src/Shared/Domain/Event/DomainEventInterface.php` | Sync event marker |
| AsyncDomainEventInterface | `src/Shared/Domain/Event/AsyncDomainEventInterface.php` | Async event marker |
| AbstractDomainEvent | `src/Shared/Domain/Event/AbstractDomainEvent.php` | Base event class |
| DomainEventProviderTrait | `src/Shared/Domain/Event/DomainEventProviderTrait.php` | Event collection trait |
| DomainEventType | `src/Shared/Domain/Event/DomainEventType.php` | Event type enum |

### Domain Services

| Service | File | Purpose |
|---------|------|---------|
| MarkupCalculator | `src/Shared/Domain/Service/Pricing/MarkupCalculator.php` | Price calculations |

### Infrastructure

| Class | File | Purpose |
|-------|------|---------|
| HasPublicUlid | `src/Shared/Infrastructure/Persistence/Doctrine/Mapping/HasPublicUlid.php` | ULID entity trait |
| DomainEventDispatcher | `src/Shared/Infrastructure/Persistence/Doctrine/EventListener/DomainEventDispatcher.php` | Event dispatch listener |
| DoctrineFlusher | `src/Shared/Infrastructure/Persistence/Doctrine/DoctrineFlusher.php` | Flusher implementation |
| CurrentUserProvider | `src/Shared/Infrastructure/Security/CurrentUserProvider.php` | Current user access |
| Paginator | `src/Shared/Infrastructure/Persistence/Search/Paginator.php` | Pagination service |

### FormFlow

| Class | File | Purpose |
|-------|------|---------|
| FormFlow | `src/Shared/UI/Http/FormFlow/FormFlow.php` | Create/update flow |
| CommandFlow | `src/Shared/UI/Http/FormFlow/CommandFlow.php` | Command execution flow |
| DeleteFlow | `src/Shared/UI/Http/FormFlow/DeleteFlow.php` | Delete with confirmation |
| SearchFlow | `src/Shared/UI/Http/FormFlow/SearchFlow.php` | Paginated index flow |
| ShowFlow | `src/Shared/UI/Http/FormFlow/ShowFlow.php` | Detail view flow |
| FlowContext | `src/Shared/UI/Http/FormFlow/View/FlowContext.php` | Flow configuration |
| TurboAwareRedirector | `src/Shared/UI/Http/FormFlow/Redirect/TurboAwareRedirector.php` | Turbo-compatible redirects |

### Twig Components

| Component | File | Purpose |
|-----------|------|---------|
| Button | `src/Shared/UI/Twig/Components/Button.php` | Styled button |
| Card | `src/Shared/UI/Twig/Components/Card.php` | Content card |
| Toast | `src/Shared/UI/Twig/Components/Toast.php` | Flash message toast |
| StatusIcon | `src/Shared/UI/Twig/Components/StatusIcon.php` | Status indicator icon |
| ProductImage | `src/Shared/UI/Twig/Components/ProductImage.php` | Product image display |

### Value Resolver

| Class | File | Purpose |
|-------|------|---------|
| PublicIdResolver | `src/Shared/UI/Http/ValueResolver/PublicIdResolver.php` | Resolve entity by ULID |

---

## Configuration Files

| File | Purpose |
|------|---------|
| `config/packages/doctrine.yaml` | Database and ORM |
| `config/packages/security.yaml` | Authentication |
| `config/packages/messenger.yaml` | Queue configuration |
| `config/packages/cache.yaml` | Caching strategy |
| `config/services.yaml` | Service definitions |

---

## Test Files

| Directory | Content |
|-----------|---------|
| `tests/Shared/Factory/` | Foundry factories (25 files) |
| `tests/Shared/Story/` | Reusable test stories |
| `tests/{Context}/Domain/` | Unit tests |
| `tests/{Context}/Application/Handler/` | Integration tests |
| `tests/{Context}/UI/` | Functional tests |

---

## Key Entry Points by Use Case

### "I need to understand pricing"
1. `src/Shared/Domain/Service/Pricing/MarkupCalculator.php`
2. `src/Shared/Domain/ValueObject/PriceModel.php`
3. `src/Catalog/Domain/Model/Product/Product.php` (getActiveMarkup, recalculatePrice)
4. `src/Pricing/Application/Listener/` (cascade listeners)

### "I need to understand order allocation"
1. `src/Purchasing/Application/Service/OrderAllocator.php`
2. `src/Purchasing/Application/Service/OrderItemAllocator.php`
3. `src/Catalog/Domain/Model/Product/Product.php` (getBestSourceWithMinQuantity)
4. `src/Order/Domain/Model/Order/CustomerOrderItem.php` (getOutstandingQty)

### "I need to understand status flows"
1. `src/Order/Domain/Model/Order/OrderStatus.php`
2. `src/Purchasing/Domain/Model/PurchaseOrder/PurchaseOrderStatus.php`
3. Entity methods: `generateStatus()`, `canTransitionTo()`

### "I need to understand product reviews"
1. `src/Review/Domain/Model/Review/ProductReview.php` (entity, status transitions, moderation)
2. `src/Review/Domain/Model/Review/ReviewStatus.php` (status enum, allowed transitions)
3. `src/Review/Application/Listener/ReviewSummaryUpdater.php` (automatic summary recalculation)
4. `src/Review/UI/Http/Validation/ValidReviewEligibilityValidator.php` (eligibility rules)

### "I need to add a new entity"
1. Create entity in `src/{Context}/Domain/Model/{Entity}/`
2. Create `{Entity}PublicId` value object
3. Add `HasPublicUlid` trait
4. Create repository interface + Doctrine implementation
5. Create command + handler
6. Create form + mapper
7. Create controller using FormFlow
8. Create factory in `tests/Shared/Factory/`
