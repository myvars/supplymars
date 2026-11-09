---
title: A staff tool that feels alive
period: February – April 2026
summary: A design pass over the whole app. A darker default that borrows Mars's greys, oranges and reds, colour that tells staff a record's status and supplier at a glance, motion that draws the eye to what just changed, and a home page built from real screenshots.
refs:
  - label: UI patterns
    url: https://github.com/myvars/supplymars/blob/main/Docs/patterns/UI/README.md
  - label: Styling
    url: https://github.com/myvars/supplymars/blob/main/Docs/patterns/UI/Styling.md
  - label: ADR 008 · Server-driven UI
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/008-server-driven-ui-architecture.md
---

Back-office tools tend to look like forms in a grey box. The people who use them look at those screens all day, though, and a screen that makes the important thing obvious saves them time on every visit. I wanted SupplyMars to be clean and quick to read, and to feel dynamic: highlights, subtle animation and blur that draw the eye to what matters, whether that's a modal, a metric or something that has just changed.

The look had roots. Tailwind was there from the first commit. The Mars rebrand of October 2024 brought a Martian landscape and 250 AI-generated product images with a fun retro chic, and since September 2024 purchase orders had taken on their supplier's colour. Between February and April 2026, with the bounded contexts in place, I went over every screen.

## Martian by default

SupplyMars has a light mode and a dark mode, and dark is the default. It's the design I prefer: greys with orange and red accents, a hint of a Martian landscape. Light mode got a full pass on 11 February so it holds up for anyone who chooses it.

On 19 March the dark design got a system of its own. Opaque grey borders gave way to translucent white ones at three strengths, 10%, 6% and 4%, for primary, secondary and tertiary edges, over layered backgrounds, so depth comes from light rather than from heavier lines. The sidebar, the toasts and the user menu got a frosted-glass finish, and form inputs inside a modal sit one level deeper than the modal around them.

## Colour that means something

In a staff tool, colour is information, so each colour carries one meaning:

- **Status on the edge.** From 5 February, record cards carry a coloured left edge for their current status, so an order's state reads before any of its text does.
- **A colour per supplier.** From 12 February each supplier has its own colour scheme, chosen in the supplier form: violet, amber, teal or rose. On an order split across suppliers, you can see which supplier holds which line. On 17 March the bold supplier gradients became subtle tints, plus a small colour dot beside the supplier's name, so the cards stay readable and the supplier stays recognisable.
- **Nothing competing with status.** Profit badges became plain coloured text rather than tinted pills, so they no longer pulled the eye away from the status badge beside them.

On 17 March, whole purchase order cards on the order page became clickable, while the links inside them, such as the status badge and the product image, still go where they say.

## Motion with a purpose

The motion is small, and each piece is there to point at something:

- **Modals** scale in over a blurred page, so the page behind steps back.
- **Toasts** slide in, and the inline editor scales into place when a value is clicked.
- **Edit buttons and KPI cards** grow slightly under the pointer, and header buttons, pagination and tabs give a small press when clicked.
- **Pages** slide a few percent left or right on forward and back navigation, through the browser's view transitions, from 5 February.
- **New demo orders glow.** From 1 April a reusable Stimulus controller gives anything created within a set time a blue glow that fades over about three seconds, so a visitor who places a demo order sees it arrive in the list.

The charts are meant to feel alive too. They animate in, and they answer on hover with values formatted as money, counts or percentages.

All of it respects `prefers-reduced-motion`. For someone who has asked for less movement, the animations are switched off and the page transitions shortened to almost nothing.

## A home page made of the real thing

On 17 March the home page got a new hero, "Commerce, built for Mars", and a showcase of the app itself: real screenshots of the catalogue, order management and the reporting dashboard, in browser frames with a soft glow behind them, saved as WebP. The next day the About, Contact, Terms and Privacy pages moved to the same dark, full-width sections with Mars landscape headers, and on 25 March I rewrote the About page around my own story.

## Every breakpoint

It mattered that every screen looks great and works properly at every breakpoint, not just on a desktop monitor. Mobile styles have been part of the app since April 2024. This pass went further:

- The supplier sources on a product's stock page became a compact two-row table, with the active source marked by a green accent bar and a "Best Price" badge.
- The pricing cascade's three large cards became a compact three-column grid.
- The header's Help and Sign In buttons adapt to the width.
- A date picker's stylesheet carried its own copy of Tailwind's utilities, which broke responsive classes such as `hidden sm:inline`. It is now built into the single Tailwind stylesheet, before Tailwind's own rules.

Earlier, in February, iPhone users had got a [native swipe back](/log/an-app-without-an-spa) between pages.

*Update, October 2026: the general-purpose components, such as buttons, forms, dialogs and badges, moved from Flowbite to the Shadcn kit: [A UI kit the project owns](/log/a-ui-kit-the-project-owns). The look described here carried across.*
