# ADR 0012 — Register-then-seed freemium: hidden pricing, paywall on action

**Date:** 2026-06-01
**Status:** Accepted
**Supersedes:** [ADR 0011](0011-public-demo-account.md)

## Context

[ADR 0011](0011-public-demo-account.md) shipped a *shared* read-only demo
account behind `/demo` with writes globally locked. Two days of use surfaced
the friction problem: a visitor who isn't sure they want to sign up is
shown a "Sign up to use your own data" banner on every page. The conversion
ask is abstract — they didn't try to do anything yet, and we're already
selling.

The Circleboom playbook (and most modern freemium SaaS — Notion, Linear,
Webflow) inverts the order:

1. Free signup, no card, **no pricing on the landing page**.
2. After signup, the user lands in a fully-populated dashboard with sample
   data — *their own copy*, not shared.
3. They explore every feature as if they had paid.
4. The wall appears at the **moment they try to use** a paid feature
   ("Get Belgian tax reports — €X/mo"), not before.

The conversion ask becomes contextual: *"you wanted to download a tax
report — here's the plan that does that."* Far higher signal than the
abstract "sign up to use your own data" we had.

## Decision

### Each new user's org is auto-seeded with the demo trades

`RegisterUserHandler` raises a domain event `UserRegistered`. A Reporting
handler listens and seeds the new org with the 14 trades from the existing
demo CSV through real `ImportTransactions` commands. Per-user copy, no
cross-tenant data leakage. Replay uses the production code path, so any
future event evolution still applies automatically.

The user can immediately edit, delete and add to those trades — it's their
data. Free tier.

### Free / paid split

| Tier   | Includes                                                                                                           |
|--------|--------------------------------------------------------------------------------------------------------------------|
| Free   | Manual record / amend / remove transactions · Holdings dashboard · Live EUR prices · Overview charts · Language switching |
| Paid   | CSV import (Kraken, Binance) · Tax tab in full (realized gains, regime estimates, how-to-declare) · PDF tax report |

The unique Belgian-tax automation sits in the paid tier — that's the
defensible value-add. Manual portfolio tracking is commodity and stays
free; if a user wants to type their trades in by hand, they should be
allowed to. They get a real product for free; they pay when they want the
machinery that saves them filing-season hours.

### Paywall is a dialog, not a redirect

Clicking a paid feature on a free account opens a Stimulus-driven dialog
(reuses `form-modal`) with:

- One-sentence pitch ("Get realized gains in EUR, regime estimates and a
  PDF for your accountant — €X/mo")
- A "Get notified when this launches" email-capture form (until Stripe
  lands)
- A "Keep exploring" dismiss button

When Stripe lands, the email-capture form is swapped for a
checkout button. The dialog shape stays.

### No pricing on the landing page

The current `/{locale}` landing page has a beta-signup form. That form
gets **replaced** with a *"Sign up free"* CTA that links straight to
`/register`. Pricing lives at `/pricing` (built later) but isn't linked
from the landing. The user only sees prices when they actively hit a
paid wall — by which point they've felt the value.

### Demo-data hint is dismissible, not persistent

The persistent "demo mode" banner from ADR 0011 is replaced with a
small, dismissible card on the Overview tab: *"This is sample data —
add your own trades anytime."* A cookie remembers dismissal across the
session; no schema change. The hint never reappears once dismissed.

### What goes away from ADR 0011

- `/demo` auto-login route and `DemoLoginController`
- The shared `demo@koersa.local` user (the seed command becomes a private
  per-org service, no longer a standalone CLI tool)
- `DemoWriteVoter` — replaced by `IsPaidUser` + a feature-flag voter; not
  the same shape

What we **keep**:

- `IsDemoUser` evolves into `IsOnSeededDemoData` (or stays the same name
  if it ends up only checking the dismissal cookie)
- `DemoExtension` Twig hook
- The Kraken demo CSV at `tests/Fixtures/Import/kraken_trades_demo.csv`
- The replay machinery from `SeedDemoUserCommand`, lifted into a
  reusable service

### `IsPaidUser` service today

A `Shared\Security\IsPaidUser` invokable returning `false` for everyone
until Stripe wiring exists. Single place to flip when subscriptions
land. The paywall voter calls it; the UI lock data attributes call it.

## Out of scope (this slice)

- **Real Stripe checkout.** Paywall captures emails until Stripe
  accounts and products are set up (PRE_LAUNCH.md item).
- **Pricing page** at `/pricing`. Built when there are real plans.
- **Org-level subscription** (one user pays, whole org gets paid
  features). Per-user for now; flips later.
- **Trial windows** ("paid for 7 days then locks"). Not needed yet —
  the seeded-demo-data exploration *is* the trial.
- **Annual vs monthly pricing UI** — out until the model is set.
- **Stripe webhook handling for subscription state** — needs Stripe.
- **Different paywalls per feature** ("PDF requires Pro; CSV import
  requires Plus") — flat tier today.

## Consequences

- `RegisterUserHandler` no longer cleanly owns "create user." It also
  triggers a side effect (demo seeding) via an event. Decoupled through
  the message bus so failures don't cascade — a Reporting-side seed
  failure logs but doesn't roll back the user.
- The beta-signup form on `/{locale}` goes away. The 1 beta signup we
  have today (`adrienhecq@gmail.com`) is now reachable through the
  admin landing; the new path is straight to `/register`.
- Tests covering "shared demo / writes locked" get removed. Tests
  covering "register-then-seed" get added.
- Adrien himself stops being a "real" user in the admin sense after
  the pivot — every account, including his, now starts seeded with
  demo data and has the same paywall surface. Convenient for "see
  what a fresh user sees" debugging.

## References

- [ADR 0011](0011-public-demo-account.md) — superseded
- `tests/Fixtures/Import/kraken_trades_demo.csv` — the seed payload
- `src/IAM/Application/RegisterUserHandler.php` — gets a side effect
- `src/Reporting/Infrastructure/Console/SeedDemoUserCommand.php` —
  gets dismantled; logic moves to a Reporting service
