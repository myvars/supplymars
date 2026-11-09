---
title: PHP 8.5, PHPStan level 7, and writing it all down
period: January 2026
summary: The month the bounded-context rebuild merged, the test suite was rebuilt for it, static analysis went to level 7 with an empty baseline, and the project got its documentation and first ADRs.
refs:
  - label: Docs index
    url: https://github.com/myvars/supplymars/blob/main/Docs/README.md
  - label: ADRs
    url: https://github.com/myvars/supplymars/tree/main/Docs/adr
  - label: Testing guide
    url: https://github.com/myvars/supplymars/blob/main/Docs/07-testing.md
---

In mid-January the [bounded-context rebuild](/log/rebuilding-by-bounded-context) merged after three months on its own branch. A change that size leaves work behind it, and January was about doing that work properly: tests, tooling, the pipeline, and writing down how the system works and why.

## Rebuilding the tests

The new structure changed nearly every class's location and many of their shapes, and the old tests were written against the old ones. Rather than patch them into place, I rebuilt the suite context by context against the new code:

- Order and Purchasing first, the core of the business. The same change retired the old suite, 174 test files written against the previous structure.
- Then Reporting, then the chart builders, refactored at the same time and given tests for each chart type.

Test files went from 136 to 190 in eleven days, organised to mirror `src/` so every class's tests are where you expect them.

## Static analysis to level 7

PHPStan moved from level 6 to 7, with the Symfony and Doctrine extensions so it understands the container and the entity mappings. Getting there touched 289 files. The baseline it created was empty: every error was fixed, none was recorded to ignore later.

Much of the work was types. The pricing engine has handled money as decimal strings with `bcmath` since 2024, and dozens of parameters and return types are now documented as `numeric-string`, so the types say what a value holds. Order and purchase order totals were still summed as floats; the February audit found that, and they moved to `bcmath` too.

## PHP 8.5 and the pipeline

The project moved to PHP 8.5.2. The Docker setup was simplified: unused stages went, the worker and cron containers wait for a healthy PHP container, and only that container runs migrations.

CI moved earlier in the process. It used to run only on pushes to `main`, testing and deploying in one job. Now it also runs on every pull request, so a change is tested before it merges, and testing and deploying are separate jobs, with the deploy depending on the tests. The build cache is keyed on the files that actually change the image (the Dockerfile, the compose files and `composer.lock`), so an ordinary code change reuses it.

## Writing it all down

Two years of decisions lived in my head and in the code. In late January I wrote them down, in one pass:

- **Ten developer guides**: an overview of the domain, local setup, the architecture, sourcing and pricing, features, operations, testing, security, the CLI and a code reference.
- **A user manual** of fourteen chapters and a troubleshooting guide, written from the staff user's side of the screen.
- **Six architecture decision records**, each with its context, decision and consequences: multi-supplier sourcing, order line splitting, simulation-first design, the pricing cascade, the reporting strategy and FormFlow.

The ADRs record decisions made over the previous two years. With them in place, the next change to sourcing or pricing starts from the reasoning instead of from guessing at it.

The week before, I had added a `CLAUDE.md` to the repository, the instructions an AI coding agent reads before it works on the project, and had rewritten it within days. [From hand-written code to Claude Code](/log/from-hand-written-code-to-claude-code) tells that part of the story.
