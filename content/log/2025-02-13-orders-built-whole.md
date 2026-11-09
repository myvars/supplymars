---
title: Orders built whole
period: February 2025
summary: Orders, order lines, purchase orders and their items stopped being assembled with setters. Private constructors and named ones build each in one call, with what it needs to be valid, and the test factories build them the same way.
refs:
  - label: ADR 002 · Order line splitting
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/002-order-line-splitting.md
  - label: Sourcing and pricing
    url: https://github.com/myvars/supplymars/blob/main/Docs/04-sourcing-and-pricing.md
---

Until February 2025, a customer order in SupplyMars was put together by whoever needed one. The `CreateOrder` service created an empty order and set its customer, reference, addresses and shipping details one setter at a time. It worked, but the order didn't decide what an order needs. Any code could call `new CustomerOrder()` and set as much or as little as it liked, and the service looked up the customer's default addresses itself, through the repository. All four entities at the heart of the business, the customer order, its lines, the purchase order and its items, had public constructors.

## Named constructors

The purchasing side already had static constructors, `PurchaseOrder::createFromOrder()` and `PurchaseOrderItem::createFromCustomerOrderItem()`, but public constructors beside them. On 13 February all four constructors became private, and the customer side got static ones that say what they are for:

```php
$customerOrder = CustomerOrder::createFromCustomer(
    $customer,
    $dto->getShippingMethod(),
    $vatRate,
    $dto->getCustomerOrderRef(),
);
```

`createFromCustomer()` takes the customer's default billing and shipping addresses from the customer, which now knows its own defaults, so the service no longer queries for them. It copies the shipping method's price, with and without VAT, and its due date, and works out the order's totals before handing it back. `CustomerOrderItem::createFromProduct()`, until then a method called on an empty line, became a static constructor that builds a line for an order from a product and a quantity. `PurchaseOrder::createFromOrder()` builds a purchase order for a supplier from the customer order it serves, taking its address, shipping method and reference, with a due date from that shipping method.

The setters behind them became private: billing address, shipping method, prices, due date, status, totals and the order lock on the order; product, quantity, prices and weight on the line. State changes went through methods named for what they do, such as `cancelOrder()` and `lockOrder()`. Outside the class, the only way to create a customer order is `createFromCustomer()`, so every path that creates one goes through the same code.

## Types that stop lying

With every field set at construction, the getters could say so. `getStatus()`, `getProduct()`, `getCustomerOrder()`, `getSupplier()`, `getShippingMethod()` and `getDueDate()` stopped returning nullable types. Code that read them no longer had to handle a null that couldn't happen, and a migration made the purchase order's shipping method and due date `NOT NULL` in the database too.

## The factories follow

The Foundry test factories had built entities the same way the old services did: create the object, then set fields on it. A factory that bypasses the constructor can produce an order the application never could, and a test built on one proves less than it seems.

The factories now build through the named constructors. `CustomerOrderFactory` supplies a customer with a default address, a shipping method and a VAT rate, and instantiates with `CustomerOrder::createFromCustomer()`. New factories for purchase orders and their items do the same.

A few setters stayed public, such as the order's customer and shipping address, and the job of moving every write behind a domain method finished in the [rebuild by bounded context](/log/rebuilding-by-bounded-context). From here on, an entity decided how it could be built, not just how it could change.
