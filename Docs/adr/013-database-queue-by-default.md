# ADR 013: Database Queue by Default, RabbitMQ as an Opt-in

## Status

Accepted

## Context

Async work goes through Symfony Messenger's `async` transport: outgoing email and any event implementing `AsyncDomainEventInterface`. From July 2025 that transport was RabbitMQ, on both production stacks (live and playground), through the `checkthiscloud/phpamqplib-messenger` package.

RabbitMQ was added for throughput and future compatibility, but the workload has not needed it:

- No domain event is async. The queue carries outgoing email only: registration, password reset and admin access emails.
- The transport came from a third-party package below version 1.0.
- There was no test transport, so the test suite needed a running broker and could not assert on what it sent.
- Each stack paid for a container, a password, a volume and a PHP extension (`sockets`).

The `failed` transport already used the database (`doctrine://default?queue_name=failed`), so the `messenger_messages` table already existed in every environment.

The same change was first made in the project skeleton this application shares its set-up with (its ADR 012), and was then ported here.

## Decision

### The `async` transport defaults to the database

`MESSENGER_TRANSPORT_DSN` is `doctrine://default` in `.env`, in `compose.override.yaml`, and as the fallback in `compose.prod.yaml`, so the production env files no longer set it. Messages are rows in `messenger_messages`, the table the `failed` transport already used, so there is no schema change. The transport is Symfony's own (`symfony/doctrine-messenger`).

### Tests use an in-memory transport

Under `when@test`, `async` is `in-memory://`. The suite runs with no queue and no worker, and a test can assert on what was sent through `getSent()` (`ResetPasswordFlowTest`).

### RabbitMQ stays available behind a compose profile

The `rabbitmq` service keeps its definition in every compose file but belongs to the `rabbitmq` profile, so it starts only when that profile is on. `php` depends on it with `required: false`, so it waits for the broker when the profile is on and ignores it otherwise. `make down` passes `--profile '*'`, so it removes the broker whether or not the profile is on.

The transport package and the `sockets` extension are removed: production no longer installs either. Switching back adds them again, then turns the profile on and changes the DSN. The steps are in [Switching to RabbitMQ](../02-setup-local.md#switching-to-rabbitmq). Application code does not change, because Messenger hides the transport.

### The playground restore leaves live's queue out

The playground is rebuilt each night from live's backup, which now holds live's queue. The reset script drops the rows of `messenger_messages` from the dump as it restores it, so the playground worker never sends live's emails. This replaces the RabbitMQ purge the script used to run.

## Consequences

- Each production stack runs one container fewer, and local development runs MySQL, Redis and Mailpit without a broker.
- A queued message is written to the same database as the change that caused it. The two writes are still separate statements, not one transaction.
- The worker polls the table (`messenger:consume async --sleep=5`), so an email can wait a few seconds before it is sent. A broker pushes messages immediately.
- The database queue suits modest volumes. If async events with a high message rate are added, switch; the steps are config only.
- Messages waiting in the queue are in the nightly backup, so a restore brings them back and the worker sends them again.
- A production env file written before this change still sets `MESSENGER_TRANSPORT_DSN` to the AMQP address, which overrides the default. Remove that line and `RABBITMQ_PASSWORD`: the transport package is no longer installed, so the worker would fail to start.
- The RabbitMQ volume (`rabbitmq_data`) is left on the server for one release after the cutover, then removed.

## Alternatives Considered

- **Keep RabbitMQ as the default.** Rejected: a broker and a pre-1.0 package for a workload that is one email queue.
- **Remove RabbitMQ entirely.** Rejected: the compose and production set-up is proven, and keeping it behind a profile costs a few lines.
- **Keep the `sockets` extension in the image so opting in needs no image change**, as the skeleton does. Rejected here: this application runs in production, and an extension nothing uses is surface for no gain. Adding it back is one line in `docker/Dockerfile`.
- **A dedicated test library** (`zenstruck/messenger-test`). Not needed: the built-in in-memory transport covers asserting on sent messages.

## References

- [Switching to RabbitMQ](../02-setup-local.md#switching-to-rabbitmq)
- [The Queue](../03-architecture.md#the-queue)
- `config/packages/messenger.yaml`, `scripts/playground-reset.sh`
