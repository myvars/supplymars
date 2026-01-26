# SupplyMars

A fully-featured backend e-commerce and supply-chain platform designed to model real-world complexity beyond the checkout.

Built using Domain-Driven Design principles with PHP 8.5+, Symfony 8, Doctrine ORM, MySQL, RabbitMQ, and Redis.

**[Live Demo →](https://www.supplymars.com)**

## What is this?
Most e-commerce demos stop at “add to cart.” SupplyMars focuses on what comes after — when orders split across multiple suppliers, pricing responds to real supplier costs and margins, and fulfillment progresses through independent purchase-order lifecycles.

Built to model real operational complexity, the platform demonstrates multi-source fulfillment, dynamic pricing, and margin tracking at scale across thousands of SKUs. The catalog — products, descriptions, and imagery — is fully AI-generated, while automated simulations keep orders flowing through the system in a realistic, end-to-end way.

Whether you’re managing a Martian colony or exploring modern PHP architecture, SupplyMars invites exploration and experimentation within a complete system.
## Key Features

- **Multi-supplier sourcing** — Products can come from your own warehouse or external dropshippers, each with their own costs and stock levels
- **Dynamic pricing** — Markups cascade from category to subcategory to product; prices recalculate automatically when supplier costs change
- **Order allocation & splitting** — Customer orders split across suppliers based on availability and cost optimisation
- **Purchase order lifecycle** — Track POs from creation through acceptance, shipping, and delivery (or rejection and refund)
- **Simulation commands** — Console commands generate realistic orders, progress fulfilment, fluctuate stock, and populate reporting data
- **Reporting dashboards** — Pre-aggregated sales and margin reports with filtering by product, category, supplier, and time period

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

## Build & Deploy

[![Build and Deploy](https://github.com/myvars/supplymars/workflows/Build%20and%20Deploy/badge.svg)](https://github.com/myvars/supplymars/actions?query=workflow%3ABuild%20and%20Deploy)
