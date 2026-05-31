# ADR 0008 — Tax-on-web / MyMinfin filing guidance

**Date:** 2026-05-31
**Status:** Accepted

## Context

[ADR 0007](0007-belgian-tax-regime-estimates.md) gives the user three regime
scenarios with a potential tax amount. They still need to know **where on the
declaration to put the number**: which *Cadre / Vak*, which code. That's the
last manual step between "Koersa told me the number" and "Koersa saved me
time at filing season."

The Belgian personal income tax declaration is filed via Tax-on-web (FR) /
Tax-on-web (NL) on MyMinfin. The form is divided into *Cadres / Vakken*, each
containing numbered codes. The codes shift year to year — modestly, but
enough that hard-coding them once and forgetting is wrong.

For crypto gains classified under the **speculative regime** (Art. 90, 1°
CIR/WIB), the codes have been stable for several years: **Cadre XV / Vak XV,
section 1.A "Bénéfices et profits occasionnels"**, codes **1440 / 2440**
(taxable amount, taxpayer 1 / taxpayer 2) and **1441 / 2441** (deductible
costs). The user enters the *gain* — the tax authority applies the 33%.

For the **professional regime** (Art. 23 CIR/WIB), the right Cadre depends on
the taxpayer's status (employee with a side activity, self-employed under
"bénéfices" or under "profits", company structure, etc.) and on what they
declare for social-security purposes. The mapping is genuinely case-by-case
and we don't have the inputs to model it.

For **normal management**, capital gains on private crypto holdings are not
taxable — there's no code to put them in.

## Decision

### A `FilingGuidance` per regime, mapped per income year

- `Shared\Domain\Tax\FilingGuidance` — value object: regime, optional box
  label (translation key), optional code label (translation key), optional
  amount (`Money`), required note (translation key), income year.
- `Shared\Application\Tax\BelgianBoxMapper::guide(Regime, Money, int $incomeYear)`
  returns a `FilingGuidance`. Pure function, no I/O.

### What goes in the amount field

For **speculative**, the amount in code 1440/2440 is the *taxable gain*, not
the 33% tax. The tax computation runs on the SPF side. Koersa shows the gain
amount alongside the regime estimate (which is what *would* be owed) — both
are useful at filing time.

### Per-income-year branching

The mapper takes an `int $incomeYear` (e.g. 2024, declared in tax year 2025).
For income years we don't have verified codes for, the mapper returns a
"no guidance for this year" `FilingGuidance` with a note pointing to SPF.

The dashboard always asks for the year that matches the realized-gains report
year (`GetRealizedGains->forMostRecentYear()` — the year a sell actually
happened). Today, we ship guidance for **income year 2024 (filed in 2025)**
based on the 2024 form codes. When the 2025 form publishes, we add a branch.

### Persistent disclaimer

Every "How to declare" rendering carries: *"Codes can change year to year.
Verify on SPF Finance / FOD Financiën documentation before submitting."*
This is the honest framing — we shorten the work, the user still confirms.

### Professional regime intentionally underspecified

The "professional" row in the How-to-declare table says *"Complex; depends on
your status (employee with a side activity, self-employed, company...) and
on the activity classification. Ask your accountant; pre-fill via Box XVII
‘bénéfices et profits’ is the usual starting point."* No specific codes.
Refusing here is honest and matches [ADR 0007]'s general posture.

### No auto-filling

Tax-on-web does have an API for fully-electronic submission, but driving it
requires identity providers (itsme / eID), per-user secrets, and accountancy
liability we don't take on in beta. Koersa stays at "tell the user the
codes; they transcribe."

## Out of scope (this slice)

- Auto-filing to Tax-on-web / MyMinfin via their API.
- Joint declaration nuance beyond surfacing the spouse-2 codes (2440, 2441).
- Box XVII detailed mapping for the professional regime.
- Income years before 2024.
- VAT, regional tax, communal surcharges (already out per ADR 0007).
- Multi-jurisdiction (Wallonia / Flanders / Brussels) variation, if any
  applies to these codes — none currently does for personal income tax, but
  if a future code splits regionally, the mapper grows a region parameter.

## Consequences

- Codes are committed for income year 2024. Verifying SPF/FOD before
  pushing live is a launch-checklist item ([PRE_LAUNCH.md](../internal/PRE_LAUNCH.md)).
- The mapper is a single switch with three branches; adding income year
  2025 is a one-file change.
- A user with a sell in 2024 now has the complete "what is it and what do
  I do with it" picture: realized gain in EUR → potential tax per regime
  → exact code on the form. That's where Koersa stops feeling like a
  portfolio tracker and starts feeling like a Belgian filing tool.

## References

- [ADR 0006](0006-tax-engine-foundations.md) — Money, ECB rates, FIFO
- [ADR 0007](0007-belgian-tax-regime-estimates.md) — regime estimates
- SPF Finance / FOD Financiën official declaration form and code tables
  (per income year, published spring of the following year)
- Art. 90, 1° CIR / WIB — speculative occasional gains
- Art. 23 CIR / WIB — professional income
