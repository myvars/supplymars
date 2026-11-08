---
title: FrankenPHP in worker mode
period: 20 – 24 October 2026
summary: One image in place of nginx and PHP-FPM, then a kernel that stays loaded between requests. Faster pages, flat memory, a rule that keeps it safe, and an incident traced to its root.
figures:
  - value: 8 → ~5 ms
    label: home page, one request at a time
  - value: ~1.7×
    label: home page throughput, 20 concurrent
  - value: ~200 MiB
    label: flat over 25,000 requests
  - value: 2 → 1
    label: web-tier images
refs:
  - label: ADR 016 · FrankenPHP
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md
  - label: "#81 · FrankenPHP, classic mode"
    url: https://github.com/myvars/supplymars/pull/81
  - label: "#85 · Worker mode"
    url: https://github.com/myvars/supplymars/pull/85
  - label: "#123 · Preload off"
    url: https://github.com/myvars/supplymars/pull/123
  - label: "#67 · State between requests"
    url: https://github.com/myvars/supplymars/issues/67
---

SupplyMars ran the way most PHP apps run: nginx in front, PHP-FPM behind, two images and a FastCGI hop between them. Every request booted the Symfony kernel from scratch. It worked, but it was two things to configure, build and secure where one would do, and every request paid for a full boot.

FrankenPHP is a PHP server built on Caddy. It can run PHP the classic way, one boot per request, or in worker mode, where the app boots once and serves request after request. I did it in two steps, so each could be measured on its own.

## Step one: one image, classic mode

The first change ([#81](https://github.com/myvars/supplymars/pull/81)) replaced nginx and PHP-FPM with a single FrankenPHP image, still booting per request.

The real work was moving what nginx used to do into places that every environment shares:

- **Rate limits** moved into the app with Symfony's RateLimiter: ten requests a minute to the sign-in page, a shared limit on registration and password reset, limits on the health checks. The rules match the decoded path, the way the router does, so an encoded URL can't slip past.
- **Real client IPs** come from Symfony's `trusted_proxies`, trusting only the forwarded-for and protocol headers.
- **The request body limit** is PHP's own.
- **Only `public/index.php` can run.** I proved it by writing a `probe.php` into `public/`: it ran from the command line, and the server returned 404 for it.
- **Production runs as `www-data`**, and can't write to its own PHP config.

With nginx gone, development, tests and production enforce the same rules in the same code. The rate limits have tests now, which nginx rules never had.

In classic mode FrankenPHP was about a millisecond slower per request than PHP-FPM, and handled more at twenty concurrent requests. Classic mode was never the destination; worker mode was the reason for the move.

## Step two: worker mode

Worker mode is mostly one line of configuration ([#85](https://github.com/myvars/supplymars/pull/85)). Production sets `FRANKENPHP_CONFIG: "worker /app/public/index.php"`; development leaves it empty and stays in classic mode, so a code change is picked up without a restart. Each worker restarts after 500 requests as a safety net against slow leaks.

The configuration is the easy part. Worker mode changes a rule that most PHP code never has to think about: **services live across requests**. A service that keeps the current user in a property will hand it to the next visitor.

So before switching I audited `src/` for state held in services. It found one real case, `PublicIdResolverRegistry`, which cached lookups, misses included, for the life of the process ([#67](https://github.com/myvars/supplymars/issues/67)). It was harmless under PHP-FPM and would have been a bug in a worker. It now implements `ResetInterface`, so Symfony clears it between requests and between queued messages.

Then I made the rule explicit, in the project's instructions and its architecture docs: no service keeps a request's data in a property, and a service that caches on purpose resets itself. `StateBetweenRequestsTest` guards it. It keeps one kernel alive while two staff users sign in through the form and take turns with an anonymous visitor, and checks each sees their own header, their own badge counts and their own name on the audit log. I broke the rule on purpose, by keeping the user in a property, and the test failed at exactly the right step.

## The numbers

The production image locally, against a copy of production's data, three runs each with Apache Bench:

| Request | Classic | Worker |
|---|---|---|
| Home page, one at a time | 8.0–8.1 ms | 4.6–5.3 ms |
| Home page, 20 concurrent | 566–705 req/s | 951–975 req/s |
| Order list, one at a time | 129.3–129.5 ms | 122.8–123.6 ms |
| Order list, 20 concurrent | 59.1–59.3 req/s | 61.8–61.9 req/s |

The home page served 1.5–1.7× as many requests. The order list improved by the same few milliseconds of boot time, which is small next to its queries: its time is in the database, so that is where its next gains are.

Memory was the other question on a 2 GB server. Over 25,000 mixed requests the container rose from about 150 MiB to 200 MiB as the twenty workers warmed up, then stayed flat, with or without the restarts. Every response was a 2xx.

## When a worker restarted

Two days later, the Mercure deploy took both sites to a 500 on every page. `/health` stayed green, because it doesn't render Twig. The log had one error: Mercure's hub factory had been given an object that didn't implement the interface it was declared with.

The cause took some tracing ([#123](https://github.com/myvars/supplymars/pull/123)). The Mercure bundle wraps a factory in a closure service, which Symfony's compiled container dumps as an anonymous class implementing an interface. Production preloaded the compiled container with OPcache. After a worker's first restart, PHP reused the preloaded anonymous class *without its interface*, and every page that built the hub failed until the container restarted.

I reproduced it with the production image and the restart interval set to 50 requests:

| Run | Result |
|---|---|
| Preload on, as deployed | requests 1–49 succeed; every request from the 50th fails |
| Preload off | all 200 requests succeed, across four worker restarts |
| No restarts, 32 in parallel | no failures |

The fix was to turn preload off. I had set it up under PHP-FPM in February, where it saved a boot's worth of class loading on every request. In worker mode the app already stays loaded, so preload was no longer earning its place, and Symfony's own error controller compiles the same kind of class, so a Mercure-only workaround would have left the fault in place. The tests hadn't caught it because they never run with preload and never restart a worker; a check that serves more requests than the restart interval is on the follow-up list.

## What it leaves behind

One web image instead of two. Rules that nginx used to enforce now in tested application code. A home page 1.5–1.7× faster, flat memory, and a written rule with a test that fails when anyone breaks it. `make check-prod-config` validates the production Caddyfile with the real worker line in CI, so a wrong path fails the build, not the deploy.
