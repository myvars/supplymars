# Operations

Read when you add or change a scheduled job, touch the Messenger worker, fill a database with simulated data, or change the production MySQL settings. Deploys, backups, restores and the environment variables are in [11-runbook.md](11-runbook.md); what each command does is in [09-cli-reference.md](09-cli-reference.md).

## Scheduled Jobs

Symfony Scheduler runs the jobs inside the Messenger worker, not cron ([ADR 015](adr/015-symfony-scheduler-in-place-of-cron.md)). Every job is in `src/Shared/Infrastructure/Scheduler/MainSchedule.php`, as a console command sent through `RunCommandMessage`, so each one can also be run by hand. `debug:scheduler` lists them with their next run times.

The schedule (what runs when, and which jobs are live only) is in the commands table of [09-cli-reference.md](09-cli-reference.md#commands).

How it runs:

- **Production only.** Only the production `messenger` container consumes `scheduler_default` (`compose.prod.yaml`). Development and `make up-prod-local` consume only `async`, so no job runs on a local machine.
- **`PLAYGROUND_MODE` leaves out the live-only jobs.** A playground refund at 01:30 would be replaced by the 02:15 reset from live's backup, and the playground gets live's pruned history each night anyway.
- **One job at a time**, in schedule order when several are due together. The backup is first in the list, so its copy is written before the playground reset reads it at 02:15. The reset itself is not a scheduled job: it runs on cron in its own `reset` container ([playground.md](infrastructure/playground.md)).
- **Missed runs.** The last run time is kept in the `cache.scheduler` pool, so a run missed while the worker restarts happens when it comes back, once (`processOnlyLastMissedRun`).
- **Locked.** The schedule takes the `main-schedule` lock, so two workers never run the same job twice.

### When a job fails

The batch commands carry on past a failed item and exit 1 ([09 exit codes](09-cli-reference.md#exit-codes-and-locking)). What a failed scheduled run reports, and why it is not retried: [13-monitoring.md](13-monitoring.md#error-tracking-sentry).

### Scheduled commands of your own

1. Add a `RecurringMessage::cron()` with a `RunCommandMessage` to `MainSchedule`, inside the `PLAYGROUND_MODE` check if it is for live only.
2. Add it to `tests/Shared/Infrastructure/Scheduler/MainScheduleTest.php`, which lists every job on each stack.
3. A scheduled command never overlaps its own previous run (the schedule is locked, one job at a time), but it can overlap a manual run. Where that matters, use `LockableTrait` with the `LockFactory` and return early when `lock()` fails, as `src/Shared/UI/Console/Utilities/BackupDatabaseCommand.php` does. Decide what a skipped run returns: the backup returns failure, so Sentry hears of it; `PruneStatusChangeLogCommand` returns success with a note.
4. Run manual copies of locked jobs in `messenger`, where the scheduler runs: the lock is per container ([11-runbook.md](11-runbook.md#42-manual-backup)).
5. Keep jobs short. While a job runs, async messages (emails, async events) wait. A job that takes minutes is the point to give `scheduler_default` a worker of its own ([ADR 015](adr/015-symfony-scheduler-in-place-of-cron.md)).
6. Follow the continue-on-failure contract above, so one bad row does not stop the batch.

## Messenger worker

One container, `messenger`, runs the queue and the schedule from the same image as `php`.

| | Production | Development, `make up-prod-local` |
|---|---|---|
| Command | `messenger:consume scheduler_default async --time-limit=3600 --memory-limit=192M --sleep=5` | `messenger:consume async --time-limit=3600 --sleep=5` |
| Receivers | `scheduler_default` first, then `async` | `async` only |

Under `symfony serve` there is no `messenger` container: the server starts `messenger:consume async` itself (`.symfony.local.yaml`), with no time limit and the default 1-second sleep, and restarts it when the code changes.

- **Order of receivers.** The worker goes back to the first receiver after each message, and the scheduler only returns jobs that are due, so jobs are not starved by a long queue.
- **Restarts.** After an hour, or between jobs once above 192M (under PHP's 256M), the worker stops cleanly and the container restarts it.
- **Stops.** The worker cannot catch SIGTERM (no `pcntl`), so a stop or deploy kills it after 30 s (`stop_grace_period`). A job cut short has not advanced the schedule and runs again; a message cut short is redelivered.
- **Queue.** `async` is `doctrine://default` (`MESSENGER_TRANSPORT_DSN`), the `messenger_messages` table in the application database; RabbitMQ is an opt-in ([ADR 013](adr/013-database-queue-by-default.md)). Routed to it: `SendEmailMessage` and every `AsyncDomainEventInterface` event.
- **Retries.** 3, with the delay doubling each time (`config/packages/messenger.yaml`), then the `failed` queue (`doctrine://default?queue_name=failed`, same table). Inspecting and retrying failures: [11-runbook.md](11-runbook.md).
- **Tests** use `in-memory://` for `async` ([07-testing.md](07-testing.md)).

## Simulated data

### Simulation Bootstrap

To fill an empty development database, in this order. What each command does, its options and its timing rules are in [09-cli-reference.md](09-cli-reference.md).

```bash
# 1. Purge, then the staff user and base data (what it creates: 02-setup-local.md#a-user-and-data)
symfony console doctrine:fixtures:load --no-interaction

# 2. Orders and their purchase orders
symfony console app:create-customer-orders 100
symfony console app:build-purchase-orders 200

# 3. Move the purchase orders on. Without --supplier each run picks one random supplier,
#    so run these once per supplier. --skip-timing: just-accepted items are not 2 hours old
symfony console app:accept-purchase-orders 100 --supplier=<id>
symfony console app:ship-purchase-order-items 200 --supplier=<id> --skip-timing
symfony console app:deliver-purchase-order-items 200 --supplier=<id> --skip-timing

# 4. Reviews (of delivered purchases) and report data; each sales command also runs its summary
symfony console app:generate-reviews 100
symfony console app:calculate-product-sales 30
symfony console app:calculate-order-sales 30
symfony console app:calculate-customer-sales 30
```

The commands fail until the `DEFAULT_STAFF_EMAIL` user exists ([09](09-cli-reference.md#simulation-behaviour)), which the fixtures create. A copy of production is the other way to get data: `app:restore-database` ([11-runbook.md](11-runbook.md)).

## MySQL sizing

### Database Optimization

`docker/mysql/production.cnf` is mounted into `database` by `compose.prod.yaml` only, so `make up-prod-local` uses it and `make up` keeps the image's defaults. One 2 GB server runs both stacks, each with its own MySQL, and the defaults are sized for a bigger machine. On 2026-10-21 the two `mysqld` processes held 1.4 GB of the server's 2 GB of swap (#86), and on 2026-10-26 their binary logs held 24 GB of its 58 GB disk (#143).

MySQL reads the file only when it starts, and a deploy does not recreate `database` for a change to it: after one, run `docker compose $LIVE restart database` and the same for `$PLAYGROUND` ([11-runbook.md](11-runbook.md#compose-command-prefix)), a few seconds of 503s each.

| Setting | Value | Why |
|---------|-------|-----|
| `performance_schema` | `OFF` | The largest saving. The app never reads it; phpMyAdmin's status pages and most `sys` views lose their data |
| `max_connections` | 50 (default 151) | FrankenPHP threads, the worker, the playground reset and the odd console or phpMyAdmin session |
| `innodb_log_buffer_size` | 16M (default 64M) | Allocated up front; the app's transactions are small |
| `mysqlx` | `OFF` | Nothing uses the X Protocol |
| `disable_log_bin` | binary logging off (default on, kept 30 days) | Nothing reads the logs: no replica, and the backup is `mysqldump`. The playground's nightly restore logged 1–2 GB a night |
| `innodb_buffer_pool_size` | default 128M | Live's data (about 1.8 GB, mostly `status_change_log`) would not fit on this host anyway, and the pages the app reads do: 99.98% of reads hit the pool |

Measured locally on `mysql:8.4`, the same instance with and without the file, before `disable_log_bin` was added:

| | Defaults | `production.cnf` |
|---|---|---|
| Memory, empty and idle | 453 MiB | 144 MiB |
| Memory after restoring a copy of live | 616 MiB | 356 MiB |
| Restore of that copy, as the playground reset does it | 123 s | 122 s |
| Two report-style aggregates over `status_change_log` and orders | 4.9–5.1 s | 4.9–5.0 s |

Check the values in use with `SHOW VARIABLES` and memory with the commands in [11-runbook.md](11-runbook.md).

## Gotchas

- **Stock oversell.** Allocation checks that a supplier has the stock (`OrderAllocator`) but never lowers it, so any number of orders can be allocated against the same units until `app:update-supplier-stock` or an edit changes the figure.
- **Order lock** doesn't stop editing, but the simulation jobs skip locked orders, so a forgotten lock stalls an order ([05 Orders](05-features.md#orders)). Any staff user can release it; the holder then gets a toast ([ADR 017](adr/017-live-updates-with-mercure.md)).
- **The simulation grows the database.** Orders arrive around the clock on both stacks; `status_change_log` is most of live's data, which is why `app:prune-status-change-log` runs nightly.
- **One worker does everything.** While it is down or busy with a long job, emails and async events wait in the queue (they are not lost), and scheduled runs are made up once when it returns.

## Related

- [ADR 003](adr/003-simulation-first-design.md): why the simulation drives the lifecycle
- [ADR 005](adr/005-reporting-strategy.md): the two reporting layers the sales commands fill
- [ADR 015](adr/015-symfony-scheduler-in-place-of-cron.md): Scheduler in place of cron
- [09-cli-reference.md](09-cli-reference.md): every command
- [11-runbook.md](11-runbook.md): deploy, backup, restore, the worker's failed messages
- [13-monitoring.md](13-monitoring.md): Sentry and `/health`
