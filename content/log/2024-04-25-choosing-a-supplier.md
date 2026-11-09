---
title: Choosing a supplier
period: February – April 2024
summary: Every product can come from several suppliers, so each one has an active source, the cheapest active supplier with stock, with more stock breaking a tie. Listeners re-pick the source when a supplier's stock, cost or status changes, and in April a supplier's range could build the catalogue on its own.
refs:
  - label: ADR 001 · Multi-supplier sourcing
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/001-multi-supplier-sourcing.md
  - label: Sourcing and pricing
    url: https://github.com/myvars/supplymars/blob/main/Docs/04-sourcing-and-pricing.md
---

In the catalogue the same product can come from several suppliers, each with its own price, stock and lead time. So every product has to answer one question all the time: which supplier would I buy this from right now?

## The supplier's side

At the end of February the suppliers arrived, and their side mirrors the catalogue. A `Supplier` has `SupplierProduct`s, and each supplier product sits in the supplier's own `SupplierCategory`, `SupplierSubcategory` and `SupplierManufacturer`: the supplier's names for things, not ours. A supplier product has a product code, a manufacturer part number, weight, stock, lead time and cost, and it can be mapped to one of our products. A product can have any number of them.

## The active source

`ActiveSourceCalculator` picks one supplier product as the product's active source. It looks only at supplier products that are active, from a supplier that is active, with stock and a cost above zero. Of those, the cheapest wins, and on equal cost the one with more stock. That tie-break kept whichever supplier it found first until the test rewrite of February 2025 caught it; its first test had passed only because of the order it added the suppliers in. The product then copies its cost, stock and lead time from the active source. If nothing qualifies, the product has no active source.

The cost the product copies is the one its [pricing](/log/pretty-prices) starts from, so a change of source can change the price.

## Keeping it current

The source is re-picked whenever something it depends on changes. A Doctrine listener on `SupplierProduct` watches its stock, cost, lead time, the product it's mapped to, and whether it's active; when any of them changes, the product's source is worked out again. Alongside it, a second listener, on `Supplier`, re-picked the source for the products affected when a supplier was switched on or off. The product's own listener then reprices it when its cost changes, so a supplier dropping its price can reach the customer's price without anyone touching the product.

A supplier product can also be switched on and off from its screen, or unmapped from a product, and both go through the same calculation.

## Building the catalogue from a supplier

By late April a supplier's range could create our catalogue. `ProductGenerator` takes an unmapped supplier product and runs four mappers. The manufacturer, category and subcategory mappers find ours by name or create them; the product mapper creates the product with the supplier product's name, cost, lead time, part number and weight, then maps the supplier product to it, and `ProductGenerator` prices it. A new category gets the standard VAT rate, a 5% markup and the default price model; a new product inherits both.

A map link on a supplier product's screen creates its product, and `app:import-products` did the same for every product of one supplier, the seeded "Turtle Inc". Matching an existing product was by name at first. In September the import became `app:create-warehouse-products`, and a new `app:create-supplier-products` matched other suppliers' products by manufacturer part number.

*Update, November 2026: ADR 001 records the rule, and since the [October 2025 rewrite](/log/domain-events-take-two) the source is re-picked by domain events. A single order line goes to the cheapest supplier with stock [for the whole line](/log/six-days-to-a-supply-chain-that-runs-itself), or waits.*
