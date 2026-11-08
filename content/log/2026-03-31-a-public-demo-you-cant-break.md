---
title: A public demo you can't break
period: March 2026
summary: Going public meant letting strangers sign in as staff. A second stack restored from live every night, credentials removed in the same step, and the guards a public repository needs.
figures:
  - value: "2"
    label: isolated stacks on one server
  - value: "02:15"
    label: nightly reset from live's backup
  - value: "0 s"
    label: window with real credentials on the playground
  - value: V1.0
    label: public release, 31 March
refs:
  - label: Playground infrastructure
    url: https://github.com/myvars/supplymars/blob/main/Docs/infrastructure/playground.md
  - label: Security
    url: https://github.com/myvars/supplymars/blob/main/Docs/08-security.md
  - label: V1.0 technical review
    url: https://github.com/myvars/supplymars/blob/main/Docs/12-technical-review.md
---

SupplyMars is a back-office tool. Its interesting parts are behind the staff login: allocating orders, rejecting purchase orders, changing a category's markup and watching prices cascade. A portfolio piece that hides all of that behind a login teaches a visitor nothing, so for the public release I wanted anyone to be able to sign in as staff and use everything.

That can't happen on the live site. Live runs the simulation day and night, and its data is the record of everything the business has done. So the visitor gets their own copy.

## Two stacks, one server

The server now runs two complete stacks side by side, live and playground, each its own Docker Compose project with its own database, Redis, queue and PHP containers. They share no networks. Caddy sits in front of both and handles TLS. Each stack has its own environment file, its own passwords and its own schedule, and phpMyAdmin shows a different footer on each so nobody mistakes one for the other.

## Reset every night

A playground is only safe if whatever a visitor does is undone. Every night:

1. At 02:00, live's backup command writes the nightly dump to S3, as it always has, and now also keeps a local copy for the playground.
2. At 02:15, the playground's reset container restores that copy into the playground database.
3. In the same step, it scrambles every staff credential and creates the demo user.
4. It copies live's product images to the playground's own S3 prefix, flushes the playground's Redis and empties its queue.

The playground starts each day as an exact, current copy of live, minus anything that would let someone into a real account.

## No window with real credentials

The order of step 3 matters more than anything else here. I first wrote the redaction as a PHP console command, run after the restore. Looking at it again the same afternoon, I saw that it left a gap: for the seconds between the restore finishing and the command running, the playground database held live's real password hashes.

So the redaction became plain SQL, `playground-redact-staff.sql`, run by the reset container straight after the restore, in the same script and the same connection. There is no moment when the restored data is reachable with real credentials in it. The PHP command was deleted.

## Signing in, and what's switched off

The playground's login page has one button: sign in as the demo staff user. It sits behind Cloudflare Turnstile, already in use on registration and sign-in since February, so bots don't spend the demo's resources. File uploads are switched off in playground mode.

## Guards a public repository needs

Making the repository public at the same time raised the bar for the code itself.

- **A super admin role** now sits above staff. The deletes that would do real damage, such as products, suppliers and VAT rates, need it, and so does editing a staff account. Lower-risk deletes stay open to staff, and records still in use refuse to be deleted through `isDeletable()` checks in the domain.
- **Authorisation lives in the handlers.** The delete flow had started to grow its own permission checks. I moved them back into the handlers, so the same rule applies whether a change comes from a form, the API or a console command, and the flow goes back to only coordinating.
- **Tighter rate limits** on password reset and resending the verification email: two a minute, with a burst of three for normal navigation.
- **Hard-coded personal email addresses** were removed before the repository went public.

## V1.0

On 31 March I tagged the public release and wrote a technical review to go with it: an architectural review of the codebase as it stood, with its strengths, the trade-offs I had made, and what I would change next. It is in the repository as a dated snapshot, and some of its recommendations became the work of the autumn.
