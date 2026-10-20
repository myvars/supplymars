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
    private ?string $apiTokenHash;   // SHA-256 hash of the API Bearer token
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
                token_handler: App\Customer\Infrastructure\Security\ApiTokenHandler
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

1. Each user can have one API token (64-character hex string). Only its SHA-256 hash is stored, in the `api_token_hash` column, so a database dump or backup holds no working token
2. Clients send the token in the `Authorization` header: `Authorization: Bearer <token>`
3. `ApiTokenHandler` hashes the token and looks up the user via `UserRepository::findByApiTokenHash()`
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
            token_handler: App\Customer\Infrastructure\Security\ApiTokenHandler
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

Issue a token with the console command (see [CLI Reference](09-cli-reference.md#appuserissue-token)):

```bash
symfony console app:user:issue-token admin@example.com   # prints a new token once
```

The token cannot be read back once issued. Running the command again replaces it, and the old token stops working. The user must be staff (`ROLE_ADMIN`) to reach the order endpoints.

In code, via the `User` entity:

```php
$user->regenerateApiToken();  // Stores the hash of a new token and returns the token
$user->setApiToken(null);     // Revokes API access
$user->hasApiToken();         // Whether a token is set
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
- `src/Customer/Infrastructure/Security/ApiTokenHandler.php`
- `src/Shared/Infrastructure/Security/ApiAuthenticationFailureHandler.php`
- `src/Customer/Domain/Model/User/User.php` (`api_token_hash` field)
- `src/Customer/UI/Console/IssueApiTokenCommand.php`

## Authorization Model

### Role Structure

| Role | Description | Assignment |
|------|-------------|------------|
| `ROLE_USER` | Base role | Automatic for all users |
| `ROLE_ADMIN` | Admin access | When `isStaff = true` |
| `ROLE_SUPER_ADMIN` | Full access including deletes and staff editing | By hand in phpMyAdmin (see below) |

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

public function setStaff(bool $isStaff): User
{
    $this->isStaff = $isStaff;

    if ($isStaff) {
        $this->roles[] = 'ROLE_ADMIN';
    } else {
        $key = array_search('ROLE_ADMIN', $this->roles, true);
        if (false !== $key) {
            unset($this->roles[$key]);
        }
    }

    return $this;
}

public function getRoles(): array
{
    $roles = $this->roles;
    // guarantee every user at least has ROLE_USER
    $roles[] = 'ROLE_USER';

    return array_unique($roles);
}
```

`ROLE_ADMIN` follows the "Member of Staff" box on the customer edit form. `ROLE_SUPER_ADMIN` has no path in the app: a super admin is set by hand in phpMyAdmin, on the `user` row:

- `is_staff` = `1`
- `roles` = `["ROLE_ADMIN","ROLE_SUPER_ADMIN"]`

Store both roles, as `setStaff()` would. The role hierarchy applies only to the logged-in token, not to the stored roles, so `User::isAdmin()` accepts either role itself. The playground reset (`scripts/playground-redact-staff.sql`) sets both on `admin@supplymars.com`.

Admin accounts (either role) cannot be deleted: `User::isDeletable()` is false for any `isAdmin()` user and for any customer with orders.

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
    - { path: ^/health$, roles: PUBLIC_ACCESS }

    # Admin routes
    - { path: ^/, roles: ROLE_ADMIN }
```

`/health` is public so an uptime monitor and the Docker healthcheck can call it. It reports only `ok` or `fail` per check, never the reason, and each outside caller may make 10 requests a minute (see [13-monitoring.md](13-monitoring.md)).

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
| PHPMyAdmin | http://localhost:8080 | This machine only (`127.0.0.1`) |
| PHPMyAdmin (live, playground) | http://localhost:8081, http://localhost:8082 | This machine only, through an SSH tunnel |
| Mailpit | http://localhost:8025 | This machine only (`127.0.0.1`) |

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
2. Sets `admin@supplymars.com` to `ROLE_ADMIN` + `ROLE_SUPER_ADMIN` (preserves existing password)
3. Creates/resets the demo user with `ROLE_ADMIN` and the public password `demo`

### Data Reset

The playground database resets nightly at 02:15 UTC (the `reset` container's cron), restoring a clean copy of production data with staff credentials redacted. See `Docs/infrastructure/playground.md` for details.

## Data Safety Assumptions

### What Is Protected

1. **Passwords:** Hashed with strong algorithms
2. **Sessions:** in Redis in every environment, through Symfony's Redis session handler on `REDIS_URL` (`framework.session.handler_id`). `symfony serve` needs Redis running to log in; `make up-dev-tools` starts it. Redis refuses connections without `REDIS_PASSWORD` (`compose.yaml` passes it to `requirepass`), so `REDIS_URL` must carry it
3. **CSRF tokens:** Required for Symfony forms, login, ActionFlow actions that call `->csrf()`, ConfirmFlow actions and the requests sent from JavaScript (product image upload and reorder, see [CSRF Protection](#csrf-protection)). The REST API is stateless and uses bearer tokens instead
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

The app sends the security headers, through `nelmio/security-bundle`, so `symfony serve`, the tests and production all use the same policy. A change that breaks the policy shows up as a violation in the browser console in development, not only after a deploy.

| Header | Value | Sent by |
|--------|-------|---------|
| `Content-Security-Policy` | see below | the app |
| `X-Frame-Options` | `SAMEORIGIN` | the app |
| `X-Content-Type-Options` | `nosniff` | the app |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | the app |
| `Strict-Transport-Security` | `max-age=63072000; includeSubDomains` | FrankenPHP (`docker/frankenphp/Caddyfile`) |

HSTS stays in the container's Caddyfile, and the host Caddy passes it on. In the bundle it comes with `forced_ssl`, which also redirects to HTTPS, and Caddy already does that. There is no `preload`: submitting the domain to the browsers' preload list is slow to undo. `includeSubDomains` means every subdomain of `supplymars.com` must serve HTTPS.

The app adds its headers only to responses that go through Symfony, including its 429s. What FrankenPHP answers itself carries only HSTS: static files (`public/assets/`, `public/bundles/`, and in development `public/uploads/` and `public/media/`) and its own refusals (403 for dotfiles and config files, 404 for a `.php` file other than `index.php`, 405 for `TRACE`). A missing file goes to Symfony, which answers 404 (or, under `/media/cache/resolve/`, generates the thumbnail). Production images are in S3 and never served by the container. Redirects carry no CSP, `X-Frame-Options` or `nosniff`: the bundle leaves them out, as they have no body.

### Content Security Policy

To change the policy, edit `csp.enforce` in `config/packages/nelmio_security.yaml`. It applies in every environment.

```yaml
# config/packages/nelmio_security.yaml
csp:
    enabled: true
    compat_headers: false
    enforce:
        level1_fallback: false
        default-src: ['self']
        script-src: ['self', 'unsafe-inline', 'data:', 'https://challenges.cloudflare.com']
        style-src: ['self', 'unsafe-inline']
        img-src: ['self', 'data:', 'https://unicorn-bucket-two.s3.eu-west-2.amazonaws.com']
        font-src: ['self']
        connect-src: ['self', 'https://challenges.cloudflare.com']
        frame-src: ['self', 'https://challenges.cloudflare.com']
        form-action: ['self']
        base-uri: ['self']
```

- **`script-src 'unsafe-inline'`**: the dark-mode script in `templates/base.html.twig`, which must run before the page is painted, and the inline `onload` of the kit's `Avatar:Image`.
- **`script-src data:`**: Asset Mapper's CSS loaders (`data:application/javascript,...`), for example Dropzone's CSS.
- **`style-src 'unsafe-inline'`**: `style=` attributes, Sonner and Turbo's progress bar.
- **`https://challenges.cloudflare.com`** in `script-src`, `connect-src` and `frame-src`: the Turnstile widget. `turnstile_controller.js` loads its script from there, and the widget runs in an iframe that calls back to it.
- **The S3 bucket host in `img-src`**: production stores product images and their thumbnails in S3. If the bucket or region changes, change the host here. Development stores them in `public/`, so the host does nothing there.

- **No CDN.** `/api/doc` inlines Swagger UI's files (`html_config.assets_mode: offline` in `config/packages/nelmio_api_doc.yaml`) rather than loading them from jsDelivr, and `templates/bundles/NelmioApiDocBundle/SwaggerUi/index.html.twig` drops the bundle's logo, which comes from the CDN in every mode. The FrankenPHP hot-reload scripts in `templates/base.html.twig` also come from jsDelivr and would be blocked; they load only when `FRANKENPHP_HOT_RELOAD` is set, which nothing here does yet.

The bundle leaves out a directive whose value equals `default-src`, so `font-src` does not appear in the header. Fonts still fall back to `default-src 'self'`.

`compat_headers: false` stops the bundle sending the old `X-Content-Security-Policy` header as well.

**No nonces yet.** Once a policy holds a nonce, browsers ignore `'unsafe-inline'`, and the dark-mode script and `Avatar:Image` would be blocked. The bundle adds a nonce only when a template calls `csp_nonce()`, so don't call it until both are changed. The debug toolbar adds none of its own while `'unsafe-inline'` is in the policy, so dev, test and production send the same `script-src`.

**The Symfony exception page in debug has no CSP.** Symfony removes the header from its own error page when debug is on (`ErrorListener::removeCspHeader`). Production error pages keep it. `tests/Shared/UI/Http/SecurityHeadersTest.php` checks a normal page and a 404 rendered without debug.

### SSL/TLS

**Development:** Self-signed certificates via Symfony CLI (`symfony serve`), or FrankenPHP's local certificate authority with `make up` ([02-setup-local.md](02-setup-local.md))

**Production:**
- TLSv1.2 and TLSv1.3 only
- Modern cipher suite (ECDHE, AES-GCM)
- The `php` container (FrankenPHP) serves plain HTTP on `127.0.0.1` only, so it is reachable only through Caddy
- HSTS enabled (sent by FrankenPHP, passed through by Caddy). The other security headers come from the app ([Security Headers](#security-headers))

### Trusted Proxies

Every production request reaches PHP from the host Caddy, so `REMOTE_ADDR` is the proxy's address and PHP sees no HTTPS flag. `config/packages/framework.yaml` trusts the proxy's headers:

```yaml
framework:
    trusted_proxies: '%env(TRUSTED_PROXIES)%'    # private_ranges in .env
    trusted_headers: ['x-forwarded-for', 'x-forwarded-proto']
```

- `$request->getClientIp()` is then the visitor's address. `login_throttling`, the `registration` limiter and the rate limits below count by it; without this, every visitor would be one IP, and one attacker could lock everyone out.
- Generated URLs and redirects use `https` when `X-Forwarded-Proto` is `https`.
- `private_ranges` is Symfony's list of private and reserved networks, which covers Docker's network. It also includes the documentation ranges (`192.0.2.0/24`, `198.51.100.0/24`, `203.0.113.0/24`), so tests use ordinary public addresses for visitors (`tests/Shared/UI/Http/TrustedProxyTest.php`).
- A client cannot claim another address: the host Caddy replaces any `X-Forwarded-For` a visitor sends, and the container is reachable only from the host. Only `X-Forwarded-For` and `X-Forwarded-Proto` are trusted; `Host` comes from the request as the host Caddy passes it on.

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
- Throws a `TooManyLoginAttemptsAuthenticationException`, which the login form shows as an authentication error
- State stored in the cache pool (Redis in production)
- Resets automatically after the interval expires

### Request Rate Limits

The app limits requests to a few public routes itself, with Symfony's RateLimiter, so development, tests and production behave the same. nginx did this before FrankenPHP replaced it ([ADR 016](adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md)). `App\Shared\UI\Http\EventListener\RateLimitListener` checks each request against the rules in `config/packages/rate_limiter.yaml` before routing and the firewall, so a refused request does no other work:

```yaml
# config/packages/rate_limiter.yaml (limiters under framework.rate_limiter)
app.rate_limits:
    - { path: '^/login$', limiter: login }
    - { path: '^/(register$|reset-password|verify/resend$)', methods: [POST], limiter: form_post }
    - { path: '^/health$', limiter: health, exempt_loopback: true }
    - { path: '^/health/backup$', limiter: health_backup }
```

| Path | Counts | Limit | Counted per |
|------|--------|-------|-------------|
| `/login` | All requests | 10 a minute (sliding window) | Client IP |
| `/register`, `/reset-password*`, `/verify/resend` | POSTs only | 2 a minute, burst of 3 (token bucket) | Client IP, across all three |
| `/health` | All requests except from loopback | 10 a minute (sliding window) | Client IP |
| `/health/backup` | All requests | 6 a minute (sliding window) | Client IP |

- Over the limit, the answer is `429 Too Many Requests` with `Retry-After`, so a rate limit is not mistaken for an outage.
- The form rule counts only POSTs, so loading a form, a Turbo prefetch of it, or the page a submit redirects to uses none of the allowance. When every request counted, Turbo's hover prefetch and the form load used up the allowance, so a submit within half a minute was refused. The burst of 3 lets a submit and two resubmissions after validation errors through at once.
- Every `/login` request counts: a failed login is a POST and the redirect back to the form, so at most five failed attempts a minute reach `login_throttling`, which then counts failures per IP and username.
- Sign-up has a second limit of its own: `RegistrationController` consumes the `registration` limiter (5 an hour per IP, defined in the same file) for each submitted form, and answers with a flash message rather than a 429.
- The counts are in the app cache (Redis). Tests use an in-memory pool that keeps its counts between the requests of one test (`config/packages/cache.yaml`); `tests/Shared/UI/Http/RateLimitTest.php` checks each rule.
- **Adding a limit:** define a limiter under `framework.rate_limiter`, and add a rule that names it to `app.rate_limits`. A rule's `path` is a regular expression, as in `access_control`.

### Defence in Depth

The layers complement each other:

| Layer | Tracks By | Limit |
|-------|-----------|-------|
| Request rate limits | IP address | 2–10 req/min (by endpoint) |
| `registration` limiter | IP address | 5 sign-up submissions/hour |
| `login_throttling` | IP + username | 5 failed attempts/15 min |

The request limits catch high-volume automated attacks. `login_throttling` catches targeted brute-force against specific accounts.

## Input Validation

### Domain Validation

The entity's constraints are the source of truth. Every user-entered string column has a `Length` rule, so an
over-length value is refused before it reaches the database. A `VARCHAR` rule matches the column. A `TEXT` column
holds 65,535 bytes, so its rule is 10,000 characters, which fits even at 4 bytes per character (`Message.body`,
`Pool.description`, `ProductReview.moderationNotes`), or a lower business limit (`ProductReview.body`, 2,000).
Create, update, reply and reject handlers validate the entity before they flush and return `Result::fail()` when it
is invalid, so nothing is saved:

```php
// src/Purchasing/Domain/Model/Supplier/Supplier.php

#[ORM\Column(length: 255)]
#[Assert\NotBlank(message: 'Please enter a supplier name')]
#[Assert\Length(max: 255, maxMessage: 'Max 255 characters')]
private ?string $name = null;
```

### Form Validation

A form validates its form model, not the entity. A rule the user can break from the form (usually `NotBlank`
and `Length`) is repeated on the form model, so the error appears next to the field. Without it, the handler's
check still stops the save, but the error comes back as a flash message:

```php
// src/Purchasing/UI/Http/Form/Model/SupplierForm.php

#[Assert\NotBlank(message: 'Please enter a supplier name')]
#[Assert\Length(max: 255, maxMessage: 'Max 255 characters')]
public ?string $name = null;
```

Repeat only those rules. An API payload does the same, for the same reason (e.g. `customerOrderRef` on
`CreateOrderPayload`). An inline edit runs no handler, so it names the entity property it saves to and the flow
checks the value against that property's rules (see `Docs/patterns/InlineEdit/README.md`).

### File Uploads

Product images are uploaded through a Dropzone control. Its limits run in the browser, so a hand-written
request skips them. The server checks every upload again; its rules are the ones that count, and the
Dropzone values (`assets/controllers/dropzone_controller.js`) only mirror them:

```php
// src/Catalog/Application/Handler/ProductImage/CreateProductImageHandler.php

public const array ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
public const string MAX_SIZE = '2Mi';

$constraint = new Image(
    maxSize: self::MAX_SIZE,
    filenameMaxLength: 255,
    extensions: self::ALLOWED_EXTENSIONS,
    maxSizeMessage: 'The image is larger than 2 MB.',
    // ...and short messages for the other cases
);
```

- **Type:** the constraint's `extensions` option checks the filename's extension *and* that the detected
  content type belongs to it. An `.svg` file is rejected, and so is HTML renamed to `photo.jpg`.
- **Size:** 2 MiB per file. PHP accepts 4 MiB per request (`post_max_size` and `upload_max_filesize` in
  `docker/php/conf.d/10-app.ini`). To allow more, raise those as well as the constant and its messages.
  PHP drops a bigger request's body before the app sees it, CSRF token included, so the request is
  refused with a `403` and no flash. Dropzone sends up to two files per request (its `parallelUploads`
  default), so two files close to the limit can together exceed 4 MiB; the page then refreshes without
  saying why (nginx's `413` before it did the same).
- **Validate first:** if one file in a request fails validation, none of that request's files are
  stored. Dropzone sends files two at a time, so other requests in the same drop can still succeed.
  The response is `422` with the reason in `error`, which Dropzone shows on the file, and the error
  flash names the file and the reason.
- **Stored name:** `UploadHelper` takes the extension from the detected content, never from the client's
  filename, because the extension decides which content type the file is served with. It also passes
  the detected type as `mimetype`, which the S3 adapter sends as the object's `Content-Type`. This is not
  a check in itself: `UploadHelper` stores whatever it is given, so every caller must validate with an
  `extensions` allowlist first. Images stored before this rule keep their names.
- **Serving:** uploads are public files (`public/uploads/` locally, the S3 bucket in production) and are
  served directly. This is safe only because every allowed type is one a browser never runs as script.
  **Never add `svg`, `html` or `xml` to the list.** Files that need those types, or that only some users
  may read, must be stored outside the public bucket and sent through a controller with
  `Content-Disposition: attachment`.

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

### Actions and Delete Operations

A flow action that changes state is a `POST` with a token, never a `GET` link: a link can be followed
from an email or another page, or re-run by the browser when it restores a tab.

`ActionFlow` checks the token only when the context names one with `->csrf()`; an action without that
call is not protected. The id is given in full, so each action on each entity has its own:

```php
// src/Review/UI/Http/Controller/ReviewController.php

context: FlowContext::forAction('app_review_show', ['id' => $review->getPublicId()->value()])
    ->csrf('approve' . $review->getPublicId()->value()),
```

`ConfirmFlow` (delete and other confirm-then-act operations) uses the same check. Its token id is the
action's confirm key (defaults to `'delete'`) followed by the command id.

A wrong or missing token gives an "Invalid CSRF token." flash and a redirect; the handler is not called.

### Requests Sent from JavaScript

A request sent from JavaScript carries its token too, and the endpoint checks it before doing anything
else:

- **Image upload:** the upload form renders a hidden `_token` field. Dropzone posts a form's other fields
  with the files, so the same field serves Dropzone and the plain form it falls back to. An invalid
  token is a `403`.
- **Image reorder:** the page renders the token into a Stimulus value, and the `sortable` controller
  sends it in the `X-CSRF-Token` header.

```php
// src/Catalog/UI/Http/Controller/ProductImageController.php

if (!$this->isCsrfTokenValid('product_image_upload', $request->request->getString('_token'))) {
    throw $this->createAccessDeniedException('Invalid CSRF token.');
}
```
