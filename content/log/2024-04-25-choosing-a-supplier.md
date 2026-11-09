---
title: Choosing a supplier
period: February – April 2024
summary: Every product can come from several suppliers, so each one has an active source, the cheapest active supplier with stock. More stock was meant to break a tie, and for a year it didn't. Listeners re-pick the source when a supplier's stock, cost or status changes, and in April a supplier's range could build the catalogue on its own.
figures:
  - value: "5"
    label: supplier-side entities mirroring the catalogue
  - value: 1 year
    label: before the stock tie-break worked
  - value: "4"
    label: mappers to turn a supplier product into a product
refs:
  - label: ADR 001 · Multi-supplier sourcing
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/001-multi-supplier-sourcing.md
  - label: Sourcing and pricing
    url: https://github.com/myvars/supplymars/blob/main/Docs/04-sourcing-and-pricing.md
---

In the catalogue the same product can come from several suppliers, each with its own price, stock and lead time. So every product has to answer one question all the time: which supplier would I buy this from right now?

## The supplier's side

On 29 February the suppliers arrived, and their side mirrors the catalogue. A `Supplier` has `SupplierProduct`s, and each supplier product sits in the supplier's own `SupplierCategory`, `SupplierSubcategory` and `SupplierManufacturer`: the supplier's names for things, not ours. A supplier product has a product code, a manufacturer part number, weight, stock, lead time and cost, and it can be mapped to one of our products. A product can have any number of them.

## The active source

`ActiveSourceCalculator` picks one supplier product as the product's active source. It looks only at supplier products that are active, from a supplier that is active, with stock and a cost above zero. Of those, the cheapest wins. When two cost the same, the one with more stock was meant to win, but the code as written kept whichever it found first: an equal cost with more stock fell through to a check for a lower cost, which failed. The test for it passed only because it added the higher-stock supplier first. The tie-break worked from February 2025, when the unit and integration tests were rewritten and the check became a plain comparison of stock. The product then copies its cost, stock and lead time from the active source. If nothing qualifies, the product has no active source.

The cost the product copies is the one its [pricing](/log/pretty-prices) starts from, so a change of source can change the price.

## Keeping it current

The source is re-picked whenever something it depends on changes. A Doctrine listener on `SupplierProduct` watches its stock, cost, lead time, the product it's mapped to, and whether it's active; when any of them changes, the product's source is worked out again. That afternoon a second listener, on `Supplier`, re-picked the source for the products affected when a supplier was switched on or off. The product's own listener then reprices it when its cost changes, so a supplier dropping its price can reach the customer's price without anyone touching the product.

A supplier product can also be switched on and off from its screen, or unmapped from a product, and both go through the same calculation.

## Building the catalogue from a supplier

On 25 April a supplier's range could create our catalogue. `ProductGenerator` takes an unmapped supplier product and runs four mappers. The manufacturer, category and subcategory mappers find ours by name or create them; the product mapper creates the product with the supplier product's name, cost, lead time, part number and weight, then maps the supplier product to it, and `ProductGenerator` prices it. A new category gets the standard VAT rate, a 5% markup and the default price model; a new product inherits both.

A map link on a supplier product's screen creates its product, and `app:import-products` did the same for every product of one supplier, the seeded "Turtle Inc". Matching an existing product was by name at first. In September the import became `app:create-warehouse-products`, and a new `app:create-supplier-products` matched other suppliers' products by manufacturer part number.

*Update, November 2026: ADR 001 records the rule, lowest cost, then highest stock, and since February 2025 the code applies both halves of it. Since then the source has been re-picked by domain events rather than Doctrine listeners, in the [October 2025 rewrite](/log/domain-events-take-two). The rule for a single order line is stricter: the line goes to the cheapest supplier with stock [for the whole line](/log/hardening-the-status-tree), or waits.*
