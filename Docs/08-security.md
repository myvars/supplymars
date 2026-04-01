# Security Documentation

This document covers authentication, authorization, and security considerations for SupplyMars.

## Authentication Model

### User Entity

Users are managed by the `Customer` bounded context:

```php
// src/Customer/Domain/Model/User/User.php

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private string $email;           // Unique identifier
    private string $password;        // Hashed password
    private string $fullName;        // Display name
    private bool $isVerified;        // Email verification status
    private bool $isStaff;           // Admin flag
    private bool $isSimulated;       // Created by simulator (not a real registration)
    private array $roles;            // Symfony roles
    private ?string $apiToken;       // API Bearer token (64-char hex)
}
```

### Login Flow

1. User navigates to `/login`
2. Enters email and password
3. Symfony Security authenticates against database
4. Session created with remember-me cookie (7 days)

**Controller:** `src/Customer/UI/Http/Controller/SecurityController.php`

### Security Configuration

```yaml
# config/packages/security.yaml

security:
    providers:
        app_user_provider:
            entity:
                class: App\Customer\Domain\Model\User\User
                property: email

    firewalls:
        api:
            pattern: ^/api/
            stateless: true
            entry_point: App\Shared\Infrastructure\Security\ApiAuthenticationFailureHandler
            access_token:
                token_handler: App\Shared\Infrastructure\Security\ApiTokenHandler
                failure_handler: App\Shared\Infrastructure\Security\ApiAuthenticationFailureHandler
        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_login
                check_path: app_login
                username_parameter: email
                enable_csrf: true
            logout:
                path: app_logout
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800  # 7 days
                always_remember_me: true
            login_throttling:
                max_attempts: 5
                interval: '15 minutes'
```

### Password Handling

- **Hashing:** Automatic via `UserPasswordHasherInterface`
- **Algorithm:** Auto-detected (bcrypt or argon2)
- **Test environment:** Reduced cost (bcrypt: 4) for speed

### Email Verification

New users must verify their email:

1. Registration creates unverified user
2. Verification email sent with signed URL
3. User clicks link → `isVerified = true`
4. Unverified users may have restricted access (configurable)

**Verification controller:** `src/Customer/UI/Http/Controller/RegistrationController.php`

### Admin Access Notification

When a user account is granted staff/admin access (via the customer edit form), an email notification is sent automatically:

1. `UpdateCustomerHandler` detects `isStaff` changed from `false` to `true`
2. `MailerHelper::sendAdminAccessGrantedMessage()` sends the notification
3. Email informs the user they now have full dashboard access

**Template:** `templates/customer/admin-access-granted.html.twig`
**Handler:** `src/Customer/Application/Handler/UpdateCustomerHandler.php`

### Password Reset

1. User requests reset at `/reset-password`
2. Token generated and emailed
3. Token stored in session (not URL) for security
4. User submits new password
5. Token invalidated after use

**Reset controller:** `src/Customer/UI/Http/Controller/ResetPasswordController.php`

## API Authentication

### Overview

The REST API uses **stateless Bearer token authentication**, separate from the session-based web login. API requests never create sessions or set cookies.

### How It Works

1. Each user has an optional `api_token` field (64-character hex string)
2. Clients send the token in the `Authorization` header: `Authorization: Bearer <token>`
3. `ApiTokenHandler` looks up the user by token via `UserRepository::findByApiToken()`
4. If the token is invalid or missing, `ApiAuthenticationFailureHandler` returns a 401 RFC 7807 JSON response

### Security Configuration

```yaml
# config/packages/security.yaml

firewalls:
    api:
        pattern: ^/api/
        stateless: true
        entry_point: App\Shared\Infrastructure\Security\ApiAuthenticationFailureHandler
        access_token:
            token_handler: App\Shared\Infrastructure\Security\ApiTokenHandler
            failure_handler: App\Shared\Infrastructure\Security\ApiAuthenticationFailureHandler
```

### Access Control

```yaml
access_control:
    - { path: ^/api/doc, roles: PUBLIC_ACCESS }        # Swagger UI
    - { path: ^/api/v1/catalog, roles: PUBLIC_ACCESS }  # Public catalog
    - { path: ^/api/, roles: ROLE_ADMIN }               # All other API routes
```

- **Catalog endpoints** are publicly accessible (no token required)
- **Order endpoints** and any future write APIs require `ROLE_ADMIN`
- **Swagger documentation** is publicly accessible in development

### Token Management

Tokens are managed via the `User` entity:

```php
$user->regenerateApiToken();  // Generates new 64-char hex token
$user->getApiToken();         // Returns current token (or null)
$user->setApiToken(null);     // Revokes API access
```

### Error Responses

All API authentication errors return RFC 7807 `application/problem+json`:

```json
{
    "type": "about:blank",
    "title": "Unauthorized",
    "status": 401,
    "detail": "Authentication required."
}
```

**Key files:**
- `src/Shared/Infrastructure/Security/ApiTokenHandler.php`
- `src/Shared/Infrastructure/Security/ApiAuthenticationFailureHandler.php`
- `src/Customer/Domain/Model/User/User.php` (`api_token` field)

## Authorization Model

### Role Structure

| Role | Description | Assignment |
|------|-------------|------------|
| `ROLE_USER` | Base role | Automatic for all users |
| `ROLE_ADMIN` | Admin access | When `isStaff = true` |
| `ROLE_SUPER_ADMIN` | Full access including deletes and staff editing | Manual assignment |

### Role Hierarchy

```yaml
# config/packages/security.yaml
role_hierarchy:
    ROLE_SUPER_ADMIN: ROLE_ADMIN
```

`ROLE_SUPER_ADMIN` inherits all `ROLE_ADMIN` permissions. The distinction controls destructive operations: only super admins can delete entities or modify staff accounts.

### Role Assignment

```php
// src/Customer/Domain/Model/User/User.php

public function setStaff(bool $staff): void
{
    $this->isStaff = $staff;
    $this->roles = $staff
        ? ['ROLE_USER', 'ROLE_ADMIN']
        : ['ROLE_USER'];
}

public function getRoles(): array
{
    // Guarantee ROLE_USER is always present
    $roles = $this->roles;
    $roles[] = 'ROLE_USER';
    return array_unique($roles);
}
```

### Access Control

Most admin pages require `ROLE_ADMIN`:

```php
// src/Customer/UI/Http/Controller/CustomerController.php

#[Route('/customer', name: 'app_customer_')]
#[IsGranted('ROLE_ADMIN')]
class CustomerController extends AbstractController
{
    // All routes require admin
}
```

### Route-Level Security

```yaml
# config/packages/security.yaml

access_control:
    # API routes
    - { path: ^/api/doc, roles: PUBLIC_ACCESS }
    - { path: ^/api/v1/catalog, roles: PUBLIC_ACCESS }
    - { path: ^/api/, roles: ROLE_ADMIN }

    # Public routes
    - { path: ^/login, roles: PUBLIC_ACCESS }
    - { path: ^/playground/login, roles: PUBLIC_ACCESS }
    - { path: ^/reset-password, roles: PUBLIC_ACCESS }
    - { path: ^/help, roles: PUBLIC_ACCESS }

    # Admin routes
    - { path: ^/, roles: ROLE_ADMIN }
```

## Admin Surface vs. Internal Tools

### Admin Surface

The admin interface (`/catalog/*`, `/order/*`, `/purchasing/*`, etc.) is designed for staff users:

- Requires `ROLE_ADMIN`
- Full CRUD operations
- Business reporting
- Configuration management

### Internal Tools

Development/operations tools with restricted access:

| Tool | URL | Access |
|------|-----|--------|
| PHPMyAdmin | http://localhost:8080 | Local network only |
| Mailpit | http://localhost:8025 | Local network only |
| RabbitMQ | http://localhost:15672 | Credentials required |

**Production:** These tools are not exposed publicly. Use SSH tunneling if needed.

## Playground Restrictions

The playground uses role-based restrictions to protect the demo environment. The demo user has `ROLE_ADMIN` (can browse, create, and edit) while the site owner has `ROLE_SUPER_ADMIN` (unrestricted access).

### What ROLE_ADMIN Can Do

- **Browse:** All pages, reports, and dashboards
- **Create:** Products, demo orders, suppliers, and other entities via FormFlow
- **Edit:** Non-staff customer accounts and all other entities
- **Confirmed actions:** Cancel orders, remove supplier product mappings, rewind purchase orders
- **Forms:** Full form submissions (validation, flash messages, redirects)

### What Only ROLE_SUPER_ADMIN Can Do

| Restriction | Scope | Feedback for ROLE_ADMIN |
|---|---|---|
| **Delete entities** | Each delete handler individually | Error flash: "Deleting is disabled for this user." |
| **Edit staff accounts** | Users with `isStaff = true` | Error flash: "Staff accounts cannot be modified in the playground." |
| **Create orders manually** | `OrderController::new` (`#[IsGranted('ROLE_SUPER_ADMIN')]`) | HTTP 403 (button hidden for non-super-admins) |

File uploads are additionally blocked by `PLAYGROUND_MODE` environment variable regardless of role.

### Implementation

- **Delete handlers** (e.g. `DeleteProductHandler`, `DeleteCategoryHandler`, etc.): Each handler checks `ROLE_SUPER_ADMIN` before removing the entity. Returns `Result::fail()` which the flow renders as a standard error flash. This is handler-level rather than flow-level because `DeleteFlow` is also used for non-delete confirmed actions (cancel, remove, rewind) that `ROLE_ADMIN` should be able to perform.
- **UpdateCustomerHandler** (`src/Customer/Application/Handler/UpdateCustomerHandler.php`): Checks `ROLE_SUPER_ADMIN` and target user's `isStaff()` before applying changes. Returns `Result::fail()` which FormFlow renders as a standard error flash.
- **UploadHelper** (`src/Shared/Infrastructure/FileStorage/UploadHelper.php`): Throws `CannotWriteFileException` when `PLAYGROUND_MODE=true`.

### Playground Account Setup

The `playground-redact-staff.sql` script runs after each nightly database restore:
1. Scrambles all staff credentials except `admin@supplymars.com` and `demo@supplymars.com`
2. Promotes `admin@supplymars.com` to `ROLE_SUPER_ADMIN` (preserves existing password)
3. Creates/resets the demo user with `ROLE_ADMIN` and the public password `demo`

### Data Reset

The playground database resets nightly at 02:15 UTC via cron, restoring a clean copy of production data with staff credentials redacted. See `Docs/infrastructure/playground.md` for details.

## Data Safety Assumptions

### What Is Protected

1. **Passwords:** Hashed with strong algorithms
2. **Sessions:** Stored in Redis with encryption
3. **CSRF tokens:** Required for all form submissions
4. **Remember-me tokens:** Signed with kernel secret

### What Is Not Protected

This is a demonstration system with intentional simplifications:

1. **No 2FA:** Single-factor authentication only
3. **No audit of admin actions:** Status changes logged, but not user sessions
4. **Simulation data:** Fabricated, not real customer data

### Database Security

**Development:**
- Root credentials (`root:password`)
- Local access only

**Production (recommended):**
- Non-root application user
- Strong passwords via environment variables
- Network isolation (private subnet)
- Encrypted connections (SSL)

## Simulation Safety

### No Real Payments

SupplyMars does not integrate with payment processors:

- No credit card handling
- No payment gateway calls
- Orders are created without payment validation

### No Real Inventory

Stock levels are simulated:

- No warehouse management integration
- No EDI connections
- Stock fluctuates via console commands

### No Real Customers

Customer data is fabricated:

- Email addresses are test domains
- Addresses are Mars-themed
- Orders are generated, not placed by real users
- Simulated users are flagged with `isSimulated = true` on the User entity
- Users created via the public registration form have `isSimulated = false`
- The `getRandomUser()` repository method excludes staff users from simulation selection

### Email Handling

**Development:**
- All emails captured by Mailpit
- `DEV_MAIL_RECIPIENT` overrides all recipients

**Production:**
- Ensure simulation commands don't send real emails
- Or configure dedicated simulation email addresses

## Security Headers

### Nginx Configuration

```nginx
# docker/nginx/conf.d/prod.conf

add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; form-action 'self'; base-uri 'self'" always;
```

### Content Security Policy

The CSP header includes `'unsafe-inline'` for both `script-src` and `style-src`:

- **`script-src 'unsafe-inline'`** — Required for the dark-mode FOUC-prevention script in `templates/base.html.twig` (lines 4-8), which must run before page paint to avoid a flash of unstyled content.
- **`style-src 'unsafe-inline'`** — Required for Tailwind CSS runtime style injection and Flowbite component styles.

Removing `unsafe-inline` would require nonce-based CSP, which Symfony's Asset Mapper does not currently support. If Asset Mapper adds nonce support in a future release, the inline script should be migrated and `unsafe-inline` removed.

### SSL/TLS

**Development:** Self-signed certificates via Symfony CLI

**Production:**
- TLSv1.2 and TLSv1.3 only
- Modern cipher suite (ECDHE, AES-GCM)
- HSTS enabled

## Rate Limiting

### Login Throttling (Symfony)

Login attempts are throttled at the application layer using Symfony's built-in `login_throttling`:

```yaml
# config/packages/security.yaml

login_throttling:
    max_attempts: 5
    interval: '15 minutes'
```

- Tracks failed attempts per **IP + username** combination
- After 5 failed attempts within 15 minutes, further login attempts are blocked
- Returns a `TooManyLoginAttemptsAuthenticationException` with a `Retry-After` header
- State stored in the cache pool (Redis in production)
- Resets automatically after the interval expires

### Nginx Rate Limiting

All public auth endpoints are rate-limited at the Nginx layer (production only):

```nginx
# docker/nginx/conf.d/prod.conf

limit_req_zone $binary_remote_addr zone=login:10m rate=10r/m;
limit_req_zone $binary_remote_addr zone=register:10m rate=2r/m;

location = /login {
    limit_req zone=login burst=5 nodelay;
}

location = /register {
    limit_req zone=register burst=1 nodelay;
}

location ^~ /reset-password {
    limit_req zone=register burst=3 nodelay;
}

location = /verify/resend {
    limit_req zone=register burst=3 nodelay;
}
```

| Endpoint | Zone | Limit | Burst | Rationale |
|----------|------|-------|-------|-----------|
| `/login` | `login` | 10/min per IP | 5 | Moderate — legitimate users may retry |
| `/register` | `register` | 2/min per IP | 1 | Tight — registration also gated by app-level limiter |
| `/reset-password/*` | `register` | 2/min per IP | 3 | Tight — prevents email-sending abuse; burst accommodates Turbo navigation |
| `/verify/resend` | `register` | 2/min per IP | 3 | Tight — prevents verification email abuse; burst accommodates Turbo navigation |

- Returns `503 Service Temporarily Unavailable` when exceeded
- Not applied in development (dev Nginx config has no rate limiting)

### Defence in Depth

The two layers complement each other:

| Layer | Scope | Tracks By | Limit |
|-------|-------|-----------|-------|
| Nginx | Reverse proxy | IP address | 2–10 req/min (by endpoint) |
| Symfony | Application | IP + username | 5 attempts/15 min (login only) |

Nginx catches high-volume automated attacks. Symfony catches targeted brute-force against specific accounts.

## Input Validation

### Form Validation

Symfony forms validate input using constraints:

```php
class CustomerForm
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    public ?string $fullName = null;
}
```

### Domain Validation

Entity constraints enforced at domain level:

```php
// src/Catalog/Domain/Model/Product/Product.php

#[Assert\NotBlank]
private string $name;

#[Assert\Range(min: 0, max: 10000)]
private int $stock;

#[Assert\PositiveOrZero]
private string $cost;
```

### SQL Injection Prevention

- All queries use Doctrine ORM
- No raw SQL without parameterized queries
- Repository methods use QueryBuilder

## CSRF Protection

### Form CSRF

All Symfony forms include CSRF tokens:

```yaml
# config/packages/framework.yaml
framework:
    csrf_protection: true
```

### Login CSRF

Form login validates CSRF token:

```yaml
form_login:
    enable_csrf: true
```

### Delete Operations

DeleteFlow validates CSRF for destructive operations:

```php
// src/Shared/UI/Http/FormFlow/DeleteFlow.php

public function delete(...): Response
{
    if (!$this->csrfTokenManager->isTokenValid(
        new CsrfToken($context->tokenId(), $request->get('_token'))
    )) {
        throw new BadRequestHttpException('Invalid CSRF token');
    }
}
```
