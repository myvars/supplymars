# Features

The business rules of each feature, with the files that hold them. Read it before changing a feature's behaviour. Screen-by-screen steps are in the [UserManual](UserManual/README.md), routes in `symfony console debug:router`, the reasons in the linked ADRs. Sourcing, allocation, statuses and pricing are in [04-sourcing-and-pricing.md](04-sourcing-and-pricing.md).

## Applies to Every Feature


- **Delete rights.** Five delete handlers check `ROLE_SUPER_ADMIN`: product, supplier, supplier product, VAT rate and pool. Every other delete is open to any staff user.
- **Deletes that hit a foreign key fail.** Nothing checks for or cascades to dependent rows, so the flush throws. A product can't be deleted while it has images, supplier products or order items, a VAT rate while a category uses it, a pool while it has tickets.
- **Inline editing.** Names (and a customer's `fullName`) are edited in place on show pages. The list is `symfony console debug:router | grep inline`; the pattern is in [patterns/InlineEdit/README.md](patterns/InlineEdit/README.md).

## Catalog

- A product needs a name, a manufacturer part number, a category, a subcategory and a manufacturer. VAT comes from the category.
- `mfrPartNumber` is not unique. Mapping a supplier product (`app_purchasing_supplier_product_map`) finds or creates the product, manufacturer, category and subcategory by **name** (`SupplierProductMappingService`).
- A product's stock, cost and lead time are copies of its active source's values. See [04 Stock](04-sourcing-and-pricing.md#stock).
- A manufacturer's Active flag is a label. Nothing filters on it.
- **Images:** JPG, PNG, GIF or WebP, up to 2 MiB, checked on the server by extension and content (`CreateProductImageHandler`; upload limits in [08-security.md](08-security.md)). Ordered by `position` from 1; the first is the product's main image (`Product::getFirstImage()`). Deleting one raises `ProductImageWasDeletedEvent`, whose listener deletes the file and its LiipImagine thumbnails.
- Files are stored through Flysystem (`products_fs`): `public/` locally, S3 in production (`config/packages/oneup_flysystem.yaml`).

Key files: `src/Catalog/Domain/Model/Product/Product.php`, `src/Catalog/Application/Handler/ProductImage/CreateProductImageHandler.php`, `src/Catalog/Application/Listener/ProductImageWasDeleted.php`, `src/Purchasing/Application/Service/SupplierProductMappingService.php`.

UI steps: [UserManual 03](UserManual/03-products.md), [04](UserManual/04-categories.md), [16](UserManual/16-manufacturers.md).

## Customers

- A user is staff when `isStaff` is set, which adds `ROLE_ADMIN`. Everything except the routes marked `PUBLIC_ACCESS` in `access_control` (`config/packages/security.yaml`: public pages, login, registration, password reset, verify, help, health, the public catalog API and `/api/doc`) needs `ROLE_ADMIN`. `ROLE_SUPER_ADMIN` includes `ROLE_ADMIN`.
- `/register` creates a non-staff user, who can sign in but can't open any back-office page.
- Email verification is not enforced at login. `/verify/resend` exists, but nothing links to it.
- Only a super admin can edit a staff account (`CustomerVoter`, asked by `UpdateCustomerHandler`, the inline name route and the templates). Making a user staff sends the Admin Access Granted email.
- Email is unique (`UniqueEntity` on `User`).
- **Emails:** Inky templates with inline CSS (`templates/shared/email/emailBase.html.twig`, `assets/styles/email.css`). `MailerHelper` has one method per email: verify email, reset password, admin access granted. `app:send-test-emails` (dev only) sends each one to Mailpit.

Key files: `src/Customer/Domain/Model/User/User.php`, `src/Customer/Application/Handler/UpdateCustomerHandler.php`, `src/Customer/Infrastructure/Mailer/MailerHelper.php`.

UI steps: [UserManual 07](UserManual/07-customers.md).

## Orders

- Status rules, allocation and how an order's status is derived from its items: [04 Statuses](04-sourcing-and-pricing.md#statuses) and [04 Allocation](04-sourcing-and-pricing.md#allocation).
- An order item copies the product's price when added. A product can be on two lines at two prices ([04 Prices on orders](04-sourcing-and-pricing.md#prices-on-orders)).
- Items can be added or removed only while the order is PENDING or PROCESSING (`OrderStatus::allowEdit()`, checked in `CustomerOrder`). Editing an item is limited by the item's own status in the template only; `UpdateOrderItemHandler` doesn't check it.
- An order can be cancelled only while PENDING, and only if no item has quantity on a purchase order (`CustomerOrder::cancelOrder()`, `CustomerOrderItem::allowCancel()`). `canTransitionTo()` would allow PROCESSING → CANCELLED, but `allowCancel()` doesn't.
- **Lock:** a "someone is working on this" flag holding the user who set it. It doesn't stop anyone editing the order, and any staff user can toggle it (`LockOrderHandler`). The scheduled jobs skip locked orders: build, accept, ship and deliver all filter on `orderLock IS NULL`. Releasing a lock someone else holds sends them a toast, on whatever page they have open.
- **Live page:** the order page updates while open, without reloading, when its items, purchase orders, statuses, totals or lock change, whoever or whatever changed them ([ADR 017](adr/017-live-updates-with-mercure.md)).
- **Demo orders:** a random customer, shipping method and products, reference `DEMO-…`. At most 10 a day across all users, counted by the reference prefix (`CreateDemoOrderHandler::DAILY_LIMIT`).
- Creating an order by hand (`/order/new`) is super admin only.
- `OrderController` names are misleading: `delete()` cancels the order, `cancel()` toggles the lock, `process()` allocates.

Key files: `src/Order/Domain/Model/Order/CustomerOrder.php`, `src/Order/Domain/Model/Order/CustomerOrderItem.php`, `src/Order/Application/Handler/CreateDemoOrderHandler.php`, `src/Order/Application/Service/DemoOrderCreator.php`.

UI steps: [UserManual 08](UserManual/08-orders.md), [09](UserManual/09-order-items.md).

## Purchasing

- **Suppliers:** a supplier has a name, a colour scheme (used in charts) and an Active flag. An inactive supplier's products are never a source. Warehouse flag: see [04](04-sourcing-and-pricing.md#suppliers-and-supplier-products)).
- **Supplier products:** cost, stock and lead time per supplier. A cost of 0 or no stock means "not a source". The lead time is copied to the product; purchase order due dates come from the order's shipping method, not the lead time.
- **One editable PO per supplier per order.** Allocation reuses the order's PENDING PO for that supplier and creates a new one only when there is none (`EditablePurchaseOrderProvider`).
- Only PENDING PO items can change quantity. A PO item can't exceed its line's outstanding quantity ([04 Allocation](04-sourcing-and-pricing.md#allocation)).
- PO statuses are an enum with `canTransitionTo()`, not Symfony Workflow. The table is in [04 Statuses](04-sourcing-and-pricing.md#statuses).
- Rejected items are refunded and reallocated by `app:refund-purchase-orders` (live only, nightly).
- **Rewind** (confirm page on the PO; refused when an item is REFUNDED or CANCELLED): every item goes back to PENDING, the PO's and its items' status change logs are deleted, and the order's status is derived again. Stock is not touched, and the erased history can't be recovered. `app:rewind-mixed-status-purchase-orders` rewinds POs whose items have mixed statuses, skipping those it may not rewind.

Key files: `src/Purchasing/Domain/Model/PurchaseOrder/PurchaseOrder.php`, `src/Purchasing/Application/Service/EditablePurchaseOrderProvider.php`, `src/Purchasing/Application/Service/PurchaseOrderRewindService.php`, `src/Purchasing/Domain/Model/SupplierProduct/SupplierProduct.php`.

UI steps: [UserManual 05](UserManual/05-suppliers.md), [06](UserManual/06-supplier-products.md), [10](UserManual/10-purchase-orders.md).

## Stock Simulation

`app:update-supplier-stock` stands in for supplier stock feeds (every 15 minutes, see [06 Scheduled Jobs](06-operations.md#scheduled-jobs)). For each supplier product:

- Low stock is replenished and its cost moved; otherwise stock falls a little. The numbers are in [09-cli-reference.md](09-cli-reference.md).
- The mapped product's active source, stock, cost and price follow each change ([04 Stock](04-sourcing-and-pricing.md#stock)).

`app:reset-supplier-stock` and `app:activate-supplier-products` are in [09-cli-reference.md](09-cli-reference.md). Why the platform simulates: [ADR 003](adr/003-simulation-first-design.md).

Key files: `src/Purchasing/UI/Console/UpdateSupplierStockCommand.php`, `src/Purchasing/Domain/Model/SupplierProduct/SupplierProduct.php`.

UI steps: [UserManual 11](UserManual/11-stock.md), [14](UserManual/14-simulations.md).

## Pricing

All rules (markup and price model levels, VAT, rounding, which events reprice what) are in [04-sourcing-and-pricing.md](04-sourcing-and-pricing.md#pricing-cascade). The Pricing screens (`PricingController`) edit the markup and price model at product, subcategory and category level; VAT rates are under `/vat-rate/`.

Key files: `src/Pricing/UI/Http/Controller/PricingController.php`, `src/Shared/Domain/Service/Pricing/MarkupCalculator.php`, `src/Pricing/Application/Listener/`.

Why: [ADR 004](adr/004-pricing-abstraction.md). UI steps: [UserManual 12](UserManual/12-pricing.md).

## Reporting

- Two layers: daily records per day and type, and summaries per duration read by the screens. The commands and their schedule are in [06 Scheduled Jobs](06-operations.md#scheduled-jobs); why: [ADR 005](adr/005-reporting-strategy.md).
- Every report filters by `SalesDuration` through `SalesDurationTrait`. The screens offer Today, 7 days, 30 days (default) and MTD (`templates/reporting/_sales_durations.html.twig`).
- Reports: product sales, order summary, overdue orders, PO item performance and the three customer reports below.
- **PO item performance:** sorts by profit, status, product or supplier name. Default is profit ascending, so the worst items come first (`PoItemPerformanceReportCriteria`).
- **Dashboard** (`/dashboard/`) is in Home, not Reporting: today's order and product summaries against the same day last week, the sidebar badge counts (pending and overdue orders, rejected POs, pending reviews), the latest orders and top products (`DashboardDataProvider`).

Key files: `src/Reporting/UI/Http/Controller/ReportsController.php`, `src/Reporting/Application/Handler/Report/`, `src/Reporting/Domain/Metric/SalesDuration.php`, `src/Home/Service/DashboardDataProvider.php`.

UI steps: [UserManual 13](UserManual/13-reports.md).

## Customer Insights

Three reports (insights, geographic, segments) and an insights card on each customer's page. Why and the summary fields: [ADR 010](adr/010-customer-insights-reporting.md).

- Segments use **lifetime** orders that are not cancelled, not orders in the period:

| Segment | Rule |
|---|---|
| NEW | 1 order (the customer card also shows NEW for 0 orders) |
| RETURNING | 2–3 orders |
| LOYAL | 4+ orders |
| LAPSED | report: no order in the period, last order between 60 days and a year before the period's start. Card: last order more than 60 days ago |

- In the segment report LAPSED customers are not in the other segments: anyone with an order in the period is never lapsed. On the customer card LAPSED replaces the segment (`UserDoctrineRepository::findCustomerInsights()`).
- Geography uses the **shipping** address city.
- The customer card reads Customer's own repository, so Customer doesn't import Reporting ([ADR 014](adr/014-architecture-rules.md)).

Key files: `src/Reporting/Application/Handler/Report/CustomerInsightsReportHandler.php`, `src/Reporting/Application/Handler/Report/CustomerSegmentReportHandler.php`, `src/Order/Infrastructure/Persistence/Doctrine/CustomerOrderDoctrineRepository.php`, `src/Customer/Infrastructure/Persistence/Doctrine/UserDoctrineRepository.php`.

## Reviews

- Eligibility: the order is DELIVERED, belongs to the customer and contains the product. One review per customer and product (unique constraint). Rating 1–5.
- The rule is `ProductReview::ineligibility()`. `CreateReviewHandler` asks it, so every caller is held to it; the form's `ValidReviewEligibility` asks it too, only to show the message on the field. `app:generate-reviews` picks orders with its own query and asks it before creating each review. `ProductReview::create()` does not check, so fixtures can review any order.
- Statuses: PENDING → PUBLISHED or REJECTED; PUBLISHED ↔ HIDDEN; REJECTED is final.
- Only PENDING and PUBLISHED reviews can be edited. Delete is on the edit page, so REJECTED and HIDDEN reviews can't be deleted from the UI.
- `ReviewSummaryUpdater` recalculates the product's summary (average, distribution) when a review is created, deleted, or changes status or rating.

Key files: `src/Review/Domain/Model/Review/ProductReview.php`, `src/Review/Domain/Model/Review/ReviewStatus.php`, `src/Review/UI/Http/Validation/ValidReviewEligibilityValidator.php`, `src/Review/Application/Listener/ReviewSummaryUpdater.php`.

UI steps: [UserManual 15](UserManual/15-reviews.md).

## Support Pools & Tickets

Why pools, statuses and visibility work as they do: [ADR 009](adr/009-support-ticket-system.md).

- Ticket statuses: OPEN → REPLIED or CLOSED; REPLIED → OPEN or CLOSED; CLOSED → OPEN only (reopen).
- A staff reply sets REPLIED, a customer reply OPEN. `ReplyToTicketHandler` refuses a reply to a CLOSED ticket; reopen it first. The system messages added on close, reopen, snooze and reassign do not go through it.
- Close, reopen and reassign add a system message. System messages and the first message of a ticket can't be deleted (`DeleteMessageHandler`).
- Messages are PUBLIC (staff and customer) or INTERNAL (staff only).
- Snooze needs a future date. Snoozed tickets are hidden from the index unless "show snoozed" is set.
- Subscribing to a pool counts its tickets that are not closed (OPEN or REPLIED) and not snoozed in the My Queue badge and includes them in the index's "my pools" filter.
- A pool's "customer visible" flag is shown as a label; nothing filters on it.
- `messageCount` and `lastMessageAt` are kept on the ticket for sorting.
- A pool with tickets can't be deleted (foreign key, see the top of this page).

Key files: `src/Note/Domain/Model/Ticket/Ticket.php`, `src/Note/Domain/Model/Ticket/TicketStatus.php`, `src/Note/Domain/Model/Pool/Pool.php`, `src/Note/Application/Handler/Ticket/DeleteMessageHandler.php`.

UI steps: [UserManual 17](UserManual/17-notes.md).

## Audit Logging

- **Status changes** of orders, order items, purchase orders and purchase order items are written to `StatusChangeLog` by `StatusChangeLogger`, with the acting user.
- **Supplier stock and cost changes** are written to `SupplierStockChangeLog` by `SupplierStockChangeLogger`, with before and after values.
- Logs are written only by these listeners. Status change rows are deleted only by a PO rewind and by retention.
- **History charts** (`/product/{id}/history`): stock and cost per supplier in the supplier's colour, one point a day, gaps filled with the last known value. Durations LAST 7, 30 (default), 90 and ALL (`HistoryDuration`). Labels show the year when the data spans years.

### Status change retention

- `app:prune-status-change-log` runs daily at 03:30 on live ([06 Scheduled Jobs](06-operations.md#scheduled-jobs)).
- It deletes rows older than 12 months (`--months`) whose order, order item, PO or PO item is in a final status: delivered, cancelled or refunded.
- Rows of an entity still open are kept however old: `ProcessingSimulator` times shipping and delivery from an item's ACCEPTED and SHIPPED rows.
- It walks the old rows once, 5,000 ids at a time (`--batch-size`), each window its own transaction, so no long lock is held beside the simulation.
- The playground has the same history: it is restored from live's backup each night.
- Rewinding a PO reopens its order and items. If they finished more than 12 months ago, their history before the rewind is already gone.
- Stock change history is kept in full; the charts' ALL reads it.

Key files: `src/Audit/Application/EventListener/StatusChangeLogger.php`, `src/Audit/Application/EventListener/SupplierStockChangeLogger.php`, `src/Audit/UI/Console/PruneStatusChangeLogCommand.php`, `src/Audit/UI/Http/Chart/StockHistoryChartBuilder.php`.

## API

- `/api/v1/catalog/*` is public and read-only. `/api/v1/orders*` needs a bearer token of a `ROLE_ADMIN` user (`app:user:issue-token`).
- `/api/doc` (Swagger UI) is served and public in every environment.
- Endpoints, payloads, errors and how to add one: [patterns/API/README.md](patterns/API/README.md). Why: [ADR 011](adr/011-rest-api-layer.md).

Key files: `src/Catalog/UI/Http/Api/`, `src/Order/UI/Http/Api/`, `src/Order/UI/Http/Api/Payload/`.

## Related

- [04-sourcing-and-pricing.md](04-sourcing-and-pricing.md): sourcing, allocation, statuses, pricing
- [06-operations.md](06-operations.md): scheduled jobs, the worker
- [09-cli-reference.md](09-cli-reference.md): every command
- [UserManual](UserManual/README.md): screen-by-screen steps
