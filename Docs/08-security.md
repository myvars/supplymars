# Security

Who can log in and do what, the headers and limits the app sets, and the checks on input. Read before adding a route, a role check, an upload, a state-changing action or an email. Configuration is in `config/packages/security.yaml`; the API's token authentication is in [patterns/API/README.md](patterns/API/README.md#authentication).

## Users and roles

Users are `App\Customer\Domain\Model\User\User`, logged in by email (`SecurityController`, form login on `/login`).

| Role | Who has it | Set by |
|------|-----------|--------|
| `ROLE_USER` | Every user, including customers who sign up on `/register` | `User::getRoles()` adds it |
| `ROLE_ADMIN` | Staff | The "Member of Staff" box on the customer edit form (`User::setStaff()`) |
| `ROLE_SUPER_ADMIN` | The site owner | By hand in phpMyAdmin, on the `user` row: `is_staff` = `1`, `roles` = `["ROLE_ADMIN","ROLE_SUPER_ADMIN"]` |

`role_hierarchy` makes `ROLE_SUPER_ADMIN` include `ROLE_ADMIN`. Every admin page needs `ROLE_ADMIN`, so a customer account can reach only the public pages.

Gotchas:

- **Ask with `isGranted()`, never `getRoles()`.** The hierarchy applies to the logged-in token, not to the stored roles. Handlers inject `Security` and call `$this->security->isGranted('ROLE_SUPER_ADMIN')`; templates use `is_granted()`.
- **Store both roles on a super admin**, as `setStaff()` would. `User::isAdmin()` reads stored roles, so it checks for either role itself. The playground reset (`scripts/playground-redact-staff.sql`) sets both on `admin@supplymars.com`.
- Admin accounts (either role) and customers with orders cannot be deleted: `User::isDeletable()`.
- Granting staff access sends the user an email (`UpdateCustomerHandler` → `MailerHelper::sendAdminAccessGrantedMessage()`, template `templates/customer/admin-access-granted.html.twig`).
- There is no 2FA, and no log of who did what beyond the status and stock change logs (Audit context).

### What only a super admin can do

The demo user on the playground has `ROLE_ADMIN`; the owner has `ROLE_SUPER_ADMIN`. These are the differences:

| Action | Where it is checked | What `ROLE_ADMIN` gets |
|--------|---------------------|------------------------|
| Delete a product, supplier, supplier product, VAT rate or pool | The handler (`DeleteProductHandler`, `DeleteSupplierHandler`, `DeleteSupplierProductHandler`, `DeleteVatRateHandler`, `DeletePoolHandler`) | Error flash "Deleting is disabled for this user." |
| Edit a staff account | `UpdateCustomerHandler` | Error flash "Staff accounts cannot be modified in the playground." |
| Create an order by hand | `OrderController::new`, `#[IsGranted('ROLE_SUPER_ADMIN')]` | `403`; the button is hidden |

- The other delete handlers (category, subcategory, manufacturer, product image, review, customer, message) do not check, so any staff user can delete those records.
- The check is in the handler, not the flow, because `ConfirmFlow` also runs confirmed actions every staff user may do (cancel an order, remove a supplier product mapping, rewind a purchase order).
- File uploads are refused on the playground whatever the role: `UploadHelper` throws `CannotWriteFileException` when `PLAYGROUND_MODE` is true.
- The nightly reset and the demo account are in [infrastructure/playground.md](infrastructure/playground.md).

## Registration, verification and password reset

- **Sign-up** (`/register`, `RegistrationController`) creates a `ROLE_USER` account and sends a signed verification link. The form has a Turnstile challenge and the `registration` limiter ([Rate limiting](#rate-limiting)).
- **Verification is not enforced.** Following the link sets `isVerified` and logs the user in. Nothing else reads `isVerified()` (there is no user checker), so an unverified user can log in too. `/verify/resend` exists, but no page links to it.
- **Password reset** (`/reset-password`, `ResetPasswordController`, `symfonycasts/reset-password-bundle` with its defaults):
  - The token is moved from the URL into the session on the first visit, so it does not stay in the browser history or reach another site in a `Referer`.
  - The "check your email" page looks the same whether or not the address belongs to a user (a fake token when it doesn't), so the form cannot be used to find accounts.
  - The request form is rate-limited with sign-up ([Rate limiting](#rate-limiting)).

## Sessions and login

- Sessions are in Redis (`framework.session.handler_id` on `REDIS_URL`) in dev and prod; tests use `session.storage.factory.mock_file` (`config/packages/framework.yaml`). `symfony serve` needs Redis running to log in; `make up-dev-tools` starts it. Redis refuses connections without `REDIS_PASSWORD` (`compose.yaml` passes it to `requirepass`), so `REDIS_URL` must carry it.
- Every login is remembered for 7 days (`remember_me` with `always_remember_me: true` in `security.yaml`); there is no "remember me" box.
- Failed logins are throttled per IP and username (`login_throttling`), on top of the request limit on `/login`: see [Rate limiting](#rate-limiting).
- Passwords use the `auto` hasher; tests lower its cost (`security.yaml` `when@test`) for speed.

## Database

What production does (`compose.prod.yaml`):

- The app connects as the `app` user (`MYSQL_USER`, `MYSQL_PASSWORD`). Root's password (`MYSQL_ROOT_PASSWORD`) is for admin work only; the app never uses it.
- MySQL is published on the server's `127.0.0.1` only. From outside it is reachable only through an SSH tunnel (the `phpmyadmin-prod-live` and `phpmyadmin-prod-playground` services in development use one, [02-setup-local.md](02-setup-local.md#phpmyadmin-for-the-production-databases)).

In development the app connects as root with a default password, and MySQL, Redis, RabbitMQ, phpMyAdmin and Mailpit are all published on `127.0.0.1` only (`compose.override.yaml`).

## Routes and access_control

`access_control` in `config/packages/security.yaml` applies **only the first matching rule**, so a public path must come before the catch-all `^/`. Read the file for the exact list; its shape is:

| Path | Access |
|------|--------|
| `/api/doc*`, `/api/v1/catalog*` | Public |
| other `/api/*` | `ROLE_ADMIN`, bearer token ([patterns/API/README.md](patterns/API/README.md#authentication)) |
| Login, logout, sign-up, verification, password reset, playground login, the home page, the about/contact/privacy/terms pages, `/help*`, `/health`, `/health/backup` | Public |
| everything else | `ROLE_ADMIN` |

- Every controller apart from the public ones (login, sign-up, reset, playground login, home pages, help, health and backup health, the catalog API) also carries `#[IsGranted('ROLE_ADMIN')]` on the class, so its routes stay protected if a public rule is widened by mistake.
- `/health` is public so an uptime monitor and the Docker healthcheck can call it. It reports only `ok` or `fail` per check, never the reason. Its limit is in [Rate limiting](#rate-limiting); what that means for the uptime monitor is in [13-monitoring.md](13-monitoring.md#gotchas).
- A new public page needs its own `PUBLIC_ACCESS` line above `^/`, or it asks for a login.

## Security headers

The app sends the security headers, through `nelmio/security-bundle` (`config/packages/nelmio_security.yaml`), so `symfony serve`, the tests and production all use the same policy. A change that breaks the policy shows up as a violation in the browser console in development, not only after a deploy.

| Header | Value | Sent by |
|--------|-------|---------|
| `Content-Security-Policy` | see below | the app |
| `X-Frame-Options` | `SAMEORIGIN` | the app |
| `X-Content-Type-Options` | `nosniff` | the app |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | the app |
| `Strict-Transport-Security` | `max-age=63072000; includeSubDomains` | FrankenPHP (`docker/frankenphp/Caddyfile`) |

- HSTS stays in the container's Caddyfile, and the host Caddy passes it on. In the bundle it comes with `forced_ssl`, which also redirects to HTTPS, and Caddy already does that.
- There is no `preload`: submitting the domain to the browsers' preload list is slow to undo. `includeSubDomains` means every subdomain of `supplymars.com` must serve HTTPS.
- The app adds its headers only to responses that go through Symfony, including its 429s. What FrankenPHP answers itself carries only HSTS: static files (`public/assets/`, `public/bundles/`, and in development `public/uploads/` and `public/media/`) and its own refusals (403 for dotfiles and config files, 404 for a `.php` file other than `index.php`, 405 for `TRACE`). A missing file goes to Symfony, which answers 404 (or, under `/media/cache/resolve/`, generates the thumbnail). Production images are in S3 and never served by the container.
- Redirects carry no CSP, `X-Frame-Options` or `nosniff`: the bundle leaves them out, as they have no body.
- TLS is terminated by Caddy on the host; the `php` container serves plain HTTP on `127.0.0.1` only. Certificates: [11-runbook.md](11-runbook.md). Locally, `symfony serve` or `make up` provide HTTPS ([02-setup-local.md](02-setup-local.md)).

### Content Security Policy

Edit `csp.enforce` in `config/packages/nelmio_security.yaml`; it applies in every environment. Why each relaxation is there:

| Source | Needed for |
|--------|-----------|
| `script-src 'unsafe-inline'` | The dark-mode script in `templates/base.html.twig`, which must run before the page is painted, and the inline `onload` of the kit's `Avatar:Image` |
| `script-src data:` | Asset Mapper's CSS loaders (`data:application/javascript,...`), for example Dropzone's CSS |
| `style-src 'unsafe-inline'` | `style=` attributes, Sonner and Turbo's progress bar |
| `https://challenges.cloudflare.com` in `script-src`, `connect-src`, `frame-src` | The Turnstile widget: `turnstile_controller.js` loads its script from there, and the widget runs in an iframe that calls back to it |
| The S3 bucket host in `img-src` | Production stores product images and their thumbnails in S3. If the bucket or region changes, change the host here. Development stores them in `public/`, so the host does nothing there |

Gotchas:

- **No CDN.** `/api/doc` inlines Swagger UI's files (`html_config.assets_mode: offline` in `config/packages/nelmio_api_doc.yaml`) rather than loading them from jsDelivr, and `templates/bundles/NelmioApiDocBundle/SwaggerUi/index.html.twig` drops the bundle's logo, which comes from the CDN in every mode.
- `font-src` does not appear in the header: the bundle leaves out a directive whose value equals `default-src`. Fonts still fall back to `default-src 'self'`.
- `compat_headers: false` stops the bundle sending the old `X-Content-Security-Policy` header as well.
- **No nonces yet.** Once a policy holds a nonce, browsers ignore `'unsafe-inline'`, and the dark-mode script and `Avatar:Image` would be blocked. The bundle adds a nonce only when a template calls `csp_nonce()`, so don't call it until both are changed. The debug toolbar adds none of its own while `'unsafe-inline'` is in the policy, so dev, test and production send the same `script-src`.
- **The Symfony exception page in debug has no CSP.** Symfony removes the header from its own error page when debug is on (`ErrorListener::removeCspHeader`). Production error pages keep it. `tests/Shared/UI/Http/SecurityHeadersTest.php` checks a normal page and a 404 rendered without debug.

## Trusted proxies

Every production request reaches PHP from the host Caddy, so `REMOTE_ADDR` is the proxy's address and PHP sees no HTTPS flag. `config/packages/framework.yaml` trusts the proxy's `X-Forwarded-For` and `X-Forwarded-Proto` from `TRUSTED_PROXIES` (`private_ranges` in `.env`).

- `$request->getClientIp()` is then the visitor's address. `login_throttling`, the `registration` limiter and the rate limits count by it; without this, every visitor would be one IP, and one attacker could lock everyone out.
- Generated URLs and redirects use `https` when `X-Forwarded-Proto` is `https`.
- A client cannot claim another address: the host Caddy replaces any `X-Forwarded-For` a visitor sends, and the container is reachable only from the host. FrankenPHP drops header names with underscores, so `X_Forwarded_For` cannot stand in either. Only those two headers are trusted; `Host` comes from the request as the host Caddy passes it on.
- `private_ranges` is Symfony's list of private and reserved networks, which covers Docker's network. It also includes the documentation ranges (`192.0.2.0/24`, `198.51.100.0/24`, `203.0.113.0/24`), so tests use ordinary public addresses for visitors (`tests/Shared/UI/Http/TrustedProxyTest.php`).

## Rate limiting

`App\Shared\UI\Http\EventListener\RateLimitListener` checks each request against the rules in `config/packages/rate_limiter.yaml` (`app.rate_limits`) before routing and the firewall, so a refused request does no other work. The app does this itself, so development, tests and production behave the same ([ADR 016](adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md)).

| Limit | Path | Counts | Allowance | Counted per | Over the limit |
|-------|------|--------|-----------|-------------|----------------|
| `login` | `/login` | All requests | 10 a minute (sliding window) | Client IP | `429` |
| `form_post` | `/register`, `/reset-password*`, `/verify/resend` | POSTs only | 2 a minute, burst of 3 (token bucket) | Client IP, across all three | `429` |
| `health` | `/health` | All requests except from loopback | 10 a minute (sliding window) | Client IP | `429` |
| `health_backup` | `/health/backup` | All requests | 6 a minute (sliding window) | Client IP | `429` |
| `registration` | Submitted sign-up forms (`RegistrationController`) | Each submit | 5 an hour (sliding window) | Client IP | Flash message |
| `login_throttling` (`security.yaml`) | Failed logins | Failures | 5 in 15 minutes | IP and username | Login error |

The request limits catch high-volume automated traffic; `login_throttling` catches brute force against one account.

Gotchas:

- A `429` carries `Retry-After`, so a rate limit is not mistaken for an outage.
- The form rule counts only POSTs, so loading a form, a Turbo prefetch of it, or the page a submit redirects to uses none of the allowance. When every request counted, Turbo's hover prefetch and the form load used it up, so a submit within half a minute was refused. The burst of 3 lets a submit and two resubmissions after validation errors through at once.
- Every `/login` request counts: a failed login is a POST and the redirect back to the form, so about four failed attempts a minute reach `login_throttling`.
- The counts are in the `cache.rate_limiter` pool, which uses the app cache (Redis outside tests, `config/packages/cache.yaml`). When Redis is down the counts read as missing and every request goes through. Tests use an in-memory pool that keeps its counts between the requests of one test (`config/packages/cache.yaml`); `tests/Shared/UI/Http/RateLimitTest.php` checks each rule.
- A rule's `path` is a regular expression, as in `access_control`, matched against the decoded path, as the router matches it (`/log%69n` counts as `/login`).
- **Adding a limit:** define a limiter under `framework.rate_limiter`, and add a rule that names it to `app.rate_limits`.

## Input and uploads

### Validation

The entity's constraints are the source of truth. Every user-entered string column has a `Length` rule, so an over-length value is refused before it reaches the database. A `VARCHAR` rule matches the column. A `TEXT` column holds 65,535 bytes, so its rule is 10,000 characters, which fits even at 4 bytes per character (`Message.body`, `Pool.description`, `ProductReview.moderationNotes`), or a lower business limit (`ProductReview.body`, 2,000).

Create, update, reply and reject handlers validate the entity before they flush and return `Result::fail((string) $errors[0]->getMessage())` when it is invalid, so nothing is saved and the user sees the rule's own message ("Max 255 characters"), not the violation list.

Gotchas:

- **A form validates its form model, not the entity.** A rule the user can break from the form (usually `NotBlank` and `Length`) is repeated on the form model (`SupplierForm` repeats `Supplier::$name`'s), so the error appears next to the field. Without it, the handler's check still stops the save, but the error comes back as a flash message. Repeat only those rules. An API payload does the same (`customerOrderRef` on `CreateOrderPayload`).
- **A uniqueness rule is repeated too**, as `#[UniqueEntity]` on the form model with `entityClass` naming the entity. An edit form also needs `identifierFieldNames`, compared with the entity's Doctrine identifier, not its public id, so the form model carries that identifier in a property that is not a form field (`CustomerForm::$userId`, mapped as `['userId' => 'id']`). Without it the record's own unchanged email counts as taken.
- An inline edit runs no handler, so it names the entity property it saves to and the flow checks the value against that property's rules ([patterns/InlineEdit/README.md](patterns/InlineEdit/README.md)).
- Queries go through repositories, with Doctrine's QueryBuilder or DQL and bound parameters. Never build SQL or DQL by concatenating input.

### File uploads

Product images are uploaded through a Dropzone control. Its limits run in the browser, so a hand-written request skips them. The server checks every upload again with an `Image` constraint (`CreateProductImageHandler::ALLOWED_EXTENSIONS` and `MAX_SIZE`); its rules are the ones that count, and the Dropzone values (`assets/controllers/dropzone_controller.js`) only mirror them.

- **Type:** the constraint's `extensions` option checks the filename's extension *and* that the detected content type belongs to it. An `.svg` file is rejected, and so is HTML renamed to `photo.jpg`.
- **Size:** 2 MiB per file. PHP accepts 4 MiB per request (`post_max_size` and `upload_max_filesize` in `docker/php/conf.d/10-app.ini`). To allow more, raise those as well as the constant and its messages. PHP drops a bigger request's body before the app sees it, CSRF token included, so the request is refused with a `403` and no flash. Dropzone sends up to two files per request (its `parallelUploads` default), so two files close to the limit can together exceed 4 MiB; the page then refreshes without saying why.
- **Validate first:** if one file in a request fails validation, none of that request's files are stored. Other requests in the same drop can still succeed. The response is `422` with the reason in `error`, which Dropzone shows on the file, and the error flash names the file and the reason.
- **Stored name:** `UploadHelper` takes the extension from the detected content, never from the client's filename, because the extension decides which content type the file is served with. It also passes the detected type as `mimetype`, which the S3 adapter sends as the object's `Content-Type`. This is not a check in itself: `UploadHelper` stores whatever it is given, so every caller must validate with an `extensions` allowlist first. Images stored before this rule keep their names.
- **Serving:** uploads are public files (`public/uploads/` locally, the S3 bucket in production) and are served directly. This is safe only because every allowed type is one a browser never runs as script. **Never add `svg`, `html` or `xml` to the list.** Files that need those types, or that only some users may read, must be stored outside the public bucket and sent through a controller with `Content-Disposition: attachment`.

## CSRF

Symfony forms and the login form (`enable_csrf: true`) carry tokens already. The rules below cover the rest. The REST API is stateless and uses bearer tokens instead.

- **State changes are a `POST` with a token, never a `GET` link:** a link can be followed from an email or another page, or re-run by the browser when it restores a tab.
- **Flow actions** (`ActionFlow`, `ConfirmFlow`) check a token only when told which one, and each action on each entity gets its own token id. How each flow checks it, and what it skips: [FormFlow: CSRF in flows](patterns/FormFlow/README.md#csrf-in-flows).
- **Requests sent from JavaScript** carry a token too, and the endpoint checks it with `isCsrfTokenValid()` before doing anything else, answering `403` when it fails (`ProductImageController`):
  - Image upload: the upload form renders a hidden `_token` field. Dropzone posts a form's other fields with the files, so the same field serves Dropzone and the plain form it falls back to.
  - Image reorder: the page renders the token into a Stimulus value, and the `sortable` controller sends it in the `X-CSRF-Token` header.

## Email

- In production every email gets a `Bcc` header, set in `config/packages/mailer.yaml` under `when@prod` (`framework.mailer.headers.Bcc`), so a copy of every message the app sends lands in that mailbox.
- In development every email goes to `DEV_MAIL_RECIPIENT` and is caught by Mailpit ([02-setup-local.md](02-setup-local.md)).
- The app sends only account emails: verification, password reset and admin access granted (`MailerHelper`). The simulation sends none.

## Simulated data

- There are no payments, payment gateways or real stock feeds. Orders are created without payment, and stock changes come from console commands.
- Customers created by the simulation are fabricated (Mars addresses, test domains) and flagged `isSimulated`; sign-ups through `/register` are not. Which existing users the simulation orders for: [09-cli-reference.md](09-cli-reference.md#simulation-behaviour).

## Related

- [patterns/API/README.md](patterns/API/README.md#authentication): API tokens, errors and endpoints
- [13-monitoring.md](13-monitoring.md): `/health`, Sentry, the backup check
- [11-runbook.md](11-runbook.md): TLS certificates, the host Caddy, deploys
- [infrastructure/playground.md](infrastructure/playground.md): the playground's nightly reset and demo account
- [ADR 016](adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md): why FrankenPHP and the app took over nginx's jobs
