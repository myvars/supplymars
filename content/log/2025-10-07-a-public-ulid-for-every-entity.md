---
title: A public ULID for every URL
period: October 2025
summary: Every record behind a URL got a ULID, added to tables with rows in them through a nullable column, a streamed backfill and a typed ID per entity. Finishing the job, and two bugs, came a year later.
figures:
  - value: "18"
    label: tables given a public ID in one migration
  - value: "200"
    label: rows per batch in the backfill
  - value: "21"
    label: typed ID value objects by January
refs:
  - label: Architecture
    url: https://github.com/myvars/supplymars/blob/main/Docs/03-architecture.md
  - label: PR #89 · Stop rewriting public_id on every flush
    url: https://github.com/myvars/supplymars/pull/89
  - label: PR #161 · Make publicId NOT NULL
    url: https://github.com/myvars/supplymars/pull/161
---

Every URL in SupplyMars used the database's auto-increment ID: `/order/1042`, `/product/57`. An ID like that tells anyone how many orders exist and what the next one will be. In October 2025 I gave every entity behind a URL, 18 of the 24, a second identifier for the outside world, a ULID, and kept the integer for the database's own relations.

A ULID is 128 bits, like a UUID, but its first part is a timestamp, so new IDs sort by creation time and index well. It is 26 characters in base 32, safe in a URL. Symfony's Uid component has it built in, and Doctrine stores it as `BINARY(16)`.

## Adding a column to tables with rows

The tables already held production data, so the column couldn't arrive as `NOT NULL`: every existing row would need a value at once, and the unique index rules out a shared default. It went in three steps:

1. **A nullable column.** One migration added `public_id` with a unique index to 18 tables: orders and their items, purchase orders and theirs, products, suppliers, users and the rest. Nullable, so the migration succeeds against tables full of rows.
2. **New rows get one straight away.** A `HasPublicUlid` trait holds the mapping, and each entity's constructor calls `initializePublicId()`.
3. **A backfill for the old rows.** `app:backfill-ulids` finds every entity using the trait, counts its rows without an ID, and streams them with Doctrine's `toIterable()`, flushing and clearing the entity manager every 200 rows so memory stays flat however big the table, with a progress bar. A `--limit` option runs it a slice at a time.

The plan for the last step was written into the trait: once every row had an ID, make the column `NOT NULL` in a second migration.

## A type per ID

A ULID is a string, and a string can be any entity's ID. So each entity gets its own value object: `CustomerOrderPublicId`, `ProductPublicId` and so on, each extending `AbstractUlidId`. A method that takes a `PurchaseOrderPublicId` can't be passed an order's ID by mistake; PHPStan rejects it before the code runs, and PHP at the call. The new domain events carry these typed IDs instead of entities.

The audit log still stored the integer IDs, so a small registry translates a public ID back to its internal one, with one resolver per type.

The URLs themselves switched on the rebuild branch, where a single value resolver, `#[ValueResolver('public_id')]`, loads any entity by the ULID in its path. By January there were 21 typed ID classes.

*Update, October 2026: the column stayed nullable for a year, and two bugs surfaced around it. Investigating a deadlock on live led to the first. The trait mapped the ULID column onto a string property, while Doctrine hydrates it as a `Ulid` object. On every flush Doctrine compared the two, saw a change, and wrote `public_id` and `updated_at` back for every loaded entity. That was behind the deadlock, and it made "last updated" unreliable on every record. The property is now a `Ulid` ([#89](https://github.com/myvars/supplymars/pull/89)). A week later, making the column `NOT NULL`, I found `status_change_log` had 2,257,122 rows without an ID: its constructor never called `initializePublicId()`, and nothing ever read that ID. The audit log dropped the column, and the other 23 tables went `NOT NULL` in a migration that counts nulls first and aborts before changing anything ([#161](https://github.com/myvars/supplymars/pull/161)). The service that caches resolved IDs also learned to clear itself between requests, for worker mode.*
