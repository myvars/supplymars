---
title: PHP 8.5, PHPStan level 7, and writing it all down
period: January 2026
summary: The month the bounded-context rebuild merged, the test suite was rebuilt for it, static analysis went to level 7 with an empty baseline, and the project got its documentation and first ADRs.
figures:
  - value: 136 → 204
    label: test files in two weeks
  - value: 6 → 7
    label: PHPStan level, empty baseline
  - value: "36"
    label: documents written in one pass
  - value: "6"
    label: ADRs recording two years of decisions
refs:
  - label: Docs index
    url: https://github.com/myvars/supplymars/blob/main/Docs/README.md
  - label: ADRs
    url: https://github.com/myvars/supplymars/tree/main/Docs/adr
  - label: Testing guide
    url: https://github.com/myvars/supplymars/blob/main/Docs/07-testing.md
---

On 16 January the bounded-context rebuild merged after three months on its own branch. A change that size leaves work behind it, and January was about doing that work properly: tests, tooling, the pipeline, and writing down how the system works and why.

## Rebuilding the tests

The new structure changed nearly every class's location and many of their shapes, and the old tests were written against the old ones. Rather than patch them into place, I rebuilt the suite context by context against the new code:

- Order and Purchasing first, the core of the business, replacing forty files of old tests.
- Then Reporting, then the chart builders, which were refactored at the same time so each chart type could be tested on its own.

Test files went from 136 to 204 in two weeks, organised to mirror `src/` so every class's tests are where you expect them.

## Static analysis to level 7

PHPStan moved from level 6 to 7, with the Symfony and Doctrine extensions so it understands the container and the entity mappings. Getting there touched 289 files. The baseline it created was empty: every error was fixed, none was recorded to ignore later.

Much of the work was types. Money in SupplyMars is decimal strings handled with `bcmath`, never floats, and the code now says so: dozens of parameters and return types are documented as `numeric-string`, so PHPStan catches a float or an arbitrary string arriving where an amount should be.

## PHP 8.5 and the pipeline

The project moved to PHP 8.5.2. The Docker build was restructured so dependency layers are cached separately from the application code, and production secrets moved out of the repository entirely.

CI became a real gate. It used to build and deploy on every push to `main`. Now it runs on pull requests as well, the tests run as their own job, and deploying waits for them to pass. The build cache is keyed on the files that actually change the image (the Dockerfile, the compose files and `composer.lock`), so an ordinary code change reuses it.

## Writing it all down

Two years of decisions lived in my head and in the code. On 26 January I wrote them down, in one pass:

- **Ten developer guides**: an overview of the domain, local setup, the architecture, sourcing and pricing, features, operations, testing, security, the CLI and a code reference.
- **A user manual** of fourteen chapters and a troubleshooting guide, written from the staff user's side of the screen.
- **Six architecture decision records**, each with its context, decision and consequences: multi-supplier sourcing, order line splitting, simulation-first design, the pricing cascade, the reporting strategy and FormFlow.

The ADRs record decisions made over the previous two years, some of them in 2024. Writing them down after the fact was still worth it: the next change to sourcing or pricing starts from the reasoning, not from guessing at it.

The same week I added a `CLAUDE.md` to the repository, the instructions an AI coding agent reads before it works on the project. I rewrote it two days later. [From ChatGPT to Claude Code](/log/from-chatgpt-to-claude-code) tells that part of the story.
