# ADR 0011 — Public demo: one shared read-only account, writes visibly locked

**Date:** 2026-06-01
**Status:** Accepted

## Context

Before launch, a Belgian crypto-holder lands on the homepage. The product
pitch ("we turn your exchange CSV into a tax-ready EUR number with the
right Cadre codes") is abstract until they see it running with realistic
data. Asking them to register, import a CSV they may not have, then
discover the value is too much friction for an interest signal.

A demo — pre-seeded, instantly accessible, no signup — collapses that
chain. Notion, Linear and Figma all do versions of this. It is the
single highest-ROI conversion lever we can ship before launch, and uses
infrastructure we already have (the Kraken demo CSV, the event-sourced
Portfolio, the three-tab UI).

## Decision

### One shared demo account, no per-visitor sandbox

A single user `demo@koersa.local` with one organisation, pre-seeded once
by a console command (`bin/console demo:seed`). Every visitor sees the
same data. We do not mint per-visitor accounts because:

- Writes are blocked, so visitors cannot meaningfully diverge.
- Per-visitor sandboxes mean TTLs, cleanup jobs, quota tracking and
  multi-tenant isolation work we do not currently need.
- Shared accounts let a 1MB ECB rate cache and a 5-min CoinGecko cache
  serve every visitor cheaply.

If a future requirement is "let demo visitors *try* recording their own
trade and keep that trade across sessions," that is per-visitor work and
gets its own ADR.

### Writes are visibly locked, not silently denied

Every write action — Record / Amend / Remove a transaction, Import a
CSV, Download the PDF report — stays visible in the UI in demo mode but
carries a 🔒 affordance. Clicking opens a "Sign up to unlock" dialog
(reusing the existing `form-modal` Stimulus machinery) with a single
CTA back to `/register`.

The framing is **discoverability over silent denial**. The visitor
sees what the product can do, hits the lock at the moment they want
the feature, and the conversion ask is contextual ("you wanted to
record a trade — sign up to actually do that") rather than abstract
("hey come sign up"). Silently disabling buttons hides the value;
silently failing writes feels broken.

### Server-side voter as defence in depth

A Symfony voter, `DemoWriteVoter`, denies every write action when the
current user is the demo account. The UI lock is the user-facing
mechanism; the voter is the safety net (if a write button slips through
without the lock data attribute, the server still says no).

### Persistent top banner

Above the nav, on every authenticated page in demo mode, a thin
indigo strip: *"Demo mode · exploring sample data · Sign up to use
Koersa with your own trades →"*. The strip survives navigation; the
visitor never forgets they are in demo.

### Auto-login at `/demo`

The route is unauthenticated; the controller programmatically logs the
visitor in as the demo user and redirects to `/`. Bookmarkable, share-
able, deep-linkable.

### Demo identification

A small `Shared\Demo\IsDemoUser` service (single method
`__invoke(?UserInterface $user): bool`) returns true if the user's
identifier is the demo email. Used by:

- The persistent banner Twig partial
- The write-lock Stimulus controller (data attribute fed by the same
  Twig check)
- The `DemoWriteVoter`

Single source of truth on what counts as "the demo user."

## Out of scope (this slice)

- **Per-visitor isolated sandboxes.** Same demo account for everyone.
- **Reset-on-entry / scheduled reset of demo data.** Writes are
  blocked, so the data never drifts. Re-running `demo:seed` is a
  manual operation when we want to refresh.
- **Watermarked demo PDF.** PDF download is locked behind the same
  paywall as everything else; we do not generate a "DEMO" stamped
  variant.
- **Demo-specific guided tour** — popovers, "click here next."
  The lock-on-click dialogs already serve the discoverability role.
- **A separate demo subdomain or database.** Demo account lives in
  the main DB as a regular user that happens to be flagged read-only.
- **Demo data refresh tied to calendar.** If "most recent year with
  sells" stays 2025 forever as time passes, the Tax tab keeps
  showing that year. Re-seed the CSV when the year feels stale.

## Consequences

- Anyone, including search engines, can deep-link `/demo` and land in
  a working app. We add `/demo` to the sitemap and set canonical
  links so the SEO surface grows.
- One more user in `iam_users` with `is_admin = false` and a
  membership in one specific org. Counts on the admin page now show
  +1 user and +1 org by default; acceptable.
- Demo writes will surface the sign-up dialog frequently. The
  registration form must therefore stay friction-light (no double
  password, no captchas yet — it doesn't have any of those now).
- Server-side write denials need a tested path. A WebTestCase logs in
  as the demo user, POSTs to `/portfolio/transactions/new`, asserts a
  403 or a redirect with a "demo-locked" flash.

## References

- `tests/Fixtures/Import/kraken_trades_demo.csv` — already-curated
  realistic trade history that the demo will replay
- `assets/controllers/form_modal_controller.js` — reusable dialog
  infrastructure for the "Sign up to unlock" prompts
- `ARCHITECTURE.md` §2 — IAM owns user identity; the demo flag is
  derived from the email, no schema change
