# ADR 016: FrankenPHP in Place of nginx + PHP-FPM

## Status

Accepted. Classic mode first (#80), then worker mode in production (#82), as planned in #68.

## Context

Each production stack ran an `nginx` container and a `php` container on PHP-FPM, from two images, behind a host-level Caddy that terminates TLS. nginx served static files, passed PHP to FPM over FastCGI, and did several of the application's jobs:

- rate limits on `/login`, on POSTs to `/register`, `/reset-password*` and `/verify/resend`, on `/health` (a shared cap of two concurrent outside calls) and on `/health/backup`;
- the visitor's IP (`set_real_ip_from` and `X-Forwarded-For`), and `HTTPS=on` from `X-Forwarded-Proto`;
- the 4 MB body limit (`client_max_body_size`);
- HSTS. The other security headers had already moved into the app (#70).

Local HTTPS in Docker needed mkcert certificates in `docker/nginx/certs/`.

The skeleton made this change first (myvars/skeleton#83, its ADR 015) and found, against this app as well as its own:

- The PHP 8.5 preload crash also happens under FrankenPHP. The server starts and reports healthy, then every request fails.
- Caddy has no built-in rate limiting. The `caddy-ratelimit` module needs a custom build.
- Behind the host Caddy, PHP sees the proxy's IP and no HTTPS flag unless Symfony trusts the proxy.
- Caddy's `request_body max_size` did not refuse an oversized POST.
- Classic mode performs about the same as FPM; worker mode is faster.
- In worker mode (myvars/skeleton#84), `FRANKENPHP_RESET_KERNEL` kept less than half of the speed-up, and under load FrankenPHP crashed twice with `zend_mm_heap corrupted`.

## Decision

- **One image** on the official FrankenPHP image, pinned (`dunglas/frankenphp:1.13.1-php8.5.11`), with dev and prod stages. The `php` container runs FrankenPHP (the image's default command) and `messenger` runs `messenger:consume`. The nginx stages, `docker/nginx/`, the `nginx` service, `make certs` and mkcert are removed. The `reset` image is unchanged.
- **One Caddyfile** (`docker/frankenphp/Caddyfile`), the skeleton's. `SERVER_NAME` is `localhost` in development (HTTPS from Caddy's local certificate authority) and `:80` in production, published on `127.0.0.1:${HTTP_HOST_PORT}` (live 8001, playground 8002), so the host Caddy config does not change. It keeps what nginx did as a web server:
  - HSTS (`max-age=63072000; includeSubDomains`, no `preload`, as decided in #68), and no `Server` header;
  - zstd/gzip, and a year's immutable cache on `/assets/` files that exist;
  - 403 for dotfiles (except `.well-known`), `.env` and `composer.*`; 405 for `TRACE`;
  - only `public/index.php` runs. A path that is not a file is rewritten to it, which also covers LiipImagine's `/media/cache/resolve/`; any other `.php` file is hidden and returns 404. `php_server` would run any `.php` file under `public/`.
- **Application rules move into the app**, so development, tests and production behave the same:
  - rate limits through Symfony's RateLimiter: limiters and one rule list (`app.rate_limits`) in `config/packages/rate_limiter.yaml`, applied by `RateLimitListener` before routing and the firewall, answering 429 with `Retry-After`. The `registration` limiter, which `RegistrationController` consumes and answers with a flash message, moves into the same file and is otherwise unchanged;
  - `framework.trusted_proxies` (`TRUSTED_PROXIES`, `private_ranges` by default) and `trusted_headers` (`x-forwarded-for`, `x-forwarded-proto`);
  - the body limit through `post_max_size` and `upload_max_filesize` (4M).
- **PHP runs as `www-data` in production**, as the FPM workers did, and cannot write to its own configuration. This includes the Messenger worker, which ran as root: the host's backups directory, where the nightly backup leaves `latest.sql.gz` for the playground reset, is owned by uid 33.
- **`NGINX_HOST_PORT` is renamed `HTTP_HOST_PORT`.** Both stacks share the server, so each env file must set it, or both fall back to 8001.
- **The preload workaround stays** in the Dockerfile and the entrypoint, with its comment.
- **Worker mode in production, classic mode in development** (#82). The Caddyfile's global `frankenphp` block takes `FRANKENPHP_CONFIG`, which only `compose.prod.yaml` sets (`worker /app/public/index.php`). The `php` directive still runs only `index.php`; the worker serves it. `symfony/runtime` switches to its FrankenPHP runner by itself, so the app code does not change. The `messenger` service is unaffected.
  - **Each worker restarts after 500 requests** (`FRANKENPHP_LOOP_MAX`, Symfony's default, set explicitly), a safety net against slow leaks. With no restarts at all, memory was already flat (below), so 500 costs little and is not tuned further.
  - **The rule:** a service keeps no data from one request in a property; one that caches on purpose implements `ResetInterface` (`Docs/03-architecture.md#state-between-requests`). `StateBetweenRequestsTest` keeps one kernel across requests and interleaves two staff users and a visitor: the header user, the "My Queue" count and the author of a status change. It fails if `CurrentUserProvider` keeps the user in a property. The audit of `src/` for #68 found one such service, `PublicIdResolverRegistry`, fixed in #67; the audit again for #82 found none.
  - **`FRANKENPHP_RESET_KERNEL` stays off**, as in the skeleton: slower, and it crashed under load. The rule, the test and the restart after 500 requests guard against leaks instead.
  - **Development stays in classic mode**, so a code change takes effect on the next request, as before. `symfony serve`, the preferred dev server, has no worker mode, and FrankenPHP's `watch` was not tested on a Docker Desktop bind mount. `make up-prod-local` runs worker mode locally.
  - `make check-prod-config` validates the Caddyfile with the worker line from `compose.prod.yaml` and the app's `public/`, so a wrong worker path fails CI, not the deploy.
- **The FrankenPHP hot-reload block in `base.html.twig` is removed.** It needs Mercure and loads scripts from jsdelivr, which the CSP blocks. `symfony serve` stays the preferred dev server.

## Consequences

- Each stack runs two app containers (php, messenger) from one image, and CI builds and pushes `php` and `reset`.
- Rate limiting runs after PHP starts instead of in nginx. At this traffic that is acceptable, and it is tested. nginx's cap on concurrent `/health` calls becomes a rate per IP, which does not bound how many slow calls run at once; a check only hangs when a dependency the whole site needs does. The counts are in Redis; if Redis is down, requests are let through.
- A POST over 4 MiB reaches the app with no body, CSRF token included, so the product image upload answers 403 where nginx answered 413. Neither sets a flash, so in both cases the page refreshes without saying why. Dropzone stops a single file over 2 MB in the browser; only two files near the limit, sent together, get there.
- `make up` needs the browser to trust Caddy's local certificate authority once per machine (`Docs/02-setup-local.md`), in place of mkcert.
- FrankenPHP starts two PHP threads per CPU by default, in each stack.
- Only `/assets/` files get a year's cache. nginx also cached other static files under `public/` (the favicon, images) by extension; they are now revalidated, which costs little.
- `TRUSTED_PROXIES=private_ranges` trusts anything on Docker's network and loopback. That is safe because the host Caddy replaces any `X-Forwarded-For` a visitor sends; if it is ever told to trust upstream proxies, a visitor could claim `127.0.0.1` and skip the `/health` limit.
- `www-data` binds port 80 because Docker sets `net.ipv4.ip_unprivileged_port_start=0` in each container, not because of the `cap_net_bind_service` file capability on the image's binary. It works the same with `--security-opt no-new-privileges`, which would block a file capability.
- The rate-limit rules match the decoded path, as the router does, so an encoded path (`/log%69n`) counts as the route it reaches.
- Worker mode (#82), `make up-prod-local`, Docker Desktop with 10 CPUs, `ab`, three runs each. `/order/` is logged in, against a copy of production's data, and its time is mostly queries; `/` is anonymous:

  | | Classic | Worker |
  |---|---|---|
  | `/order/`, one at a time | 129.3–129.5 ms | 122.8–123.6 ms |
  | `/order/`, 20 concurrent | 59.1–59.3 req/s | 61.8–61.9 req/s |
  | `/`, one at a time | 8.0–8.1 ms | 4.6–5.3 ms |
  | `/`, 20 concurrent | 566–705 req/s | 951–975 req/s |

  Each request saves the 3–6 ms it took to start the app. Memory of the `php` container over 25,000 requests (half `/order/`, half `/`, 20 at a time): it rises from about 150 MiB to about 200 MiB as the 20 workers warm up, then stays at 195–203 MiB with restarts every 500 requests, and 202–204 MiB with none.
- MySQL, not FrankenPHP, held most of the server's memory and swap after the worker-mode deploy. Its production settings and their before and after numbers are in `Docs/06-operations.md#database-optimization` (#86).
- In worker mode a service that keeps state between requests is a bug that only shows in production, `make up-prod-local` and tests that keep one kernel. The rule and `StateBetweenRequestsTest` are the guard.
- A deploy recreates the `php` container and so restarts the workers. They can also be restarted by hand through FrankenPHP's admin API, inside the container (`Docs/11-runbook.md`, section 2.4).

## Alternatives Considered

- **Keep nginx + PHP-FPM.** It works. Rejected for the single image, local HTTPS without mkcert, and worker mode next.
- **Rate limits in Caddy with `caddy-ratelimit`.** Rejected: a custom build in CI and a third-party module to keep updated.
- **`php_server`**, FrankenPHP's one-line default. Rejected: it runs any `.php` file under `public/`.
- **Caddy's `request_body max_size`** for the body limit. Rejected: it did not refuse an oversized request.
- **`FRANKENPHP_RESET_KERNEL`** in worker mode. Rejected: slower, and it crashed under load in the skeleton.
- **Worker mode in development with `watch`.** Rejected for now: `symfony serve` cannot use it, and file watching through a Docker Desktop bind mount was not tested. Classic mode in development means a leak is caught by the test, not by using the site locally.
- **`php_server` with a `worker` block**, the form FrankenPHP's docs show. Rejected for the same reason as `php_server` above; the global `worker` serves `index.php` through the existing `php` directive.
- **The Messenger worker as root**, to keep writing the backups directory without a server step. Rejected: the worker runs every scheduled command, and a one-time `chown` on the server is enough.
