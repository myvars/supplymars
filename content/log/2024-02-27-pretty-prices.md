---
title: "Pretty prices: markup, VAT and charm pricing"
period: February 2024
summary: Prices worked out from cost, a markup that cascades from category to subcategory to product, VAT, and a rounding rule that ends a price in .99 or .95. The rounding rules started as a table, became an enum the same afternoon, and one of them was wrong for a month.
figures:
  - value: "3"
    label: levels a markup or price model can come from
  - value: "7"
    label: price models, NONE included
  - value: 3 hours
    label: from price model table to enum
  - value: 1 month
    label: Pretty 95 rounded the wrong way
refs:
  - label: ADR 004 · Pricing abstraction
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/004-pricing-abstraction.md
  - label: Sourcing and pricing
    url: https://github.com/myvars/supplymars/blob/main/Docs/04-sourcing-and-pricing.md
---

A shop's prices are rarely cost plus a percentage. They're cost plus a percentage, plus VAT, rounded to something a customer is used to seeing: £12.99, not £12.47. In February 2024, four weeks after the first commit, I built that into the catalogue.

## Money as strings

Every amount is a decimal column in the database and a numeric string in PHP, and every sum goes through `bcmath`: `bcmul`, `bcdiv`, `bcadd`, with a small `bcround()` helper for rounding half up. A float can't hold 0.10 exactly; a decimal string can.

## Where a markup comes from

On 20 February VAT rates moved off products and subcategories: a product's VAT rate is its category's. Markup and price model work the same way, but with three levels. A category has a default markup and a price model. A subcategory and a product can each set their own, and a markup of zero, or a price model of `NONE`, means "inherit":

```php
public function getActiveMarkup(): ?string
{
    if ($this->getDefaultMarkup() > 0) {
        return $this->getDefaultMarkup();
    }
    if ($this->getSubcategory()->getDefaultMarkup() > 0) {
        return $this->getSubcategory()->getDefaultMarkup();
    }

    return $this->getCategory()->getDefaultMarkup();
}
```

So a category can set 5% for everything in it, one subcategory can override that, and one product can override both.

## Pretty, then work back

The price comes out in four steps:

1. Cost plus the active markup gives the sell price before VAT.
2. Adding the category's VAT rate gives the price the customer would pay.
3. The active price model rounds that price to a pretty one.
4. The pretty price, less VAT, gives the real sell price, and from it the markup that actually applies.

The rounding happens on the price with VAT, the one the customer sees, and the product stores the markup that produces it. A product set to 5% might really be at 5.6%, because that's what £12.99 needs, and its record says so.

## A table, then an enum

The price models arrived on 23 February, at noon, as a database table: an entity, a repository, a form, a controller and fixtures for None, Pretty 00, Pretty 10, Pretty 49, Pretty 95 and Pretty 99, with a `PrettyPriceService` that rounded a price by the model's tag.

Just after three the table was gone. `PriceModel` became an enum, each model a rule in code instead of a row in a table, with seven cases, `NONE`, `DEFAULT` and five pretty ones, and the enum does its own rounding:

```php
private function pretty49(string $price): string
{
    $fraction = bccomp(bcsub($price, bcdiv($price, 1, 0), 2), '0.50', 2) >= 0 ? '0.99' : '0.49';

    return bcadd(bcmul($price, '1', 0), $fraction, 2);
}
```

Pretty 49 ends a price in .49 below the half and .99 above it. Pretty 99 always ends in .99, Pretty 00 rounds up to the pound, Pretty 10 up to the next ten pence. The commit removed 713 lines and added 180: the controller, the form, the repository, the fixtures and the service went, and the entity's columns became `match` arms.

## Pretty 95, backwards

Pretty 95 was meant to end every price in .95, the next one up. As first written, it did the opposite of what it should: a price whose pence were already past .95 went down to .95, and any price below .95 went up to the next pound's .95, so £10.20 became £11.95. It stayed that way for a month. On 23 March the commit that added the test suite fixed it, and its cases pin the behaviour: £10.01 becomes £10.95, £10.95 stays, and £10.96 becomes £11.95.

## Repricing only what changed

A price depends on four things that live elsewhere: the product's cost, its subcategory, its category and the category's VAT rate. Doctrine listeners on them watch the fields that matter. When a category's markup changes, its listener reprices only the products whose markup comes from the category, those with no markup of their own and none on their subcategory. The price model got the same check on 23 March; until then a category's change repriced every product without a model of its own, which did no harm, since the price came out the same. On 27 February a listener on VAT rates joined them: a change of rate reprices every product in the categories that use it, since VAT has no override. The same commit gave the product a costing section that shows the markup and price model at all three levels, its own, its subcategory's and its category's, and edits each.

*Update, November 2026: the seven price models and the three-level cascade haven't changed, and ADR 004 records them. The listeners went in the [October 2025 rewrite of domain events](/log/domain-events-take-two): a category now raises an event saying what changed, and a listener in the Pricing context reprices the products.*
