---
title: From ChatGPT to Claude Code
period: March 2023 – November 2026
summary: How the way I build changed over three years of AI tools, from pasting answers out of a chat window to an agent working from my issues and rules, and what has stayed mine throughout.
figures:
  - value: "0% → 77%"
    label: commits with a written explanation
  - value: 377 → 239
    label: lines in CLAUDE.md, as it became rules
  - value: "0"
    label: production commands run by an AI
refs:
  - label: CLAUDE.md
    url: https://github.com/myvars/supplymars/blob/main/CLAUDE.md
  - label: About the developer
    url: https://www.supplymars.com/about
---

I have used AI tools for the whole life of SupplyMars, and for a while before it. They changed a great deal over three years. My job on the project didn't, and that is the part worth explaining.

## ChatGPT: an assistant in a window

I subscribed to ChatGPT in March 2023, ten months before the first commit. For most of the project's life that was the whole of my AI tooling: a chat window beside the editor. I would describe a problem, read the answer, take what was useful, adapt it to the codebase and paste it in.

Every line went through me. The questions that shape this project, such as how a product should pick a supplier, how a status rolls up through an order, and where one context ends and the next begins, were mine to answer, and the code from that period was written by hand, with AI as an assistant.

## Copilot: in the editor

In September 2025 I added GitHub Copilot to the IDE and used it for about five months. That covered the biggest refactor in the project's history, the move to bounded contexts: 1,200 files, three months on a branch. Copilot worked as completion in the editor. The design of the migration was mine: the contexts, the layers, the commands and handlers, and FormFlow.

## Claude Code: an agent with rules

In January 2026 I started using Claude Code, an agent that works in the repository itself: it reads the code, runs the tests and the tools, and makes changes. That needs a different kind of preparation, because an agent will follow whatever conventions it can infer, good or bad. So the first thing I wrote for it was `CLAUDE.md`, 377 lines on the architecture and how the project is built, on 20 January. I rewrote it two days later.

The tooling around it grew from there. In February I connected two MCP servers: Context7, so it reads current library documentation rather than relying on memory, and Playwright, so it can open the app in a browser and check what it built. I used it to run a structured audit of the codebase with five reviewer roles, which became the work of [audit week](/log/audit-week), and to plan new contexts before building them.

By the autumn the way of working had settled. Almost every change starts as a GitHub issue that I write or approve, with its scope and acceptance points. The agent plans against the project's docs and ADRs, I agree the plan, it builds on a branch, and a pull request says what changed, how it was tested, what was left out and what to look at closely. On larger changes, a second agent with no knowledge of the first one's reasoning reviews the diff. In five weeks that produced [119 pull requests](/log/five-weeks-119-pull-requests).

`CLAUDE.md` changed with it. It is now 239 lines, shorter than the first version, because it became a set of rules that each link to the document with the detail, instead of a second copy of the documentation.

## What the history shows

The clearest change is in how the work is recorded. I counted the commits that carry an explanation beyond their one-line title:

| Period | Commits | With an explanation |
|---|---|---|
| ChatGPT, Jan 2024 – Aug 2025 | 157 | 0% |
| Copilot, Sep 2025 – Jan 2026 | 8 | 0% |
| Claude Code, Jan – Jun 2026 | 140 | 49% |
| Claude Code, Oct – Nov 2026 | 394 | 77% |

Non-merge commits on `main`, by commit date, with Claude Code counted from 20 January 2026.

My early commit messages were one line. That made this log harder to write: the first two years had to be reconstructed from the diffs. Now each change explains itself, and each pull request carries its measurements and its trade-offs.

## What stays mine

Writing code is a smaller share of my time than it was. The responsibility is the same:

- **The architecture and the decisions.** The bounded contexts, the sourcing and pricing rules, the choice of FrankenPHP or Mercure or a database queue. Seventeen ADRs record them, and the recent ones, such as FrankenPHP, Mercure, the database queue and the Scheduler, list the alternatives I rejected.
- **The scope.** I write or approve every issue, and a change that grows beyond its issue comes back to me as a question.
- **The review.** I read the plan before any code and the diff before any merge. I merge every pull request myself.
- **Proof.** A fix comes with a test that fails without it. PHPStan at level 7, Deptrac's architecture rules and `make check` hold generated code to the same bar as mine, with no baseline to hide behind.
- **Production.** The agent has no access to the server. I run every production command myself.

This log was written the same way. Claude Code read nearly three years of commits and pull requests and drafted each post from them, every figure was checked against its source by a separate review, and I decided what went in.
