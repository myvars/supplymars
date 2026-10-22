# REST API

The JSON API under `/api/v1/`: authentication, response format, endpoints and how to add one. Read before adding or changing an endpoint, or calling the API. API controllers call the same handlers and repositories as the web controllers and add no domain logic; [ADR 011](../../adr/011-rest-api-layer.md) records the design and why.

## How a request runs

| Step | What happens | Where |
|------|--------------|-------|
| Firewall | `api` firewall on `^/api/`: stateless, bearer token, no session or cookie | `config/packages/security.yaml` |
| Input | Body into a Payload with `#[MapRequestPayload]` (deserialised and validated); query into a `SearchCriteria` with `#[MapQueryString]`; route ids through `#[ValueResolver('public_id')]` | the controller |
| Read | Repository → `Paginator::searchPagination()` → Resource → `ApiResponse::collection()` / `item()` | `src/Shared/Infrastructure/Persistence/Search/Paginator.php` |
| Write | Payload → command → the web flow's handler → `handleResult()` | `src/Shared/UI/Http/Api/AbstractApiController.php` |
| Errors | Any exception under `/api/` → RFC 7807 Problem Details | `ApiExceptionListener` |

## Authentication

The `api` firewall takes a bearer token. Each user can have one token, stored as a hash (`User.apiTokenHash`, column `api_token_hash`). `ApiTokenHandler` hashes the incoming token and looks the user up by that hash (`UserRepository::findByApiTokenHash()`).

```bash
symfony console app:user:issue-token admin@supplymars.com
curl -H "Authorization: Bearer <token>" https://127.0.0.1:8000/api/v1/orders
```

- What the command prints, and why the token cannot be shown again: [09-cli-reference.md](../../09-cli-reference.md#appuserissue-token).
- Running it again replaces the user's token, and the old one stops working. There is no revoke command: to revoke without issuing a new one, call `$user->setApiToken(null)` and flush.
- The order endpoints need `ROLE_ADMIN`, so issue tokens to staff users ([08-security.md](../../08-security.md#users-and-roles)).

### Access

| Path | Access |
|------|--------|
| `/api/doc` (Swagger UI), `/api/doc.json` (OpenAPI spec) | Public, in every environment |
| `/api/v1/catalog/*` | Public, no token needed |
| `/api/v1/orders*` | `ROLE_ADMIN`: `access_control` and `#[IsGranted('ROLE_ADMIN')]` on `OrderApiController` and `OrderItemApiController` |

| Status | When | `detail` | Sent by |
|--------|------|----------|---------|
| `401` | No `Authorization` header on a protected route | `"Authentication required."` | `ApiAuthenticationFailureHandler::start()` (firewall entry point) |
| `401` | A token that matches no user | `"Invalid API token."` | `ApiAuthenticationFailureHandler::onAuthenticationFailure()` |
| `403` | A valid token for a user without `ROLE_ADMIN` | | `ApiExceptionListener` |

Gotchas:

- **A bad token is refused even on a public route.** The firewall checks any `Authorization: Bearer` header it is sent, so `/api/v1/catalog/products` with a wrong token is a `401`, not the public list.
- In tests, `->actingAs($user, 'api')` attaches the user to the `api` firewall, so no token is needed; `tests/Shared/UI/Http/Api/ApiAuthenticationTest.php` tests the header itself. See [07-testing.md](../../07-testing.md).

## Response format

| Kind | Body |
|------|------|
| Single resource | `{"data": {...}}`, `200`, or `201` on create |
| Collection | `{"data": [...], "meta": {"page", "limit", "total"}, "links": {"self", "next", "prev"}}` (`next`/`prev` are `null` at the ends) |
| No content | `204`, empty body (remove an order item) |
| Error | RFC 7807 Problem Details, `Content-Type: application/problem+json`, with `type`, `title`, `status`, `detail` |

- Every resource exposes the ULID `publicId` as `id`, never the auto-increment id. Related records are nested with their own `id`.
- Dates are ISO 8601 (`DateTimeInterface::ATOM`).

### Errors

| Status | When | `title` / `detail` | Sent by |
|--------|------|--------------------|---------|
| `404` | No record with that ULID | `"Not Found"` / e.g. `Product "…" not found.` | `ApiExceptionListener` |
| `422` | The payload fails validation | `"Validation Failed"` / `"One or more fields are invalid."`, plus `violations: [{propertyPath, title}]` | `ApiExceptionListener` |
| `422` | The handler returned `Result::fail()`, or a ULID in the payload matches nothing | `"Unprocessable Content"` / the result's message, or e.g. `"Customer not found."` | `AbstractApiController::handleResult()`, `ApiResponse::error()` |
| `500` | Anything else | `"Internal Server Error"` / the exception message, hidden in `prod` | `ApiExceptionListener` |

```json
{
    "type": "about:blank",
    "title": "Validation Failed",
    "status": 422,
    "detail": "One or more fields are invalid.",
    "violations": [{"propertyPath": "shippingMethod", "title": "Invalid shipping method."}]
}
```

`ApiExceptionListener` runs at priority `-1`, only for paths starting `/api/`, and leaves a response another listener already set alone.

## Endpoints

| Method | Path | Body | Status |
|--------|------|------|--------|
| `GET` | `/api/v1/catalog/{categories,subcategories,manufacturers,products}` | | 200 |
| `GET` | `/api/v1/catalog/{categories,subcategories,manufacturers,products}/{id}` | | 200 |
| `GET` | `/api/v1/orders` | | 200 |
| `GET` | `/api/v1/orders/{id}` | | 200 |
| `POST` | `/api/v1/orders` | `{"customer": "<user ULID>", "shippingMethod": "THREE_DAY" \| "NEXT_DAY", "customerOrderRef": "…"}` | 201 |
| `POST` | `/api/v1/orders/{id}/cancel` | | 200 |
| `POST` | `/api/v1/orders/{id}/allocate` | | 200 |
| `POST` | `/api/v1/orders/{orderId}/items` | `{"product": "<product ULID>", "quantity": 2}` | 201 |
| `PUT` | `/api/v1/orders/{orderId}/items/{itemId}` | `{"quantity": 3}` | 200 |
| `DELETE` | `/api/v1/orders/{orderId}/items/{itemId}` | (cancels the item) | 204 |

List query parameters are the web search criteria's: `page`, `limit` (default 5, max 50, `SearchCriteria`), `sort` (the criteria's `SORT_OPTIONS`), `sortDirection` (`ASC`/`DESC`), `query`, the criteria's own filters, and ULID filters: `category`, `subcategory`, `manufacturer` on products, `category` on subcategories, `customer` on orders. `symfony console debug:router | grep api_` lists the routes; `/api/doc` shows the fields.

Gotchas:

- **A filter ULID that matches nothing is ignored**, so the list comes back unfiltered: `resolveFilterId()` returns `null` for it. An unknown `sort` falls back to the default the same way.
- **A malformed id (not a ULID), in the path or a filter, gives a `500`**, not a `404` or `422`.
- **The order item routes do not check that the item belongs to the order in the path.** They check that both exist, then act on the item.

## Adding an endpoint

```text
src/{Context}/UI/Http/Api/
├── {Entity}ApiController.php   extends AbstractApiController
├── Resource/                   {Entity}Resource, {Entity}ListResource (fewer fields, for lists)
└── Payload/                    {Action}{Entity}Payload: request body DTOs
```

1. Create a Resource: a `final readonly` class implementing `ApiResourceInterface`, with `fromEntity()` and `toArray()`, and `#[OA\Schema]`/`#[OA\Property]` attributes. `ApiResponse::collection()` takes its class-string.
2. Create the controller with `#[Route('/api/v1/…')]` and `#[OA\Tag]`. Route names are the web route's with `api_` for `app_`: `api_catalog_product_index`, `api_order_create`, `api_order_item_add`.
3. For a list, take a `SearchCriteria` with `#[MapQueryString]`, set ULID filters with `resolveFilterId()`, and paginate with `Paginator::searchPagination()`.
4. For a write, take a Payload with `#[MapRequestPayload]`. Payload fields are what the client sends, ULIDs included; the controller resolves them to entities (answering `422` "… not found." when one matches nothing), builds the command and calls the existing handler. Return `handleResult($result, onSuccess: $resource)`, or `ApiResponse::item(…, 201)` on create.
5. Repeat on the Payload the entity rules a client can break, so they come back as `violations` ([08-security.md](../../08-security.md#validation)).
6. A route that needs a login: add `#[IsGranted('ROLE_ADMIN')]`. A public one: add a `PUBLIC_ACCESS` rule above `^/api/` in `access_control`.
7. Add `#[OA\Get]`/`#[OA\Post]`… so it appears in `/api/doc`, and write tests in `tests/{Context}/UI/Api/`.

## Shared classes

| Class | Purpose |
|-------|---------|
| `AbstractApiController` | `handleResult()`: `Result` → `200`/`201` with the resource, or a `422` problem; `resolveFilterId()`: ULID query parameter → internal id |
| `ApiResponse` | Static factory: `item()`, `collection()`, `error()`, `noContent()` |
| `ApiResourceInterface` | Contract for resources (`toArray()`) |
| `ApiExceptionListener` | Exceptions → Problem Details under `/api/` |
| `ApiAuthenticationFailureHandler` | The two JSON `401` responses |
| `ApiTokenHandler` | Hashes the bearer token and finds its user |

## Swagger / OpenAPI

NelmioApiDoc builds the spec from the `#[OA\...]` attributes. `/api/doc` and `/api/doc.json` are routed (`config/routes.yaml`) and public in every environment, production included. The title, the Bearer scheme (so the UI's "Authorize" button can call order endpoints) and the `^/api/` area are configured under `when@dev` in `config/packages/nelmio_api_doc.yaml`, so only development has them. Swagger UI's files are inlined, not loaded from a CDN ([08-security.md](../../08-security.md#content-security-policy)).

## Related

- [ADR 011](../../adr/011-rest-api-layer.md): why the API reuses the handlers and uses bearer tokens
- [08-security.md](../../08-security.md): roles, `access_control`, rate limits
- [09-cli-reference.md](../../09-cli-reference.md): `app:user:issue-token`
- [FormFlow](../FormFlow/README.md): the web equivalent
