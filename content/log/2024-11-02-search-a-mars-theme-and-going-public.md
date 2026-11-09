---
title: Search, a Mars theme, and going public
period: October 2024
summary: The month the project got a public face and its name. Search and filters held in the URL, the Mars theme with 250 AI-generated product images, the public pages, every admin screen locked to staff, and every address moved to Mars.
refs:
  - label: Security
    url: https://github.com/myvars/supplymars/blob/main/Docs/08-security.md
  - label: Turbo pattern docs
    url: https://github.com/myvars/supplymars/blob/main/Docs/patterns/Turbo/README.md
---

Until October 2024 the project, still called Turtle in its README, sat behind a plain demo home page, with sign-ups open since the site went up in April. That month it got search worth the name, a real public face, and a theme: everything a Martian colony needs.

## Search in the URL

Every index page got search, sorting and filters, and all of it lives in the query string. Each page has a search DTO: a base `SearchDto` holds the query, sort, direction, page and limit, and each index page extends it with its own filters, such as status for orders, or category and manufacturer for products. The controller receives it already filled with `#[MapQueryString]`:

```php
public function index(
    // …
    #[MapQueryString] CategorySearchDto $dto = new CategorySearchDto()
): Response
```

Because the state is in the URL, a filtered list can be bookmarked, shared or reloaded, and the back button works. The search box is a small Stimulus controller: 300 ms after you stop typing, it rebuilds the URL with the new query, keeps the other parameters, and asks Turbo to visit it, replacing the history entry rather than adding one per keystroke. A filter screen for the five busiest sections turns a filter form into the same query string. Four days later, date ranges joined the filters.

## A Mars shop

On 17 October the home page became a shop for life on the Red Planet: a Martian landscape, "Your One-Stop Shop for Life on the Red Planet", and an honest line underneath saying it is also a working demo of e-commerce development with a modern PHP stack. From here on the project is SupplyMars: the home page shows the domain it's served on as its name, though the README said Turtle until June 2025 and the page title said Butterfly until February 2026. The catalogue got a look to match: 250 AI-generated product images, made for the rebrand, with thumbnails on the product list and on the order and purchase order cards. An About page described the stack and the twenty years of e-commerce behind it, with contact, privacy and terms pages and themed error pages for 403, 404 and 500.

Two weeks later every address moved to Mars too. New customers get one of fifteen cities, Olympia, Gale, Hellas, Tharsis and the rest, with a post code built from the city's sector code. A one-off command rewrote the existing addresses the same way, and was deleted the same day once it had run. The [Customer Insights](/log/customer-insights) reports of 2026 still group customers by those cities.

## Locked to staff

The Mars theme commit of 17 October also closed a gap that had been open since the site went up in April. Until 21 September the admin screens had no access check at all; that day every admin controller started requiring `ROLE_USER`, which kept out crawlers but still let in anyone who registered. All sixteen now require `ROLE_ADMIN`. Signing up gets you an account, and nothing behind it.

