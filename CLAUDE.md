# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SupplyMars is a **Mars-themed e-commerce and operations demo platform** built to showcase modern PHP/Symfony architecture in a realistic, end-to-end system. It models products, categories, suppliers, pricing, orders, purchase orders, fulfilment, reporting, and automation. The project is fully Dockerized and uses modern PHP development practices with strict type checking and code quality tools.

Architecturally, SupplyMars is a **modular monolith with strong DDD influences**. The goal is long-term clarity, explicit domain modelling, and automation — not novelty, over-abstraction, or frontend complexity.


## Development Environment

### Starting the Application

```bash
# Start Symfony local server in the background (preferred for development)
symfony serve -d

# Start required development tools (DB, PHPMyAdmin, etc.)
make up-dev-tools

# Start full containerized development environment (PHP, Nginx, DB, etc) - alternative for development
make up

# Start with production configuration locally
make up-prod-local

# Stop all services
make down
```

### Running Tests

```bash
# Run all tests (via Docker)
make test

# Run specific test by filter
make test-NameOfTest

# The test script (scripts/run-tests.sh) automatically:
# - Drops and recreates the test database
# - Creates schema
# - Sets up messenger transports
# - Runs PHPUnit
```

**Important**: Tests use `APP_ENV=test` and rely on DAMA Doctrine Test Bundle for database transaction rollback between tests.

### Code Quality Tools

```bash
# PHP-CS-Fixer (code style - PSR-12 + Symfony conventions)
docker compose exec php vendor/bin/php-cs-fixer fix

# PHPStan (static analysis - Level 6)
docker compose exec php vendor/bin/phpstan analyse

# Rector (automated refactoring and upgrades)
docker compose exec php vendor/bin/rector process
```

**Configuration files**:
- `.php-cs-fixer.dist.php` - Uses `@Symfony` rules with `yoda_style: false`
- `phpstan.dist.neon` - Level 6 analysis
- `rector.php` - Configured for dead code removal, code quality, type declarations, Doctrine, and Symfony

## Architecture

### Domain-Driven Design Structure

The codebase follows a **DDD layered architecture**, organized by bounded contexts.

```
src/
├── Audit/           - Audit logging domain
├── Catalog/         - Product catalog domain
├── Customer/        - Customer management domain
├── Home/            - Homepage (simple, no DDD layers)
├── Order/           - Order management domain
├── Pricing/         - Pricing strategies domain
├── Purchasing/      - Purchase order domain
├── Reporting/       - Business reporting domain
└── Shared/          - Shared kernel
```

### DDD Layers

Each bounded context generally follows this structure — pragmatism beats purity.

**Application Layer** (`Application/`)
- **Commands** - Write operations (CQRS command objects)
  - Example: `CreateManufacturer.php` - Readonly DTOs with constructor parameters
- **Handlers** - Command handlers (one per command)
- **Listeners** - Domain event listeners
- **Search** - Read model queries (CQRS query objects)

**Domain Layer** (`Domain/`)
- **Model** - Entities, Aggregates, Value Objects
- **Repository** - Repository interfaces (implementation in Infrastructure)
- **Event** - Domain events
- **Service** - Domain services

**Infrastructure Layer** (`Infrastructure/`)
- **Persistence** - Doctrine repositories, ORM mappings
- Concrete implementations of domain repository interfaces

**UI Layer** (`UI/`)
- **Http** - Controllers, Forms, DTOs
- Presentation logic

### Shared Kernel (`Shared/`)

The `Shared/` module contains cross-cutting concerns:

**Application Layer**:
- `FlusherInterface` - Abstraction for persisting changes
- `Result` - Result object pattern for operations
- `RedirectTarget` - For handling redirects
- `Identity/` - Identity and access management
- `Search/` - Shared search/query abstractions

**Domain Layer**:
- `Event/` - Base domain event classes
  - `DomainEventInterface` - Marker interface for all domain events
  - `AsyncDomainEventInterface` - Events processed asynchronously via messenger
  - `DomainEventProviderInterface` & `DomainEventProviderTrait` - For entities that emit events
  - `StatusWasChangedEventInterface` - Marker for status change events
- `Service/` - Shared domain services
- `ValueObject/` - Shared value objects (Money, Email, etc.)

**Infrastructure Layer**:
- `Security/` - Authentication and authorization
- `FileStorage/` - File upload handling
- `Persistence/Search/` - Pagination and search infrastructure

### CQRS Pattern

This codebase often implements Command-Query Responsibility Segregation:

- **Commands** (`Application/Command/`) - Write operations, return `Result` objects
- **Handlers** (`Application/Handler/`) - Process commands, modify domain models
- **Search/Queries** (`Application/Search/`) - Read operations, optimized for queries

### Domain Events & Messaging

Domain events are used for cross-bounded-context communication:

- Events implementing `AsyncDomainEventInterface` are routed to RabbitMQ (see `config/packages/messenger.yaml`)
- Use `DomainEventProviderTrait` in aggregates to collect and release events
- Messenger handles asynchronous processing with retry strategy (max 3 retries, 2x multiplier)

Rule:
> If the behavior must succeed immediately → sync  
> If it can fail, retry, or lag → async

**Messenger Transports**:
- `async` - RabbitMQ transport for async processing
- `failed` - Doctrine-based failed message storage

## Key Technologies

- **PHP 8.x** - Latest PHP with modern features
- **Symfony 8.x** - Full-stack framework
- **Doctrine ORM** - Database abstraction with migrations
- **RabbitMQ** - Message broker for async operations
- **Redis** - Caching layer
- **MySQL 8.x** - Primary database
- **Tailwind CSS** (via SymfonyCasts bundle) - Frontend styling
- **Turbo** - Hotwire Turbo for SPA-like experience
- **Zenstruck Foundry** - Test factories
- **DAMA Doctrine Test Bundle** - Database transaction management in tests
- **Docker** - Containerized development and deployment
- **Chart.js** - Data visualization in reporting (via Symfony UX)

## Twig, Templates & Components

- **Twig is the primary rendering layer**
- Templates are organized by bounded context in `templates/`:
- Templates are kept **boring, explicit, and readable**
- Business logic does **not** live in Twig
- Conditionals are simple; complex decisions belong in PHP
- Twig works hand-in-hand with **Turbo Frames and Streams**
- Templates may render:
    - Full pages
    - Frame fragments
    - Stream responses

### Symfony UX Twig Components

- Symfony **Twig Components** are used where UI structure or presentation logic is often reused
- Shared components → `src/Shared/UI/Twig/Components/` + `templates/components/`

### UX Icons

Icons are rendered using Symfony UX Icons:

## Docker Architecture

### Overview

The project uses a **multi-stage Dockerfile** with **layered Docker Compose files** to support different environments. The Makefile orchestrates these combinations.

This describes the typical setup; not all services run in all environments.

**Development Approaches:**
1. **Symfony Server + Dev Tools** (Recommended) - Run `symfony serve -d` + `make up-dev-tools`
2. **Full Docker Stack** - Run `make up` for complete containerized environment

### Multi-Stage Dockerfile

**Location**: `docker/Dockerfile`

### Docker Compose Files (Layered)

Docker Compose automatically merges files to build configurations:

#### 1. `compose.yaml` - Base Services

Defines core services with sensible defaults:

#### 2. `compose.override.yaml` - Development Overrides

**Automatically loaded** with `docker compose up`. Adds development conveniences:

#### 3. `compose.prod.yaml` - Production Overrides

Used with `-f compose.yaml -f compose.prod.yaml` for production deployments.

#### 4. `compose.prod.local.yaml` - Local Production Testing

Combines with `compose.yaml` + `compose.prod.yaml` for testing production build locally.

Uses secrets from `../supplymars-secrets/prod-local.env` (outside repo).

#### 5. `compose.dev-tools.yaml` - Standalone Dev Tools

**Separate file** for use with Symfony local server. Provides just the infrastructure:

**Usage**: `make up-dev-tools` then `symfony serve -d`

## Testing Approach

Tests are organized by bounded context in `tests/`:

```
tests/
├── Audit/
├── Catalog/
├── Customer/
├── Home/
├── Pricing/
├── Purchasing/
└── Shared/
```

**Test Extensions Configured**:
- **Zenstruck Foundry** - Object factories for tests
- **DAMA Doctrine Test Bundle** - Wraps each test in a database transaction
- **DG Bypass Finals** - Allows mocking of final classes

**Best Practices**:
- Use factories for test data creation
- Database is automatically reset before each test run via `scripts/run-tests.sh`
- Each test runs in an isolated transaction (auto-rollback)

## Common Symfony Console Commands

```bash
# When using symfony server, use symfony command instead of php bin/console:
symfony console <command>

# Access container shell first if using full Docker setup
docker compose exec php bash

# Doctrine commands
php bin/console doctrine:migrations:migrate

# Messenger
php bin/console messenger:consume async
php bin/console messenger:setup-transports

# Cache
php bin/console cache:clear
php bin/console cache:warmup

# Debug
php bin/console debug:router
php bin/console debug:container
php bin/console debug:autowiring
```

## Controller Rules

- Controllers orchestrate only
- No business logic in controllers
- No persistence logic in controllers
- Controllers delegate to:
    - FormFlow / CommandFlow
    - Application handlers
- Controllers may choose responses, not outcomes

## FormFlow Pattern

### Overview

Controllers use **FormFlow classes** to keep them thin and consistent. FormFlows centralize common HTTP patterns: form rendering, validation, command mapping, handler invocation, flash messages, and redirects.

**Location**: `src/Shared/UI/Http/FormFlow/`

#### 1. FormFlow - Create/Update Forms

Handles GET/POST for Symfony forms with validation and command mapping.

#### 2. CommandFlow - Direct Command Execution

Executes commands without forms (e.g., state transitions, actions).

#### 3. DeleteFlow - Delete with Confirmation

Handles delete confirmation page and CSRF-validated delete POST.

#### 4. SearchFlow - Index/List Pages

Handles paginated search/index pages with out-of-range protection.

### Benefits

1. **Thin Controllers** - Controllers become simple coordinators
2. **Consistency** - All forms handle validation, errors, redirects the same way
3. **Turbo Integration** - Automatic Turbo frame/stream support
4. **Testing** - Flows are tested once; controllers test only routing/auth
5. **Readability** - Clear intent with named parameters

### Best Practices

- Use `FormFlow` for forms that create/update entities
- Use `CommandFlow` for POST-only actions (state changes, operations)
- Use `DeleteFlow` for destructive operations requiring confirmation
- Use `SearchFlow` for paginated lists
- Use `ShowFlow` for simple detail pages
- Keep mappers simple - no business logic
- Let handlers return `Result` objects with success/error messages
- Flows do not contain domain rules — only orchestration

## File Organization Conventions

When creating new features:

1. **Determine the bounded context** - Does it belong to Catalog, Order, Pricing, etc.?
2. **Follow the DDD layers**:
   - Commands and Handlers in `Application/`
   - Entities and Value Objects in `Domain/Model/`
   - Repository interfaces in `Domain/Repository/`
   - Repository implementations in `Infrastructure/Persistence/`
   - Controllers and Forms in `UI/Http/`
3. **Use readonly DTOs** for commands and queries
4. **Implement domain events** for cross-context communication
5. **Place tests** in the corresponding `tests/` subdirectory

## Environment Configuration

- `.env` - Main environment file (not committed with secrets in production)
- `.env.dev` - Development-specific overrides
- `.env.test` - Test environment configuration
- Production secrets for local production are stored in `../supplymars-secrets/prod-local.env` (outside repo)
- Actual Production secrets are added via deployment pipeline, not stored in the repo

## Code Style Expectations

- Strict types enabled (`declare(strict_types=1)`)
- Readonly properties where possible (PHP 8.1+)
- Constructor property promotion
- No Yoda-style conditions
- Full type hints on all methods
- String concatenation with single space: `'foo' . $bar`
