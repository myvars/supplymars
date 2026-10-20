# ADR 015: Symfony Scheduler in Place of Cron

## Status

Accepted

## Context

Scheduled jobs ran in a `cron` container, built from its own `cron-prod` image stage with the cron daemon installed in `php-base`. The image held two crontabs: `live-crontab`, with 17 jobs, and `playground-crontab`, the same less the refund and the backup. `CRONTAB_FILE` in each stack's env file picked one when the container started, and the container copied its environment into `/etc/environment` so the jobs could see it. `compose.prod.local.yaml` replaced its command with `tail -f /dev/null` so a local production run never ran a job. CI built and pushed the `cron` image alongside `php`, `nginx` and `reset`.

The plan for FrankenPHP (tracked in #68, after the skeleton's ADR 014) goes to one image for the web server and the worker. A cron daemon would have to be added to that image, as a second process in a container that otherwise runs one command, and the cron stage could no longer run as root.

Cron also started jobs due at the same minute together. `app:build-purchase-orders` and `app:update-supplier-stock` both ran at :00, :15, :30 and :45, and live's log showed about 11 deadlocks a day between them.

## Decision

- Scheduled jobs are listed in one class, `src/Shared/Infrastructure/Scheduler/MainSchedule.php` (`#[AsSchedule]`, the `default` schedule). Each job is a `RecurringMessage::cron()` with a `RunCommandMessage`, so a job is still a console command that can be run by hand. `debug:scheduler` lists them.
- **One schedule for both stacks.** `PLAYGROUND_MODE`, which both env files already set, leaves out the live-only jobs: `app:refund-purchase-orders` (a playground refund at 01:30 would be replaced by the 02:15 reset from live's backup) and `app:backup-database`. `CRONTAB_FILE` is no longer read.
- **One worker.** In production the `messenger` container consumes `scheduler_default async`. The scheduler comes first because the worker returns to the first receiver after each message; it only returns jobs that are due. Development and `make up-prod-local` consume only `async`, so no job runs on a local machine.
- **Jobs run one at a time**, in the order they are listed when several are due together. The backup is listed first, so at 02:00 it runs before the hourly shipping and delivery jobs and its copy is ready for the 02:15 playground reset, as under cron.
- The schedule is **stateful** on `cache.scheduler` (the app cache, Redis) and processes only the last missed run. The worker restarts every hour (`--time-limit=3600`) and on each deploy; a run that fell due meanwhile happens when it is back, once.
- The schedule is **locked** with the app's lock factory, so two workers in the same container never run a job twice. `LOCK_DSN` stays `flock`: a manual backup runs in the `messenger` container to share the backup's lock.
- **`app:create-customer-orders` no longer sleeps.** It slept up to 300 seconds between orders to spread them over the 5-minute cron interval, which would hold the worker. It now creates its orders at once, with a default count of 1. The schedule spreads them instead: two entries every 5 minutes (`*/5` and `2-59/5`), each `app:create-customer-orders 1 --random` with `withJitter(290)`. The average is unchanged at 12 orders an hour, and each arrives alone at a random moment.
- The `cron-prod` stage, the `cron` package in `php-base`, the `cron` service in every compose file, the app crontabs and the `cron` image in CI are removed.
- The playground reset keeps its own container and its own cron. It is a shell script that needs the MySQL client, the AWS CLI and `redis-cli`, none of which belong in the application image.

## Consequences

- One fewer image, one fewer container, and no environment copying.
- Jobs due together no longer run at the same second, which should end the deadlocks between building purchase orders and updating supplier stock.
- While a job runs, the worker handles nothing else, so async messages (emails, async domain events) wait. The jobs take seconds, and the backup minutes at night. If a job grows long, `scheduler_default` gets a worker of its own, in one container only (the lock is a file).
- A scheduled command that throws or exits with a failure code becomes a failed message, reported to Sentry as `RunCommandFailedException`. Under cron those failures were only in the container log. The simulation and reporting commands exit with a failure when any item fails, so a partial failure is now reported once per run. Scheduled messages are not retried and not sent to the `failed` transport; the next run is the next scheduled time.
- A successful run's output is no longer written to the log: `RunCommandMessage` keeps it. Errors the commands log still are.
- The nightly playground reset flushes the playground's Redis, and with it the schedule's state. The worker then carries on from the current time, so nothing is made up and nothing runs twice.
- Schedule times follow PHP's `date.timezone`, which the image sets to UTC, as the container's cron did.

## Alternatives Considered

- **Keep cron and add it to the single image.** Rejected: a second process in the worker container, run as root, and the environment copying, for a job list the framework can hold.
- **A separate scheduler worker.** Not needed while every job takes seconds; it is the step to take when one does not.
- **A crontab per stack, kept as files.** Rejected: the two lists differ by two jobs, and one class with a `PLAYGROUND_MODE` check keeps them in step.
- **Keep the order command's sleep.** Rejected: it would hold the only worker for up to five minutes in every five.
