# API Pattern

Standardised approach for building JSON REST endpoints that reuse existing domain logic.

## Intent

Provide a consistent, lightweight structure for API endpoints that complements the server-rendered UI without duplicating business logic. API controllers delegate to the same handlers and repositories as web controllers, but return JSON responses instead of HTML.

## Architecture

```
HTTP Request (JSON)
    │
    ▼
API Controller (extends AbstractApiController)
    │
    ├── Reads: Repository → Resource::fromEntity() → ApiResponse::item/collection
    │
    └── Writes: Request DTO → Command → Handler → handleResult() → ApiResponse
```

**Key principle:** API controllers are thin. Domain logic stays in handlers and entities.

## Components

### AbstractApiController

Base controller providing `handleResult()` for write operations and `resolveFilterId()` for ULID query-param filters:

```php
// src/Shared/UI/Http/Api/AbstractApiController.php

abstract class AbstractApiController extends AbstractController
{
    public function __construct(
        private readonly PublicIdResolverRegistry $publicIdResolver,
    ) {}

    protected function handleResult(
        Result $result,
        int $successStatus = 200,
        ApiResourceInterface|callable|null $onSuccess = null,
    ): JsonResponse {
        if (!$result->ok) {
            return ApiResponse::error($result->message ?? 'Operation failed.', 422);
        }

        $data = match (true) {
            $onSuccess instanceof ApiResourceInterface => $onSuccess->toArray(),
            $onSuccess !== null => $onSuccess($result),
            default => ['message' => $result->message],
        };

        return ApiResponse::item($data, $successStatus);
    }

    protected function resolveFilterId(Request $request, string $param, string $publicIdClass): ?int
    {
        if (!$request->query->has($param)) {
            return null;
        }

        return $this->publicIdResolver->resolve(
            $publicIdClass::fromString($request->query->getString($param))
        );
    }
}
```

`handleResult()` accepts an `ApiResourceInterface` directly (preferred for commands on existing entities) or a callable for custom response shaping. `resolveFilterId()` converts a ULID query parameter to an internal int ID via `PublicIdResolverRegistry`, returning null if the parameter is absent or the entity is not found.

### ApiResponse

Static factory for consistent JSON response formatting:

| Method | Purpose | HTTP Status |
|--------|---------|-------------|
| `ApiResponse::item($data, $status)` | Single resource | 200 (default) or 201 |
| `ApiResponse::collection($pager, $resource, $request)` | Paginated list with meta + links | 200 |
| `ApiResponse::error($detail, $status)` | RFC 7807 error | 400/404/422/etc. |
| `ApiResponse::noContent()` | Empty response | 204 |

### Resource Classes (Output DTOs)

Readonly classes that transform entities into arrays for JSON serialization. All resource classes implement `ApiResourceInterface` which formalizes the `toArray()` contract.

```php
// src/Shared/UI/Http/Api/ApiResourceInterface.php

interface ApiResourceInterface
{
    /** @return array<string, mixed> */
    public function toArray(): array;
}
```

```php
// src/Catalog/UI/Http/Api/Resource/ManufacturerResource.php

#[OA\Schema(schema: 'Manufacturer', description: 'A manufacturer resource')]
final readonly class ManufacturerResource implements ApiResourceInterface
{
    public function __construct(
        #[OA\Property(description: 'Manufacturer ULID')]
        public string $id,
        #[OA\Property(description: 'Manufacturer name')]
        public string $name,
        public bool $isActive,
        public ?string $createdAt,
    ) {}

    public static function fromEntity(Manufacturer $manufacturer): self
    {
        return new self(
            id: $manufacturer->getPublicId()->value(),
            name: $manufacturer->getName(),
            isActive: $manufacturer->isActive(),
            createdAt: $manufacturer->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt,
        ];
    }
}
```

**Conventions:**
- One Resource per entity; optionally a `ListResource` for index views (fewer fields)
- Implement `ApiResourceInterface` so `ApiResponse::collection()` can accept the class-string directly
- Use `fromEntity()` static factory to map from domain entity
- Use `toArray()` to produce the JSON-serializable output
- Use `#[OA\Schema]` and `#[OA\Property]` attributes for Swagger documentation
- Always expose `publicId` as `id`, never the internal auto-increment ID
- Format dates as ISO 8601 (`DateTimeInterface::ATOM`)

### Request Classes (Input DTOs)

Readonly classes for write operations, validated via Symfony Validator attributes:

```php
// src/Order/UI/Http/Api/Payload/CreateOrderPayload.php

final readonly class CreateOrderPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Customer is required.')]
        public string $customer,

        #[Assert\NotBlank(message: 'Shipping method is required.')]
        #[Assert\Choice(choices: ['THREE_DAY', 'NEXT_DAY'], message: 'Invalid shipping method.')]
        public string $shippingMethod,

        public ?string $customerOrderRef = null,
    ) {}
}
```

Used in controllers via `#[MapRequestPayload]`:

```php
public function create(
    #[MapRequestPayload] CreateOrderPayload $payload,
    // ...
): JsonResponse {
    // $payload is already validated
}
```

### ApiExceptionListener

Intercepts all exceptions for `/api/` routes and converts them to RFC 7807 `application/problem+json`:

| Exception Type | Status | Behaviour |
|---|---|---|
| `ValidationFailedException` (wrapped in `HttpException`) | 422 | Includes `violations` array |
| `HttpExceptionInterface` | Varies | Uses exception's status code |
| Any other exception | 500 | Generic message in prod, exception message in dev |

### Error Response Format

```json
{
    "type": "about:blank",
    "title": "Unprocessable Content",
    "status": 422,
    "detail": "One or more fields are invalid.",
    "violations": [
        { "propertyPath": "customer", "title": "Customer is required." },
        { "propertyPath": "shippingMethod", "title": "Invalid shipping method." }
    ]
}
```

## Adding a New API Endpoint

### Read-Only Endpoint (Index + Show)

1. Create Resource class in `src/{Context}/UI/Http/Api/Resource/`:

```php
#[OA\Schema(schema: 'Widget')]
final readonly class WidgetResource implements ApiResourceInterface
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    public static function fromEntity(Widget $widget): self { ... }
    public function toArray(): array { ... }
}
```

2. Create controller in `src/{Context}/UI/Http/Api/`:

```php
#[Route('/api/v1/widgets')]
#[OA\Tag(name: 'Widgets')]
class WidgetApiController extends AbstractApiController
{
    #[Route('', name: 'api_widget_index', methods: ['GET'])]
    public function index(
        Request $request,
        WidgetRepository $widgets,
        Paginator $paginator,
        #[MapQueryString] WidgetSearchCriteria $criteria = new WidgetSearchCriteria(),
    ): JsonResponse {
        $criteria->categoryId = $this->resolveFilterId($request, 'category', CategoryPublicId::class);

        $pager = $paginator->searchPagination($widgets, $criteria);

        return ApiResponse::collection(
            pager: $pager,
            resource: WidgetResource::class,
            request: $request,
        );
    }

    #[Route('/{id}', name: 'api_widget_show', methods: ['GET'])]
    public function show(
        #[ValueResolver('public_id')] Widget $widget,
    ): JsonResponse {
        $resource = WidgetResource::fromEntity($widget);

        return ApiResponse::item($resource->toArray());
    }
}
```

3. Add access control rule in `config/packages/security.yaml` if the endpoint should be public.

### Write Endpoint

1. Create Payload DTO in `src/{Context}/UI/Http/Api/Payload/`
2. Add controller action using `#[MapRequestPayload]` and `$this->handleResult()`
3. Reuse existing handler — no new domain logic needed

## Route Naming

API routes follow the pattern `api_{context}_{entity}_{action}`:

| Route Name | Path |
|---|---|
| `api_catalog_product_index` | `GET /api/v1/catalog/products` |
| `api_catalog_product_show` | `GET /api/v1/catalog/products/{id}` |
| `api_order_create` | `POST /api/v1/orders` |
| `api_order_item_add` | `POST /api/v1/orders/{orderId}/items` |

## Authentication

- **Public endpoints** (e.g., Catalog): Controlled via `access_control` in `security.yaml`, no token needed
- **Authenticated endpoints** (e.g., Orders): Require `ROLE_ADMIN`, enforced via `#[IsGranted('ROLE_ADMIN')]` on the controller class and `access_control` rules
- **Token format**: `Authorization: Bearer <64-char-hex-token>`

See [Security Documentation](../../08-security.md#api-authentication) for full details.

## Swagger / OpenAPI

NelmioApiDoc generates OpenAPI documentation from `#[OA\...]` attributes:

- **Swagger UI**: `/api/doc` (development only)
- **JSON spec**: `/api/doc.json`
- **Configuration**: `config/packages/nelmio_api_doc.yaml`

The Swagger UI is configured with Bearer token authentication, so you can test authenticated endpoints directly.

## File Locations

```
src/Shared/UI/Http/Api/
├── AbstractApiController.php                # Base controller
├── ApiResourceInterface.php                 # Resource contract (toArray)
├── ApiResponse.php                          # Response factory
└── EventListener/
    └── ApiExceptionListener.php             # RFC 7807 error handling

src/Shared/Infrastructure/Security/
├── ApiTokenHandler.php                      # Bearer token validation
└── ApiAuthenticationFailureHandler.php      # 401 JSON responses

src/{Context}/UI/Http/Api/
├── {Entity}ApiController.php                # Route handlers
├── Resource/
│   ├── {Entity}Resource.php                 # Detail view DTO
│   └── {Entity}ListResource.php             # List view DTO
└── Payload/
    ├── Create{Entity}Payload.php            # Write input DTO
    └── Update{Entity}Payload.php            # Write input DTO

config/packages/nelmio_api_doc.yaml          # Swagger configuration
config/packages/security.yaml                # API firewall + access control
config/routes.yaml                           # Swagger UI route
```

## Related Documentation

- [ADR 011: REST API Layer](../../adr/011-rest-api-layer.md) — Architectural decision and rationale
- [ADR 008: Server-Driven UI](../../adr/008-server-driven-ui-architecture.md) — Why the web UI doesn't use JSON
- [Security](../../08-security.md) — Authentication and authorization
- [FormFlow Pattern](../FormFlow/README.md) — The web UI equivalent of this pattern
