# ADR 0007 — Regime-aware tax estimates: scenarios, not classification

**Date:** 2026-05-31
**Status:** Accepted

## Context

[ADR 0006](0006-tax-engine-foundations.md) shipped the realized-gains number in
EUR — a defensible cost basis on FIFO + ECB rates. That's the input to a tax
computation, not the answer to *"what do I owe."* From the dashboard today, a
Belgian user still has to multiply by their regime's rate by hand, knowing
which regime applies to them, with no help from the product. That gap is the
difference between "another portfolio tracker" and "the thing you pay for at
tax season."

Belgian crypto tax has three possible regimes for a natural person:

- **Normal management of private wealth** ("gestion normale du patrimoine
  privé" / "normaal beheer van privévermogen") — capital gains on crypto held
  as private assets are **tax-free**. Most retail holders fall here.
- **Divers revenue, speculative** (Art. 90, 1° CIR/WIB) — flat **33%** on the
  gain, declared as "divers revenue" in Box VII / code 1440-1441 of the
  declaration.
- **Professional revenue** (Art. 23 CIR/WIB) — treated as professional income,
  progressive **25%–50%** plus social contributions. Rare; requires the
  activity to look like a trade or business.

The SPF Finance / FOD Financiën assigns the regime case-by-case based on
factors that include trading frequency, holding period, leverage and
derivatives use, the share of total income, and whether the activity is
organized like a business. **There is no formula a piece of software can run
to decide which regime applies to a specific person.** Saying otherwise — even
implicitly, by picking one and showing only that number — is tax advice, and
puts Koersa on the hook for outcomes a user is supposed to determine with
their accountant or via a Service des Décisions Anticipées (SDA) ruling.

## Decision

### Show all three scenarios, never pick one

The dashboard displays a *"Potential tax owed (estimate)"* block with one row
per regime:

- *Normal management* → **€0** (with a `Tax-free under this regime` note)
- *Speculative* → **33% × max(gain, 0)** (losses don't generate refunds here)
- *Professional* → **shown as "varies (25–50% progressive)"**, no amount

We do not store a chosen regime, we do not compute "your tax" as a single
number, we do not let the UI suggest one is the right answer. The user (or
their accountant) reads the rows and picks the one that applies.

### Why "progressive" stays as a label, not a number

Computing the professional-regime amount needs the user's other taxable
income, social-contribution status, and bracket — none of which Koersa has
or should ask for in this slice. A "rough estimate" that ignores those would
be wrong often enough to mislead. The product is honest about the gap and
points to the accountant.

### Losses

For any regime, when the realized gain for the period is zero or negative,
the displayed estimate is **€0**, with a translated note that losses *may* be
deductible (against other divers revenue under the speculative regime, fully
under the professional regime, not at all under normal management) and that
the rules around carry-forward and offset are out of scope. We do not show
"negative tax" / refund figures — those aren't what Belgian tax law actually
produces.

### Disclaimer, always visible

A persistent note under the estimates block, in every language: *"Koersa
shows scenarios; it does not give tax advice. Confirm your regime with an
accountant or via a Ruling Commission (SDA/DVB)."* This isn't legalese
window-dressing — it is the honest statement of what the product does.

### Where the types live

- `Shared\Domain\Tax\Regime` enum — the three cases.
- `Shared\Domain\Tax\TaxEstimate` value object — `Regime` + nullable
  `Money $amountEur`.
- `Shared\Application\Tax\BelgianTaxEstimator` — pure function class with one
  method: `estimate(Money $gainEur): list<TaxEstimate>`. No I/O, no state,
  no DB access. *Lives in Application* even though it's a pure function:
  it is a service the controller autowires, and `config/services.yaml`
  excludes `src/**/Domain/` from the DI container by design (domain layer
  has zero framework dependencies, including DI registration). Data types
  stay in Domain; the service-shaped calculator goes one layer up.

The Shared placement is deliberate: a future `Reporting` context (per
ARCHITECTURE.md §2) will read the same types when generating PDF/Tax-on-web
outputs. Putting them in Portfolio would force Reporting to depend on
Portfolio later — wrong direction.

The dashboard's Portfolio controller calls the estimator with the total
gain from [ADR 0006]'s `GetRealizedGains` and passes the three estimates to
the template. No new query class is needed in Portfolio — the estimator is a
pure computation.

## Out of scope (this slice)

- **Auto-classification.** Nothing in this slice or any future one will read
  user behaviour and conclude "you are in regime X."
- **Per-asset regime mixing.** A user could in principle be a professional
  trader for one asset class and a passive holder for another. We do not
  model that; all gains for the period are estimated under each regime as
  a single block.
- **Year-end vs realized-vs-deemed-realized distinctions.** Belgian rules
  around forking, staking rewards, and lending have separate treatments not
  covered here. Out until an accountant verifies them.
- **Tax-on-web / MyMinfin box mapping** — slice 3.
- **PDF / accountant-ready export** — slice 4.
- **Carry-forward of losses, offset rules, exemption thresholds.** Real, but
  out until the per-regime branch is more developed than "show a number."
- **VAT, regional surcharges, communal surcharges on the speculative
  divers-revenue line.** Out — communal surcharges in particular vary by
  municipality and add maybe a few percent on top; explicitly omitted with a
  note for the user.
- **Non-Belgian residents.** The product is Belgian-tax-focused and assumes
  Belgian fiscal residency.

## Consequences

- The dashboard becomes recognisably a *Belgian* tax tool instead of a
  portfolio-tracker-that-happens-to-show-EUR-gains. That is the conversion
  lever the strategy points at.
- Koersa explicitly does not classify, and the UI carries that statement
  every time the estimates render. Less rope to hang us on, less rope to
  hang a user on.
- A precedent is set: tax types live in `Shared\Domain\Tax\`. When the
  `Reporting` context lands, calculations move there and read the same
  enum and VO.
- The 33% rate is hard-coded for now. If it changes (politically possible —
  the rate has been debated several times), we lift it into a config or
  yearly table in a follow-up. A single constant change today is acceptable;
  multi-year historical-rate tables are not worth building before they're
  needed.

## References

- [ADR 0006](0006-tax-engine-foundations.md) — Money, ECB rates, FIFO
  realized gains
- `ARCHITECTURE.md` §2 — bounded contexts (`Reporting` planned)
- `docs/internal/STRATEGY.md` — decision filter
- SPF Finance / FOD Financiën — official guidance on crypto taxation
  (regimes, criteria for classification, SDA rulings)
