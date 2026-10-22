# SupplyMars

[![CI](https://github.com/myvars/supplymars/actions/workflows/ci.yml/badge.svg)](https://github.com/myvars/supplymars/actions/workflows/ci.yml)

A fully operational backend e-commerce platform, built as a portfolio project to demonstrate real-world architectural complexity beyond the checkout.

**[Live Demo →](https://www.supplymars.com)** · **[About the Developer →](https://www.supplymars.com/about)**

## What Is This?

Most e-commerce demos stop at "add to cart." SupplyMars focuses on what comes after: sourcing each order line from competing suppliers, pricing that follows supplier costs and margins, and fulfilment that moves through independent purchase-order lifecycles.

The catalog (products, descriptions and imagery) is AI-generated, and scheduled simulations keep orders flowing through the system end to end.

This is not a commercial product. It is a working demonstration of how I approach complex problems, from domain modelling and architecture through to UI, testing and deployment.

## Key Features

- **Multi-supplier sourcing**: products come from your own warehouse or external dropshippers, each with its own cost and stock
- **Dynamic pricing**: markups cascade from category to subcategory to product, with price-model rounding
- **Order allocation**: each order line goes to the cheapest supplier that can supply it; staff can split a line across suppliers by hand
- **Purchase order lifecycle**: from creation through acceptance, shipping and delivery, or rejection and refund
- **Simulation engine**: console commands generate orders, progress fulfilment, change stock and build reporting data
- **Reporting dashboards**: pre-aggregated sales, margin and customer reports, filtered by product, category, supplier and period
- **REST API**: OpenAPI-documented endpoints for the catalog and orders
- **Support tickets**: pool-based internal ticketing with threaded messages

Built with PHP 8.5+, Symfony 8.1, Doctrine ORM, MySQL 8.4, Symfony Messenger (database queue), Redis, Tailwind CSS, Hotwire (Turbo + Stimulus) and AssetMapper, served by FrankenPHP in Docker, with GitHub Actions CI and AWS hosting.

## Getting Started

```bash
make up-dev-tools          # MySQL, Redis, Mailpit, phpMyAdmin
symfony serve -d           # the app at https://127.0.0.1:8000
```

[Local Development Setup](Docs/02-setup-local.md) covers prerequisites, the full Docker stack, a user and data, and the local URLs.

## Documentation

- [Docs/README.md](Docs/README.md): every technical doc and when to read it. AI agents start at [CLAUDE.md](CLAUDE.md).
- [User Manual](Docs/UserManual/README.md): day-to-day use of the app, screen by screen.
- [Technical Review](Docs/12-technical-review.md): an architectural review as of V1.0 (31 March 2026), a dated snapshot.
