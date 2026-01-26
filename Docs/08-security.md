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
    private array $roles;            // Symfony roles
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

### Password Reset

1. User requests reset at `/reset-password`
2. Token generated and emailed
3. Token stored in session (not URL) for security
4. User submits new password
5. Token invalidated after use

**Reset controller:** `src/Customer/UI/Http/Controller/ResetPasswordController.php`

## Authorization Model

### Role Structure

| Role | Description | Assignment |
|------|-------------|------------|
| `ROLE_USER` | Base role | Automatic for all users |
| `ROLE_ADMIN` | Admin access | When `isStaff = true` |

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
    # Public routes
    - { path: ^/login, roles: PUBLIC_ACCESS }
    - { path: ^/reset-password, roles: PUBLIC_ACCESS }

    # Admin routes (example)
    - { path: ^/admin, roles: ROLE_ADMIN }
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

## Data Safety Assumptions

### What Is Protected

1. **Passwords:** Hashed with strong algorithms
2. **Sessions:** Stored in Redis with encryption
3. **CSRF tokens:** Required for all form submissions
4. **Remember-me tokens:** Signed with kernel secret

### What Is Not Protected

This is a demonstration system with intentional simplifications:

1. **No rate limiting:** Login throttling disabled
2. **No 2FA:** Single-factor authentication only
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

add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

### SSL/TLS

**Development:** Self-signed certificates via Symfony CLI

**Production:**
- TLSv1.2 and TLSv1.3 only
- Modern cipher suite (ECDHE, AES-GCM)
- HSTS enabled

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
