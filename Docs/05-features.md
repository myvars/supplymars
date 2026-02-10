# Feature Documentation

This document provides feature-by-feature documentation for developers working with SupplyMars. Each section covers the purpose, workflows, entry points, and business rules for a specific capability.

## Products & Categorization

### Purpose

The Catalog context manages the product information displayed to customers and used throughout the system. Products aggregate supplier offerings into a unified catalog.

### Main Workflows

**Creating a Product:**
1. Navigate to `/catalog/product/new`
2. Fill required fields: name, manufacturer part number, category, subcategory, manufacturer
3. Product created with default pricing (inherits from category)
4. Optionally set product-level markup or price model overrides

**Mapping Supplier Products:**
1. Create SupplierProducts in Purchasing context
2. Map via manufacturer part number matching or manual association
3. System automatically selects best source and recalculates pricing

**Managing Categories:**
1. Categories define base markup, VAT rate, and price model
2. Subcategories can override markup and price model
3. Changes cascade to affected products via domain events

### Entry Points

| Action | Controller | Route |
|--------|------------|-------|
| List products | `ProductController::index` | `app_catalog_product_index` |
| Create product | `ProductController::new` | `app_catalog_product_new` |
| Edit product | `ProductController::edit` | `app_catalog_product_edit` |
| View product | `ProductController::show` | `app_catalog_product_show` |
| Delete product | `ProductController::delete` | `app_catalog_product_delete` |

**Key files:**
- `src/Catalog/UI/Http/Controller/ProductController.php`
- `src/Catalog/Application/Handler/Product/CreateProductHandler.php`
- `src/Catalog/Domain/Model/Product/Product.php`

### Business Rules

1. **Unique part numbers:** `mfrPartNumber` must be unique across products
2. **Category required:** Every product must have a category (for VAT rate)
3. **Subcategory required:** Every product must have a subcategory
4. **Stock derivation:** Product stock comes from active supplier source
5. **Pricing cascade:** Category/subcategory changes trigger selective recalculation

---

## Suppliers & Supplier Products

### Purpose

The Purchasing context manages supplier relationships and their product offerings. Suppliers provide inventory sources with independent pricing and stock levels.

### Main Workflows

**Creating a Supplier:**
1. Navigate to `/purchasing/supplier/new`
2. Set name and active status
3. Optionally designate as warehouse (one per system)

**Creating Supplier Products:**
1. Navigate to `/purchasing/supplier-product/new`
2. Associate with supplier, category, subcategory, manufacturer
3. Set cost, stock, lead time
4. Map to catalog product (or leave unmapped)

**Stock/Price Updates:**
1. Manual: Edit supplier product directly
2. Automated: `app:update-supplier-stock` command simulates feeds
3. Events trigger product pricing recalculation

### Entry Points

| Action | Controller | Route |
|--------|------------|-------|
| List suppliers | `SupplierController::index` | `app_purchasing_supplier_index` |
| Create supplier | `SupplierController::new` | `app_purchasing_supplier_new` |
| List supplier products | `SupplierProductController::index` | `app_purchasing_supplier_product_index` |
| Create supplier product | `SupplierProductController::new` | `app_purchasing_supplier_product_new` |

**Key files:**
- `src/Purchasing/UI/Http/Controller/SupplierController.php`
- `src/Purchasing/Domain/Model/Supplier/Supplier.php`
- `src/Purchasing/Domain/Model/SupplierProduct/SupplierProduct.php`

### Business Rules

1. **One warehouse:** Only one supplier can have `isWarehouse = true`
2. **Active filtering:** Inactive suppliers excluded from sourcing
3. **Cost validation:** Cost must be positive for viable sourcing
4. **Stock events:** Stock/cost changes raise domain events
5. **Lead time:** Used for due date calculation on purchase orders

---

## Customer Orders

### Purpose

The Order context manages customer purchases from initial creation through fulfillment. Orders contain items that reference catalog products.

### Main Workflows

**Creating an Order:**
1. Navigate to `/order/new`
2. Select customer and shipping method
3. Order created in PENDING status

**Adding Order Items:**
1. View order detail page
2. Add products with quantities
3. Items capture current product price

**Allocating to Suppliers:**
1. Trigger allocation (manual or via command)
2. System creates PurchaseOrders per supplier
3. Order moves to PROCESSING status

**Order Lifecycle:**
```
PENDING → PROCESSING → SHIPPED → DELIVERED
                    ↘ CANCELLED
```

### Entry Points

| Action | Controller | Route |
|--------|------------|-------|
| List orders | `OrderController::index` | `app_order_order_index` |
| Create order | `OrderController::new` | `app_order_order_new` |
| View order | `OrderController::show` | `app_order_order_show` |
| Cancel order | `OrderController::cancel` | `app_order_order_cancel` |

**Key files:**
- `src/Order/UI/Http/Controller/OrderController.php`
- `src/Order/Application/Handler/CreateOrderHandler.php`
- `src/Order/Domain/Model/Order/CustomerOrder.php`
- `src/Order/Domain/Model/Order/CustomerOrderItem.php`

### Business Rules

1. **Price capture:** Item prices frozen at creation time
2. **Edit window:** Orders editable only in PENDING/PROCESSING status
3. **Cancel restrictions:** Can only cancel PENDING orders
4. **Status derivation:** Order status = minimum item status
5. **Locking:** Orders can be locked by user to prevent concurrent edits

---

## Purchase Orders

### Purpose

Purchase orders represent fulfillment requests to suppliers. A single customer order can generate multiple POs across different suppliers.

### Main Workflows

**Automatic Generation:**
1. Order allocation creates POs per supplier
2. PO items link to customer order items
3. Outstanding quantities tracked for split fulfillment

**Status Progression:**
```
PENDING → PROCESSING → ACCEPTED → SHIPPED → DELIVERED
                    ↘ REJECTED → REFUNDED
                    ↘ CANCELLED
```

**Handling Rejections:**
1. Supplier rejects PO item
2. Item moves to REJECTED status
3. `app:refund-purchase-orders` processes refund and re-allocates

### Entry Points

| Action | Controller | Route |
|--------|------------|-------|
| List POs | `PurchaseOrderController::index` | `app_purchasing_purchase_order_index` |
| View PO | `PurchaseOrderController::show` | `app_purchasing_purchase_order_show` |

**Key files:**
- `src/Purchasing/UI/Http/Controller/PurchaseOrderController.php`
- `src/Purchasing/Domain/Model/PurchaseOrder/PurchaseOrder.php`
- `src/Purchasing/Domain/Model/PurchaseOrder/PurchaseOrderItem.php`

### Business Rules

1. **Multi PO per supplier:** Each supplier can have multiple editable POs per order
2. **Quantity limits:** Combined PO item qty cannot exceed order item outstanding qty
3. **Status cascade:** PO status derived from minimum item status
4. **Edit restrictions:** Only PENDING items allow quantity changes
5. **Status transitions:** Enforced via state machine

---

## Stock Simulation

### Purpose

Stock simulation maintains realistic inventory levels across suppliers without requiring actual EDI feeds.

### Main Workflows

**Automated Stock Fluctuation:**
```bash
# Simulates real-world inventory changes
symfony console app:update-supplier-stock 50
```

**Simulation Logic:**
1. If stock ≤ 20: Replenish (add 0-100 units)
2. Otherwise: Decrease by up to 10% of current level
3. Cost varies by ±10%

**Stock Events:**
1. Stock change raises `SupplierProductStockWasChangedEvent`
2. Audit listener logs changes
3. Product may recalculate active source

### Entry Points

| Action | Type | Command |
|--------|------|---------|
| Update stock | Console | `app:update-supplier-stock {count}` |
| Reset stock | Console | `app:reset-supplier-stock {supplierId}` |
| Activate products | Console | `app:activate-supplier-products {count}` |

**Key files:**
- `src/Purchasing/UI/Console/UpdateSupplierStockCommand.php`
- `src/Purchasing/Domain/Model/SupplierProduct/SupplierProduct.php`

### Business Rules

1. **Replenish threshold:** 20 units triggers replenishment
2. **Cost variance:** ±10% random variance
3. **Stock variance:** Up to 10% decrease per cycle
4. **Event logging:** All changes tracked via domain events

---

## Pricing Engine

### Purpose

The Pricing context manages VAT rates and orchestrates price cascades when underlying costs or margins change.

### Main Workflows

**Editing Product Pricing:**
1. Navigate to `/pricing/{id}/cost`
2. View current cost breakdown
3. Edit product/subcategory/category markup
4. Changes trigger recalculation

**VAT Rate Management:**
1. Create/edit VAT rates via `/pricing/vat/`
2. Rate changes cascade to all products in affected categories

**Price Model Selection:**
1. Set at category, subcategory, or product level
2. Higher specificity wins
3. Changes cascade via domain events

### Entry Points

| Action | Controller | Route |
|--------|------------|-------|
| View product pricing | `PricingController::cost` | `app_pricing_cost` |
| Edit product markup | `PricingController::editProductCost` | `app_pricing_cost_product_edit` |
| Edit category markup | `PricingController::editCategoryCost` | `app_pricing_cost_category_edit` |
| List VAT rates | `VatRateController::index` | `app_pricing_vat_rate_index` |

**Key files:**
- `src/Pricing/UI/Http/Controller/PricingController.php`
- `src/Shared/Domain/Service/Pricing/MarkupCalculator.php`
- `src/Pricing/Application/Listener/CategoryPricingWasChanged.php`

### Business Rules

1. **Markup inheritance:** Product → Subcategory → Category
2. **Price model inheritance:** Same hierarchy
3. **VAT from category:** All products in category share VAT rate
4. **Selective cascade:** Only affected products recalculated
5. **Precision:** bcmath used for decimal calculations

---

## Customer Insights

### Purpose

The Customer Insights feature provides analytics on customer behavior, geographic distribution, and segmentation to support business intelligence and marketing decisions.

### Main Workflows

**Daily ETL Pipeline:**
1. `app:calculate-customer-sales` runs daily via cron
2. Aggregates order data into CustomerSales and CustomerActivitySales records
3. `app:calculate-customer-sales-summary` pre-computes summaries for fast dashboard loads

**Insights Dashboard:**
1. Navigate to `/dashboard/report/customer/insights`
2. View top customers by revenue, activity trends
3. Filter by duration (7d, 30d, 90d, 365d)

**Geographic Analysis:**
1. Navigate to `/dashboard/report/customer/geographic`
2. View pie chart of sales distribution by city
3. Useful for regional marketing decisions

**Segment Analysis:**
1. Navigate to `/dashboard/report/customer/segments`
2. View customer categorisation: NEW, RETURNING, LOYAL, LAPSED
3. See revenue contribution by segment

**Customer Profile Insights:**
1. View any customer detail page
2. Insights card shows:
   - Lifetime revenue and order count
   - Revenue rank vs other customers
   - Customer segment classification
   - Review activity

### Entry Points

| Route | Controller | Purpose |
|-------|------------|---------|
| `/dashboard/report/customer/insights` | `DashboardController::customerInsights` | Main insights dashboard |
| `/dashboard/report/customer/geographic` | `DashboardController::customerGeographic` | Geographic breakdown |
| `/dashboard/report/customer/segments` | `DashboardController::customerSegments` | Segment analysis |

**Key files:**
- `src/Reporting/UI/Http/Controller/DashboardController.php`
- `src/Reporting/Application/Handler/CustomerInsightsReportHandler.php`
- `src/Reporting/Application/Handler/CustomerGeographicReportHandler.php`
- `src/Reporting/Application/Handler/CustomerSegmentReportHandler.php`
- `src/Customer/Application/Handler/CustomerProfileInsightsHandler.php`

### Business Rules

**Customer Segmentation Logic:**
- **NEW:** 0-1 lifetime orders
- **RETURNING:** 2-3 lifetime orders
- **LOYAL:** 4+ lifetime orders
- **LAPSED:** Any segment with no activity in 60+ days

**Two-Layer Reporting Architecture:**
- Daily granular records (CustomerSales, CustomerActivitySales)
- Pre-aggregated summaries for dashboard performance (CustomerSalesSummary, CustomerGeographicSummary, CustomerSegmentSummary)

---

## Purchase Order Rewind

### Purpose

Allows administrators to reset a purchase order back to pending status, useful for error recovery or testing scenarios.

### Main Workflows

**Manual Rewind via UI:**
1. Navigate to PO detail page
2. Click "Rewind" button in actions area
3. Confirm action in modal
4. PO and all items reset to PENDING
5. Audit logs for the PO are cleared
6. Parent customer order status regenerated

**Bulk Rewind via Console:**
```bash
# Find and rewind POs with inconsistent item statuses
symfony console app:rewind-mixed-status-purchase-orders
```

### Entry Points

| Action | Controller | Route |
|--------|------------|-------|
| Rewind confirm | `PurchaseOrderController::rewindConfirm` | `app_purchasing_purchase_order_rewind_confirm` |
| Rewind execute | `PurchaseOrderController::rewind` | `app_purchasing_purchase_order_rewind` |

**Key files:**
- `src/Purchasing/UI/Http/Controller/PurchaseOrderController.php`
- `src/Purchasing/Application/Handler/RewindPurchaseOrderHandler.php`
- `src/Purchasing/Domain/Service/PurchaseOrderRewindService.php`

### Business Rules

1. **Status reset:** PO and all items return to PENDING status
2. **Audit cleanup:** Status change logs for the PO are removed
3. **Cascade effect:** Parent customer order status is regenerated
4. **Stock unchanged:** Inventory levels are not affected (manual adjustment may be needed)
5. **Irreversible context:** Use carefully as it erases status history

---

## Reporting & Dashboards

### Purpose

The Reporting context provides business intelligence through pre-aggregated sales data and interactive dashboards.

### Main Workflows

**Dashboard Overview:**
1. Navigate to `/dashboard/`
2. View today's orders, sales, and margins
3. Compare against week-ago baseline
4. See action items (overdue orders, rejected POs)

**Detailed Reports:**
- Product Sales: `/dashboard/report/product/sales`
- Order Summary: `/dashboard/report/order/summary`
- Overdue Orders: `/dashboard/report/overdue/orders`

**Data Aggregation:**
```bash
# Daily aggregation commands
symfony console app:calculate-product-sales 7
symfony console app:calculate-order-sales 7
```

### Entry Points

| Action | Controller | Route |
|--------|------------|-------|
| Dashboard | `DashboardController::show` | `app_reporting_dashboard` |
| Product sales | `DashboardController::productSales` | `app_reporting_dashboard_product_sales` |
| Order summary | `DashboardController::orderSummary` | `app_reporting_dashboard_order_summary` |
| Overdue orders | `DashboardController::overdueOrders` | `app_reporting_dashboard_overdue_orders` |

**Key files:**
- `src/Reporting/UI/Http/Controller/DashboardController.php`
- `src/Reporting/UI/Http/Dashboard/DashboardViewer.php`
- `src/Reporting/Application/Handler/CalculateProductSalesHandler.php`

### Business Rules

1. **Two-layer aggregation:** Daily records + pre-computed summaries
2. **Duration options:** Today, Last 7, Last 30, MTD
3. **Chart data:** Bar charts for trends, doughnut for status breakdown
4. **Filter dimensions:** Product, category, subcategory, manufacturer, supplier

---

## PO Item Performance Report

### Purpose

The PO Item Performance Report tracks purchase order item profitability and fulfillment metrics across suppliers and products. It helps identify which supplier-product combinations yield the best margins and which have fulfillment issues.

### Main Workflows

**Viewing PO Item Performance:**
1. Navigate to Dashboard → Reports
2. Select PO Item Performance report
3. Filter by date range (7d, 30d, 90d, 365d)
4. Sort by profit, status, product, or supplier
5. View summary totals and paginated item details

### Entry Points

| Route | Handler | Purpose |
|-------|---------|---------|
| `/dashboard/report/po-item/performance` | `PoItemPerformanceReportHandler` | PO item profitability analysis |

**Key files:**
- `src/Reporting/Application/Handler/Report/PoItemPerformanceReportHandler.php`
- `src/Reporting/Application/Report/PoItemPerformanceReportCriteria.php`

### Business Rules

1. **Date filtering:** Uses SalesDuration trait for consistent date range options
2. **Sort options:** profit, status, product.name, supplier.name
3. **Default sort:** By profit ascending (lowest first to identify problem items)
4. **Summary aggregation:** Provides totals alongside paginated details

---

## Image/Media Handling

### Purpose

Product images are managed via the Catalog context with storage adapters for local filesystem (development) or S3 (production).

### Main Workflows

**Uploading Images:**
1. Navigate to product edit page
2. Upload images via file input
3. Images stored with position ordering
4. First image becomes primary

**Storage Configuration:**
- Development: Local filesystem (`public/uploads/`)
- Production: AWS S3 bucket

### Entry Points

| Action | Type | Notes |
|--------|------|-------|
| Upload image | Form submission | Via ProductImageType |
| Delete image | Controller action | Raises ProductImageWasDeleted event |
| Reorder images | Controller action | Updates position values |

**Key files:**
- `src/Catalog/Domain/Model/ProductImage/ProductImage.php`
- `src/Shared/Infrastructure/FileStorage/UploadHelper.php`
- `config/packages/oneup_flysystem.yaml`

### Business Rules

1. **Position ordering:** Images sorted by position (1-based)
2. **Size limits:** Max 2MB per image
3. **MIME validation:** Only image/* types allowed
4. **Cascade delete:** Images deleted with product
5. **Thumbnail generation:** Via Imagine bundle (inferred)

---

## Audit Logging

### Purpose

The Audit context tracks significant changes to entities, particularly status transitions and stock changes.

### Main Workflows

**Status Change Logging:**
1. Entity status changes raise events
2. Audit listener captures change details
3. Stored in `StatusChangeLog` entity

**Stock Change Logging:**
1. Supplier product stock/cost changes raise events
2. Audit listener captures before/after values
3. Stored in `SupplierStockChangeLog` entity

### Entry Points

Audit logs are primarily accessed via:
- Entity detail pages (shows history)
- Admin reporting (aggregate views)

**Key files:**
- `src/Audit/Domain/Model/StatusChangeLog/StatusChangeLog.php`
- `src/Audit/Domain/Model/SupplierStockChangeLog/SupplierStockChangeLog.php`
- `src/Audit/Application/Listener/` (event listeners)

### Business Rules

1. **Immutable records:** Audit logs are append-only
2. **User tracking:** Changes associated with acting user
3. **Timestamp precision:** DateTimeImmutable for accuracy
4. **Event-driven:** No direct writes, only via listeners

---

## Product Reviews

### Purpose

The Review context provides a customer feedback and moderation system. Reviews are linked to delivered orders and moderated before publication. Published reviews generate per-product summaries with average ratings and rating distribution.

### Main Workflows

**Creating a Review:**
1. Navigate to `/review/new`
2. Provide customer ID, product ID, and order ID
3. Set rating (1-5), optional title and body
4. System validates eligibility (delivered order, correct customer/product, no duplicates)
5. Review created in PENDING status

**Moderating Reviews:**
1. View pending review detail page
2. Approve to publish, or reject with reason and optional notes
3. Approved reviews appear on the product page
4. Rejected reviews are terminal and cannot be recovered

**Product Review Summary:**
1. Summaries recalculate automatically on review creation, status change, or rating change
2. Product detail page shows average rating, distribution chart, and latest reviews

### Entry Points

| Action | Controller | Route |
|--------|------------|-------|
| List reviews | `ReviewController::index` | `app_review_index` |
| Filter reviews | `ReviewController::filter` | `app_review_search_filter` |
| Create review | `ReviewController::new` | `app_review_new` |
| View review | `ReviewController::show` | `app_review_show` |
| Edit review | `ReviewController::edit` | `app_review_edit` |
| Delete confirm | `ReviewController::deleteConfirm` | `app_review_delete_confirm` |
| Delete review | `ReviewController::delete` | `app_review_delete` |
| Approve review | `ReviewController::approve` | `app_review_approve` |
| Reject review | `ReviewController::reject` | `app_review_reject` |
| Hide review | `ReviewController::hide` | `app_review_hide` |
| Republish review | `ReviewController::republish` | `app_review_republish` |

**Key files:**
- `src/Review/UI/Http/Controller/ReviewController.php`
- `src/Review/Application/Handler/CreateReviewHandler.php`
- `src/Review/Domain/Model/Review/ProductReview.php`
- `src/Review/Domain/Model/ReviewSummary/ProductReviewSummary.php`
- `src/Review/Application/Listener/ReviewSummaryUpdater.php`

### Business Rules

1. **Eligibility:** Order must be DELIVERED, belong to the customer, and contain the product
2. **Status transitions:** PENDING can transition to PUBLISHED or REJECTED; PUBLISHED can transition to HIDDEN; HIDDEN can transition to PUBLISHED; REJECTED is terminal
3. **Editable states:** Only PENDING and PUBLISHED reviews can be edited
4. **Summary automation:** Product review summaries recalculate on any review change affecting published stats
5. **Rating validation:** Rating must be an integer from 1 to 5
6. **One per customer and product:** A customer can only submit one review per product (enforced by unique constraint)

---

## Support Pools & Tickets

### Purpose

The Note context provides an internal support system for managing customer queries and staff communication. Pools organize tickets by topic, and tickets contain threaded messages with visibility controls.

### Main Workflows

**Pool Management:**
1. Navigate to `/note/pool/new`
2. Set name, description, active status, and customer visibility
3. Staff can subscribe to pools to filter their ticket view
4. Toggle subscriptions via the pool detail page

**Ticket Lifecycle:**
1. Create ticket at `/note/ticket/new` — select pool, customer, subject, and initial message
2. Staff reply via `/note/ticket/{id}/reply` — choose PUBLIC or INTERNAL visibility
3. Close ticket — adds system message audit trail, status transitions to CLOSED
4. Reopen ticket — adds system message, status transitions back to OPEN
5. Reassign ticket to a different pool
6. Snooze ticket until a future date (hidden from default view until then)

**Message Visibility Model:**
- **PUBLIC** — visible to both staff and customers
- **INTERNAL** — visible to staff only, for private notes and discussion

**System Messages:**
- Generated automatically for close, reopen, and reassign actions
- Provide audit trail within the ticket conversation
- Cannot be deleted

### Entry Points

| Action | Controller | Route |
|--------|------------|-------|
| List pools | `PoolController::index` | `app_note_pool_index` |
| Create pool | `PoolController::new` | `app_note_pool_new` |
| Edit pool | `PoolController::edit` | `app_note_pool_edit` |
| Delete pool | `PoolController::delete` | `app_note_pool_delete` |
| Toggle subscription | `PoolController::subscribe` | `app_note_pool_subscribe` |
| List tickets | `TicketController::index` | `app_note_ticket_index` |
| Create ticket | `TicketController::new` | `app_note_ticket_new` |
| View ticket | `TicketController::show` | `app_note_ticket_show` |
| Reply to ticket | `TicketController::reply` | `app_note_ticket_reply` |
| Close ticket | `TicketController::close` | `app_note_ticket_close` |
| Reopen ticket | `TicketController::reopen` | `app_note_ticket_reopen` |
| Reassign ticket | `TicketController::reassign` | `app_note_ticket_reassign` |
| Snooze ticket | `TicketController::toggleSnooze` | `app_note_ticket_toggle_snooze` |
| Delete message | `TicketController::deleteMessage` | `app_note_ticket_message_delete` |

**Key files:**
- `src/Note/UI/Http/Controller/PoolController.php`
- `src/Note/UI/Http/Controller/TicketController.php`
- `src/Note/Domain/Model/Pool/Pool.php`
- `src/Note/Domain/Model/Ticket/Ticket.php`
- `src/Note/Domain/Model/Message/Message.php`

### Business Rules

1. **Pool deletion cascades:** Deleting a pool removes all associated tickets and messages
2. **Ticket status transitions:** OPEN ↔ REPLIED ↔ CLOSED, with guards on invalid transitions
3. **Reply status effect:** Staff reply sets REPLIED; customer reply sets OPEN; closed tickets remain CLOSED
4. **Snooze filtering:** Snoozed tickets hidden from default index unless explicitly included
5. **Original message protection:** The first message in a ticket cannot be deleted
6. **System message protection:** System-generated messages cannot be deleted
7. **Message count tracking:** `messageCount` and `lastMessageAt` are maintained on the ticket for sorting

---

## FormFlow Pattern

### Purpose

FormFlow is a thin orchestration layer that standardizes controller behavior across create, update, delete, and search operations.

### Flow Types

| Flow | Purpose | Key Method |
|------|---------|------------|
| `FormFlow` | Create/update with forms | `form()` |
| `CommandFlow` | Direct command execution | `execute()` |
| `DeleteFlow` | Delete with confirmation | `deleteConfirm()`, `delete()` |
| `SearchFlow` | Paginated index pages | `search()` |

### Usage Example

```php
// src/Catalog/UI/Http/Controller/ManufacturerController.php

#[Route('/catalog/manufacturer/new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    CreateManufacturerMapper $mapper,
    CreateManufacturerHandler $handler,
    FormFlow $flow,
): Response {
    return $flow->form(
        request: $request,
        formType: ManufacturerType::class,
        data: new ManufacturerForm(),
        mapper: $mapper,
        handler: $handler,
        context: FlowContext::forCreate(self::MODEL),
    );
}
```

**Key files:**
- `src/Shared/UI/Http/FormFlow/FormFlow.php`
- `src/Shared/UI/Http/FormFlow/View/FlowContext.php`
- `src/Shared/UI/Http/FormFlow/Redirect/TurboAwareRedirector.php`

### Benefits

1. **Consistency:** All forms behave the same way
2. **Turbo support:** Automatic Turbo Stream responses
3. **Flash messages:** Standardized success/error messaging
4. **Redirect handling:** Configurable success routes
5. **Validation:** Integrated with Symfony Validator
