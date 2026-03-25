# SupplyMars

A fully operational backend e-commerce platform, built as a portfolio project to demonstrate real-world architectural complexity beyond the checkout.

PHP 8.5+ • Symfony 8 • Doctrine ORM • MySQL 8.4 • RabbitMQ • Redis • Tailwind CSS • Hotwire

**[Live Demo →](https://www.supplymars.com)** · **[About the Developer →](ABOUT.md)**

---

## What Is This?

Most e-commerce demos stop at "add to cart." SupplyMars focuses on what comes after — when orders split across multiple suppliers, pricing responds to real supplier costs and margins, and fulfilment progresses through independent purchase-order lifecycles.

The platform models real operational complexity: multi-source fulfilment, dynamic pricing, and margin tracking at scale across thousands of SKUs. The catalog — products, descriptions, and imagery — is fully AI-generated, and automated simulations keep orders flowing through the system in a realistic, end-to-end way.

This is not a commercial product. It is a working demonstration of how I approach complex problems — from domain modelling and architecture through to UI, testing, and deployment. For more on the background and thinking behind it, see [ABOUT.md](ABOUT.md).

## Key Features

- **Multi-supplier sourcing** — Products can come from your own warehouse or external dropshippers, each with their own costs and stock levels
- **Dynamic pricing** — Markups cascade from category to subcategory to product; prices recalculate automatically when supplier costs change
- **Order allocation & splitting** — Customer orders split across suppliers based on availability and cost optimisation
- **Purchase order lifecycle** — Track POs from creation through acceptance, shipping, and delivery (or rejection and refund)
- **Simulation engine** — Console commands generate realistic orders, progress fulfilment, fluctuate stock, and populate reporting data
- **Reporting dashboards** — Pre-aggregated sales and margin reports with filtering by product, category, supplier, and time period
- **REST API** — OpenAPI-documented endpoints for catalog and order management
- **Support tickets** — Pool-based internal ticketing with threaded messages and visibility controls

## Getting Started

1. Clone the repository
2. Follow the [Local Development Setup](Docs/02-setup-local.md) guide

```bash
# Quick start (Symfony server + Docker services)
make up-dev-tools          # Start MySQL, Redis, RabbitMQ, Mailpit
symfony serve -d           # Start PHP server at https://127.0.0.1:8000
```

## Documentation

| Guide | Description |
|-------|-------------|
| [Technical Documentation](Docs/README.md) | Architecture, setup, features, operations, CLI reference |
| [User Manual](Docs/UserManual/README.md) | Day-to-day operations guide for managing orders, suppliers, and products |

## Tech Stack

**Backend:** PHP 8.5+ • Symfony 8.0 • Doctrine ORM • MySQL 8.4 • RabbitMQ • Redis

**Frontend:** Tailwind CSS • Hotwire (Turbo + Stimulus) • AssetMapper

**Infrastructure:** Docker • GitHub Actions CI/CD • AWS

## Build & Deploy

[![Build and Deploy](https://github.com/myvars/supplymars/workflows/Build%20and%20Deploy/badge.svg)](https://github.com/myvars/supplymars/actions?query=workflow%3ABuild%20and%20Deploy)
