# ADR 011: REST API Layer

## Status

Accepted

## Context

[ADR 008](008-server-driven-ui-architecture.md) established a server-driven UI architecture where controllers return HTML, not JSON. This was the right choice for the admin interface, which is form-heavy and benefits from Symfony's validation, Turbo navigation, and Twig rendering.

However, several use cases emerged that the server-rendered UI doesn't serve well:

1. **External integrations** — third-party systems (ERP, WMS, future storefronts) need programmatic access to catalog and order data without parsing HTML
2. **Public catalog access** — product/category data should be consumable by any client (mobile apps, partner sites) without authentication
3. **Automation** — internal scripts and tooling benefit from a stable JSON contract rather than scraping Turbo responses

The team evaluated approaches:

| Approach | Pros | Cons |
|----------|------|------|
| **API Platform** | Feature-rich, auto-generated | Heavy abstraction, forces conventions on existing code |
| **Custom controllers + NelmioApiDoc** | Lightweight, full control, reuses existing handlers | More manual work per endpoint |
| **GraphQL** | Flexible queries | Overkill for current needs, learning curve |

Key constraints:
- Must not duplicate domain logic — API controllers should reuse existing handlers and repositories
- Must not affect the web UI — separate firewall, separate error handling
- Authentication must be stateless (no sessions) for API consumers

## Decision

We added a **lightweight REST API layer** alongside the existing server-rendered UI. The two presentation layers are independent:

```
┌───────────────────────────────────────────────────────────────────┐
│                        UI LAYER                                   │
│                                                                   │
│  ┌─────────────────────┐          ┌────────────────────────────┐  │
│  │     WEB (HTML)      │          │       API (JSON)           │  │
│  │                     │          │                            │  │
│  │  FormFlow           │          │  AbstractApiController     │  │
│  │  Twig + Turbo       │          │  ApiResponse helpers       │  │
│  │  Symfony Forms      │          │  Resource DTOs (output)    │  │
│  │  Session auth       │          │  Request DTOs (input)      │  │
│  │  CSRF protection    │          │  Bearer token auth         │  │
│  │                     │          │  RFC 7807 errors           │  │
│  └─────────┬───────────┘          └──────────┬─────────────────┘  │
│            │                                 │                    │
│            └──────────────┬──────────────────┘                    │
│                           │                                       │
│              Shared: Handlers, Repositories, Domain               │
└───────────────────────────────────────────────────────────────────┘
```

### Design Choices

**Custom controllers over API Platform:** API Platform is powerful but opinionated. Our entities use DDD patterns (rich aggregates, ULID public IDs, domain events) that don't map cleanly to API Platform's resource model. Custom controllers give full control with minimal abstraction.

**Resource classes as output DTOs:** Each API response is shaped by a readonly `Resource` class implementing `ApiResourceInterface`, with a `fromEntity()` factory and `toArray()` method. This decouples the JSON representation from the entity structure and allows different representations for list vs. detail views (`ProductListResource` vs. `ProductResource`). The interface enables `ApiResponse::collection()` to accept a resource class-string directly instead of requiring a verbose closure.

**Request classes as input DTOs:** Write endpoints use `#[MapRequestPayload]` with Symfony Validator constraints. Validation errors are caught by `ApiExceptionListener` and returned as RFC 7807 with a `violations` array.

**Separate firewall:** The `api` firewall is stateless and uses `access_token` authentication. It sits before the `main` firewall in the config so `/api/` routes never hit session handling.

**NelmioApiDoc for Swagger:** OpenAPI documentation is generated from `#[OA\...]` attributes on controllers and Resource classes. The Swagger UI is available at `/api/doc` in development.

**Selective public access:** Catalog endpoints are public (read-only product browsing). Order endpoints require `ROLE_ADMIN` via Bearer token. This is controlled via `access_control` rules, not per-controller logic.

## Consequences

### Positive

- **No domain duplication**: API controllers call the same handlers and repositories as web controllers
- **Independent evolution**: Web and API can change independently (different DTOs, different response shapes)
- **Stateless**: No session overhead for API consumers
- **Standardised errors**: RFC 7807 problem+json across all API error responses
- **Self-documenting**: Swagger UI auto-generated from code attributes
- **Progressive**: New contexts can add API endpoints incrementally without affecting existing web UI

### Negative

- **Two presentation layers to maintain**: Each new feature may need both web and API endpoints
- **No automatic CRUD**: Unlike API Platform, each endpoint is hand-written
- **Resource class boilerplate**: Each entity needs one or more Resource classes for serialization

### Relationship to ADR 008

This decision **complements** rather than replaces ADR 008. The server-driven UI remains the primary interface for staff users. The API layer serves a different audience (external systems, automation) with different constraints (stateless, JSON, no CSRF). The two layers share all business logic through the Application and Domain layers.

## Implementation Notes

### File Structure

```
src/{Context}/UI/Http/Api/
├── {Entity}ApiController.php           # Route handlers
├── Resource/
│   ├── {Entity}Resource.php            # Detail view DTO
│   └── {Entity}ListResource.php        # List view DTO (optional)
└── Request/
    ├── Create{Entity}Request.php       # Write input DTO
    └── Update{Entity}Request.php       # Write input DTO
```

### Response Envelope

```json
// Single item
{ "data": { "id": "...", ... } }

// Collection (paginated)
{
    "data": [ ... ],
    "meta": { "page": 1, "limit": 20, "total": 42 },
    "links": { "self": "...", "next": "...", "prev": null }
}

// Error (RFC 7807)
{
    "type": "about:blank",
    "title": "Unprocessable Content",
    "status": 422,
    "detail": "One or more fields are invalid.",
    "violations": [
        { "propertyPath": "quantity", "title": "Quantity must be positive." }
    ]
}
```

### Key Files

- `src/Shared/UI/Http/Api/AbstractApiController.php` — Base controller
- `src/Shared/UI/Http/Api/ApiResourceInterface.php` — Resource contract (`toArray()`)
- `src/Shared/UI/Http/Api/ApiResponse.php` — Response factory
- `src/Shared/UI/Http/Api/EventListener/ApiExceptionListener.php` — Error handling
- `src/Shared/Infrastructure/Security/ApiTokenHandler.php` — Token authentication
- `src/Shared/Infrastructure/Security/ApiAuthenticationFailureHandler.php` — Auth error responses
- `config/packages/nelmio_api_doc.yaml` — Swagger configuration
- `config/packages/security.yaml` — API firewall and access control

## Related Documentation

- [Server-Driven UI](008-server-driven-ui-architecture.md) — Why the web UI uses HTML, not JSON
- [API Pattern](../patterns/API/README.md) — How to add new API endpoints
- [Security](../08-security.md) — Full authentication and authorization documentation
