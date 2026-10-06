# AGENTS.md

The instructions for AI coding agents working in this repository live in
[`CLAUDE.md`](CLAUDE.md). Read that file in full before making changes and treat
it as authoritative — it covers commands, architecture, patterns, testing, code
style and workflow for this project.

This file replaces the generic `AGENTS.md` shipped by the `symfony/framework-bundle`
recipe. That default assumes a blank Symfony app; this project has already made
those choices (Doctrine ORM, Twig, SecurityBundle, DDD bounded contexts), so its
guidance is superseded by `CLAUDE.md`. If `composer recipes:update` offers to
restore the default content, keep this version.
