# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Quick Reference

### Common Commands
```bash
# Development (preferred)
symfony serve -d              # Start local dev server (https://127.0.0.1:8000)
make up-dev-tools             # Start DB, Redis, RabbitMQ, Mailpit

# Tests
make test                     # Run all tests
make test-SomeTest            # Run filtered tests
vendor/bin/phpunit            # Run tests locally (ensure test DB exists)

# Code Quality (run locally or via Docker)
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse
vendor/bin/rector process

# Full Docker (alternative to symfony serve)
make up                       # Start full containerized environment
make down                     # Stop all services
make bash                     # Shell into PHP container
```

### Local Dev URLs
| Service | URL                    |
|---------|------------------------|
| App | https://localhost:8000 |
| PHPMyAdmin | http://localhost:8080  |
| Mailpit | http://localhost:8025  |
| RabbitMQ Management | http://localhost:15672 |

### Key Directories
```
src/{Context}/Application/    # Commands, Handlers, Search
src/{Context}/Domain/Model/   # Entities, Value Objects
src/{Context}/Domain/Repository/  # Repository interfaces
src/{Context}/Infrastructure/ # Doctrine repositories
src/{Context}/UI/Http/        # Controllers, Forms, DTOs
tests/Shared/Factory/         # Test factories (Foundry)
tests/Shared/Story/           # Test stories (e.g., StaffUserStory)
```

## Project Overview

SupplyMars is a **Mars-themed e-commerce and operations demo platform** built with modern PHP/Symfony architecture. It models products, categories, suppliers, pricing, orders, purchase orders, fulfilment, reporting, and automation.

Architecturally, SupplyMars is a **modular monolith with strong DDD influences**. The goal is long-term clarity, explicit domain modelling, and automation.

### Key Technologies
- **PHP 8.5+** / **Symfony 8.0.x**
- **Doctrine ORM** with MySQL 8.4
- **RabbitMQ** for async messaging
- **Redis** for caching
- **Tailwind CSS** + **Turbo** (Hotwire)
- **Zenstruck Foundry** + **DAMA Doctrine Test Bundle** for testing

## Development Environment

### Preferred Setup: Symfony Server + Dev Tools

```bash
symfony serve -d          # Start local PHP server
make up-dev-tools         # Start MySQL, Redis, RabbitMQ, Mailpit, PHPMyAdmin
```

This gives you fast iteration with hot reload while Docker provides the infrastructure services.

### Alternative: Full Docker Stack

```bash
make up                   # Start everything in containers
make bash                 # Shell into PHP container
```

Use this when you need a fully isolated environment or for CI-like testing.

### Running Tests

Tests can run via Docker (recommended) or locally:

```bash
# Via Docker (handles DB setup automatically)
make test
make test-SomeTest

# Locally (ensure test database exists first)
symfony console doctrine:database:create --env=test
symfony console doctrine:schema:create --env=test
vendor/bin/phpunit
```

Tests use `APP_ENV=test` and DAMA Doctrine Test Bundle for transaction rollback between tests.

### Code Quality Tools

Run locally or via Docker:

```bash
# Locally
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse
vendor/bin/rector process

# Via Docker
docker compose exec php vendor/bin/php-cs-fixer fix
docker compose exec php vendor/bin/phpstan analyse
docker compose exec php vendor/bin/rector process
```

**Configuration**:
- `.php-cs-fixer.dist.php` - `@Symfony` rules, `yoda_style: false`, single-space concatenation
- `phpstan.dist.neon` - Level 6 analysis
- `rector.php` - Dead code, code quality, type declarations, Doctrine/Symfony sets

## Architecture

### Bounded Contexts

```
src/
├── Audit/           - Audit logging (status changes, stock changes)
├── Catalog/         - Products, categories, manufacturers, subcategories
├── Customer/        - Users, addresses, authentication
├── Home/            - Homepage (simple, no DDD layers)
├── Order/           - Customer orders and order items
├── Pricing/         - VAT rates, pricing strategies
├── Purchasing/      - Purchase orders, suppliers, supplier products
├── Reporting/       - Business reporting and dashboards
└── Shared/          - Shared kernel (cross-cutting concerns)
```

### DDD Layers

Each bounded context follows this structure:

```
{Context}/
├── Application/
│   ├── Command/     # Write operations (readonly DTOs)
│   ├── Handler/     # Command handlers (one per command)
│   ├── Listener/    # Domain event listeners
│   └── Search/      # Read model queries
├── Domain/
│   ├── Model/       # Entities, Aggregates, Value Objects
│   ├── Repository/  # Repository interfaces
│   ├── Event/       # Domain events
│   └── Service/     # Domain services
├── Infrastructure/
│   └── Persistence/ # Doctrine repositories
└── UI/
    └── Http/        # Controllers, Forms, DTOs
```

### Entity Patterns

**ULID Public IDs**: Entities use auto-increment `id` internally but expose ULID-based `publicId` for URLs and APIs:

```php
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;

class Manufacturer
{
    use HasPublicUlid;

    public function __construct()
    {
        $this->initializePublicId();  // Call in constructor
    }

    public function getPublicId(): ManufacturerPublicId
    {
        return ManufacturerPublicId::fromString($this->publicIdString());
    }
}
```

Each entity has a typed `{Entity}PublicId` value object extending `AbstractUlidId`.

**ValueResolver**: Controllers use `#[ValueResolver('public_id')]` to resolve entities by ULID:

```php
#[Route('/manufacturer/{id}/edit')]
public function edit(#[ValueResolver('public_id')] Manufacturer $manufacturer): Response
```

**Timestampable**: Use `TimestampableEntity` trait from Gedmo for `createdAt`/`updatedAt`.

### CQRS Pattern

- **Commands** (`Application/Command/`) - Readonly DTOs for write operations
- **Handlers** (`Application/Handler/`) - Process commands, return `Result` objects
- **Search** (`Application/Search/`) - Query criteria objects for read operations

**Result Pattern**:
```php
Result::ok('Manufacturer created');
Result::fail('Name cannot be empty');
```

### Domain Events

Events enable cross-context communication:

- `DomainEventInterface` - Marker for sync events
- `AsyncDomainEventInterface` - Routed to RabbitMQ
- `DomainEventProviderTrait` - Use in aggregates to collect/release events

Rule: If behavior must succeed immediately → sync. If it can fail/retry/lag → async.

## Controller & FormFlow Patterns

Controllers are thin orchestrators using FormFlow classes.

### FormFlow Types

| Flow | Purpose | Example |
|------|---------|---------|
| `FormFlow` | Create/update with Symfony forms | New manufacturer form |
| `CommandFlow` | Direct command execution (no form) | State transitions |
| `DeleteFlow` | Delete with confirmation | Delete manufacturer |
| `SearchFlow` | Paginated index pages | Manufacturer list |
| `ShowFlow` | Simple detail pages | Manufacturer detail |

### Example Controller

```php
#[Route('/manufacturer/new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    CreateManufacturerMapper $mapper,
    CreateManufacturerHandler $handler,
    FormFlow $flow,
): Response {
    return $flow->form(
        request: $request,
        formType: ManufacturerType::class,
        data: new ManufacturerForm(),
        mapper: $mapper,
        handler: $handler,
        context: FlowContext::forCreate(self::MODEL),
    );
}
```

### Route Naming Convention

Routes follow: `app_{context}_{entity}_{action}`

Examples:
- `app_catalog_manufacturer_index`
- `app_catalog_manufacturer_new`
- `app_catalog_manufacturer_edit`
- `app_catalog_manufacturer_delete`

## Testing

### Test Organization

Tests mirror the `src/` structure:

```
tests/
├── {Context}/           # Tests for each bounded context
└── Shared/
    ├── Factory/         # Zenstruck Foundry factories
    └── Story/           # Reusable test stories
```

### Writing Tests

```php
use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Zenstruck\Foundry\Test\Factories;

class ManufacturerTest extends WebTestCase
{
    use Factories;

    #[WithStory(StaffUserStory::class)]  // Authenticated user
    public function testCreateManufacturer(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Test']);
        // ...
    }
}
```

**Key Points**:
- Use `#[WithStory(StaffUserStory::class)]` for authenticated requests
- Factories are in `tests/Shared/Factory/`
- Each test runs in an isolated transaction (auto-rollback)

## Shared Kernel

Located in `src/Shared/`, contains cross-cutting concerns:

### Application Layer
- `Result` - Success/failure result objects
- `RedirectTarget` - Redirect handling
- `FlusherInterface` - Persistence abstraction

### Domain Layer
- `ValueObject/AbstractUlidId` - Base class for ULID value objects
- `Event/*` - Domain event infrastructure

### Infrastructure Layer
- `Persistence/Doctrine/Mapping/HasPublicUlid` - ULID trait for entities
- `Security/` - Authentication/authorization

### UI Layer
- `Http/FormFlow/` - FormFlow classes
- `Twig/Components/` - Shared Twig components

## Templates

- Twig is the primary rendering layer
- Organized by bounded context in `templates/`
- Use Turbo Frames and Streams for SPA-like experience
- Symfony UX Twig Components are used where UI structure or presentation logic is often reused - found in `templates/components/`
- Icons are rendered using Symfony UX Icons
- Chart.js for data visualization, reporting (via Symfony UX)
- Keep templates simple; complex logic belongs in PHP

## Common Console Commands

```bash
# When using symfony server:
symfony console <command>

# Doctrine
symfony console doctrine:migrations:migrate
symfony console doctrine:schema:validate

# Messenger
symfony console messenger:consume async
symfony console messenger:setup-transports

# Cache (useful when things break)
symfony console cache:clear

# Debug
symfony console debug:router
symfony console debug:container
symfony console debug:autowiring
```

## Git Commit Messages

Commit messages must be clear, concise, and written as if by a human contributor.
- Do not mention Claude, AI automation.

## Code Style

- `declare(strict_types=1)` - Handled by Symfony
- Readonly properties where possible
- Constructor property promotion
- No Yoda-style conditions (`$value === null`, not `null === $value`)
- Full type hints on all methods
- String concatenation: `'foo' . $bar` (single space around `.`)

## File Organization for New Features

1. **Determine the bounded context** - Catalog, Order, Pricing, etc.
2. **Follow DDD layers**:
   - Commands/Handlers in `Application/`
   - Entities/Value Objects in `Domain/Model/`
   - Repository interfaces in `Domain/Repository/`
   - Doctrine repositories in `Infrastructure/Persistence/`
   - Controllers/Forms in `UI/Http/`
3. **Create typed PublicId** value object extending `AbstractUlidId`
4. **Use `HasPublicUlid` trait** in entities, call `initializePublicId()` in constructor
5. **Place tests** in corresponding `tests/` subdirectory
6. **Create Foundry factory** in `tests/Shared/Factory/` if needed
