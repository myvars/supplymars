# CLI Reference

What each `app:` command does and when it runs. Read it before running a simulation command by hand, changing one, or wondering why one did nothing. Arguments and options in full: `symfony console <command> --help`. The schedule itself is `src/Shared/Infrastructure/Scheduler/MainSchedule.php`; how the scheduler and worker run is in [06-operations.md](06-operations.md#scheduled-jobs).

## Commands

Times are UTC. Which stacks run the schedule ("live only" jobs are left out of the playground, and nothing runs in development): [06-operations.md](06-operations.md#scheduled-jobs).

| Command | Context | Scheduled | Purpose | Notable arguments / options |
|---|---|---|---|---|
| `app:create-customer-orders` | Order | `1 --random`, twice every 5 min with jitter (about 12 orders an hour) | Random orders of 1–5 lines, quantity 1–5, ref `TEST-` + zero-padded customer id | `order-count` (1), `--random` (0 to `order-count`), `--dry-run` |
| `app:build-purchase-orders` | Purchasing | `20`, every 15 min | Allocates PENDING, unlocked orders with no PO yet to suppliers, then retries unlocked orders with outstanding quantity in the slots left ([04](04-sourcing-and-pricing.md)) | `order-count` (50), `--dry-run` |
| `app:accept-purchase-orders` | Purchasing | `20`, at :01, :16, :31, :46 | Accepts or rejects PROCESSING POs of unlocked orders | `po-count` (50), `--supplier`, `--dry-run` |
| `app:ship-purchase-order-items` | Purchasing | `100`, hourly | Ships ACCEPTED items that pass the timing check | `po-item-count` (50), `--supplier`, `--skip-timing`, `--dry-run` |
| `app:deliver-purchase-order-items` | Purchasing | `100`, hourly | Delivers SHIPPED items that pass the timing check | `po-item-count` (50), `--supplier`, `--skip-timing`, `--dry-run` |
| `app:refund-purchase-orders` | Purchasing | `50`, 01:30, live only | Marks the items of REJECTED POs REFUNDED and re-runs allocation (may pick the same supplier) | `po-count` (50), `--dry-run` |
| `app:update-supplier-stock` | Purchasing | `20`, every 15 min | Moves supplier product stock and cost | `product-count` (50), `--supplier`, `--dry-run` |
| `app:calculate-product-sales` | Reporting | `1 1` at 00:03; `1` at :10, :40 | Daily product sales records | `day-count` (required), `day-offset` (0), `--skip-summary`, `--dry-run` |
| `app:calculate-product-sales-summary` | Reporting | `1` at 00:05 | Pre-computed product summaries | `rebuild` (0), `--dry-run` |
| `app:calculate-order-sales` | Reporting | `1 1` at 00:07; `1` at :12, :42 | Daily order sales records | as product sales |
| `app:calculate-order-sales-summary` | Reporting | `1` at 00:09 | Pre-computed order summaries | `rebuild` (0), `--dry-run` |
| `app:calculate-customer-sales` | Reporting | `1 1` at 00:11; `1` at :15, :45 | Daily customer sales and activity (active, new, returning) | as product sales |
| `app:calculate-customer-sales-summary` | Reporting | `1` at 00:13 | Top customers, by city, by segment | `rebuild` (0), `--dry-run` |
| `app:backup-database` | Shared | `--local-copy=/backups/latest.sql.gz`, 02:00, live only | Gzipped mysqldump to the backups filesystem (S3 in prod, `var/backups/` in dev), then deletes old backups | `--retention-days` (30), `--local-copy`, `--dry-run` |
| `app:prune-status-change-log` | Audit | 03:30, live only | Deletes status history of finished orders and POs older than the retention period | `--months` (12), `--batch-size` (5000) |
| `app:generate-reviews` | Review | no | PENDING reviews for delivered purchases with no review yet | `count` (20), `product-id`, `--dry-run` |
| `app:create-warehouse-products` | Purchasing | no | Setup: creates catalog products from the warehouse supplier's products | — |
| `app:create-supplier-products` | Purchasing | no | Setup: maps each active, unmapped dropshipper product to a product with the same manufacturer part number, else creates one | — |
| `app:activate-supplier-products` | Purchasing | no | Activates inactive supplier products (through the status toggle) | `product-count` (50) |
| `app:reset-supplier-stock` | Purchasing | no | Sets every product of one supplier to random stock 0–300 | `supplierId` (required, internal id) |
| `app:resync-product-pricing` | Pricing | no | Runs `recalculateActiveSource()` on every product, so its stored source, stock, cost and price match its supplier products (a product with no viable source gets stock 0 and keeps its last cost and price). Run once after #106's fix; safe to repeat. A product that fails is left as it was and listed | `--batch-size` (200), `--dry-run`, `-v` lists each change |
| `app:rewind-mixed-status-purchase-orders` | Purchasing | no | Finds POs whose items disagree (some rejected) and rewinds them to PENDING, deleting their status history; skips (and counts) any with a REFUNDED or CANCELLED item | `--limit` (100), `--days-back` (30), `--dry-run` |
| `app:backfill-ulids` | Shared | no | Fills a null `publicId` on every `HasPublicUlid` entity | `batch-size` (200), `--limit` |
| `app:user:issue-token` | Customer | no | New API token for a user; replaces the old one | `email` |
| `app:restore-database` | Shared | no, dev only | Restores a `.sql.gz` from `var/backups/` into the local database | `--from-s3`, `--file`, `--force` |
| `app:send-test-emails` | Customer | no, dev only | Sends the three customer emails to Mailpit with dummy data | — |

### app:user:issue-token

- The token is printed once, as the only line on stdout (messages go to stderr), also with `--quiet`, so `... --quiet | pbcopy` works. It is a 64-character hex string. Only its SHA-256 hash is stored, so it can't be shown again and a database backup holds no working token.
- Exits 1 if no user has that email. The order endpoints need `ROLE_ADMIN`, so issue tokens to staff users ([patterns/API/README.md](patterns/API/README.md)).

## Simulation behaviour

| Rule | Detail | Source |
|---|---|---|
| Acting user | Every simulation, setup and utility command in Order, Purchasing and Review logs in as `DEFAULT_STAFF_EMAIL` (`admin@supplymars.com` in `.env`). That user must exist, or the command throws | `DefaultUserAuthenticator::ensureAuthenticated()` |
| One supplier per run | accept, ship, deliver and update-stock pick **one random supplier** (any supplier, warehouse included) unless `--supplier` is given. `ship 100` ships up to 100 items of that one supplier | `resolveSupplier()` in each command |
| Rejection | Rolled once **per PO**, 1 in 50; every item of the PO gets the result | `AcceptPOsCommand::REJECTION_ODDS` |
| Shipping | 09:00–18:00, at least 2 hours after the item's ACCEPTED status change, then 95% chance | `ProcessingSimulator` |
| Delivery | 07:00–21:00, at least 12 hours after the SHIPPED status change, then 95% chance | `ProcessingSimulator` |
| `--skip-timing` | Skips the hours, the wait **and** the 95% roll: every candidate item moves | `ShipPOItemsCommand`, `DeliverPOItemsCommand` |
| Stock | Stock ≤ 20: add 0–100. Otherwise remove up to 10%. Cost moves up to ±10%. Product prices don't follow ([04](04-sourcing-and-pricing.md)) | `UpdateSupplierStockCommand` |
| Customers | 1 order in 3 goes to an existing verified non-staff user; otherwise a new random user and address are created | `DemoOrderCreator` |
| Demo order button | Orders > Create Demo Order uses the same `DemoOrderCreator`, with ref prefix `DEMO-`, 10 a day across all users | `CreateDemoOrderHandler::DAILY_LIMIT` |
| Review ratings | 5★ 35%, 4★ 35%, 3★ 15%, 2★ 10%, 1★ 5% | `ReviewGenerator` |

### Reporting chain

Each `calculate-*-sales` command rewrites the daily records for its range. When `day-offset` is 0 and neither `--skip-summary` nor `--dry-run` is given, it then runs its summary command **incrementally** (`rebuild` 0: today and the current month only). With `rebuild` 1 the summary also recomputes the last 30 days and 12 months of chart series.

The schedule uses both: `X 1` every 30 minutes (today, plus the incremental summary), and at night `X 1 1` (yesterday, no summary) followed by `X-summary 1` (rebuild). To rebuild history by hand, run `X <days>` and then `X-summary 1`.

## Exit codes and locking

| Code | When |
|---|---|
| 0 | All items processed, or nothing to do |
| 1 | One or more items failed, or a backup found the lock held |
| 2 | Invalid argument (for example a count below 1) |

- **Continue on failure.** The 11 batch commands (create orders, build, accept, ship, deliver, refund, update-stock, generate-reviews, the three `calculate-*-sales`) catch each item's exception, log it with the entity id, carry on, and finish with `N item(s) failed — see logs for details.` and exit 1.
- **A scheduled run that exits non-zero** reaches Sentry ([13-monitoring.md](13-monitoring.md#error-tracking-sentry)).
- **Locks** (`symfony/lock`, `LockableTrait`), and they differ on purpose:
  - `app:backup-database` held → warning, exit **1**, so a skipped backup is reported. Where to run a manual backup: [11-runbook.md](11-runbook.md#42-manual-backup).
  - `app:prune-status-change-log` held → note, exit **0**.

## Dev-only commands

`app:restore-database` and `app:send-test-emails` are registered only in `dev` (`#[When('dev')]`), so they don't exist in the production image.

- `app:restore-database` overwrites the database in `DATABASE_URL` after a confirmation (`--force` skips it). Without `--file` it restores the newest file in `var/backups/`. `--from-s3` first downloads the latest backup from `AWS_S3_BUCKET`, asking for the production S3 keys at the prompt. Backup and restore in production: [11-runbook.md](11-runbook.md).
- `app:send-test-emails` sends Verify Email, Reset Password and Admin Access Granted. Templates are under `templates/customer/`.

## Setup order

The fixtures already give each supplier product a catalog product, so after `doctrine:fixtures:load` go straight to the simulation commands ([06-operations.md](06-operations.md#simulation-bootstrap) has the sequence). `app:create-warehouse-products` and `app:create-supplier-products` are for supplier data loaded without the fixtures: run them in that order, warehouse first, so dropshipper products can map to the warehouse's products by part number.

## Gotchas

- **Running the chain straight through moves little.** Items accepted a moment ago won't ship (2-hour wait), and outside 09:00–18:00 UTC nothing ships at all. Use `--skip-timing` on ship and deliver when seeding data.
- **One supplier per run** means `accept-purchase-orders 100` leaves the other suppliers' POs waiting. Repeat the command, or pass `--supplier` for each.
- **Ship and deliver need the item's ACCEPTED / SHIPPED `StatusChangeLog` row.** An item that reached that status without one (data loaded without the audit event, or history deleted) never moves without `--skip-timing`. A rewind deletes the rows but also returns the items to PENDING, so the next acceptance writes a new row.
- **Locked orders are skipped** by build, accept, ship and deliver, so a lock left on an order stalls it.
- **The auto-run summary is incremental.** After recomputing many days, run the summary with `rebuild` 1, or the 30-day and 12-month charts keep the old figures.
- The simulations write real rows. Don't run them against a copy of production you care about.

## Related

- [ADR 003](adr/003-simulation-first-design.md): why the platform simulates
- [06-operations.md](06-operations.md): the scheduler, the worker, the bootstrap sequence
- [04-sourcing-and-pricing.md](04-sourcing-and-pricing.md): what allocation, refunds and stock changes do to orders and prices
- [11-runbook.md](11-runbook.md): backups, restore, rebuilding reports in production
- [UserManual/14-simulations.md](UserManual/14-simulations.md): what users see happen on its own
