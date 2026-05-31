# ADR 0006 — Tax engine foundations: Money, ECB rates, FIFO realized gains

**Date:** 2026-05-31
**Status:** Accepted

## Context

The Belgian tax report is what makes Koersa a thing someone pays for; without
it, the product is yet another portfolio tracker. The full report has three
parts — converting every trade to EUR, computing realized gains, and
classifying them under one of the three Belgian regimes (normal management,
speculative, professional). The third part needs an accountant in the loop and
will not be invented here. The first two are mechanical and can ship now.

Three foundations are owed before any of that math is honest:

1. **A `Money` value object.** Today, `price` and `fee` on `TransactionRecorded`
   are decimal strings without a currency, so a `XBTUSD` Kraken trade and a
   `XBTEUR` Kraken trade aggregate as if they were the same currency. That is
   wrong and silently so.
2. **A historical FX source.** Without one, USD/USDT trades can't be converted
   to EUR at the trade date — and the report can't be defended.
3. **A cost-basis method.** Belgian guidance for individual investors is not
   prescriptive on lot selection. We need to pick one, state it, and stick to
   it so the same export always produces the same number.

This is the first slice of the tax engine, not the whole engine.

## Decision

### `Shared\Domain\Money`

- An immutable value object carrying `amount` (decimal string) and `currency`
  (ISO-4217 three-letter code).
- Arithmetic via **bcmath** at a fixed internal scale (18), then formatted back
  to a canonical string on output. Money never goes through `float` in this
  codebase.
- Operations only between matching currencies; cross-currency ops throw. EUR
  conversion is an explicit step (see ECB below), not an implicit cast.
- Lives in `Shared\Domain` because Portfolio, Reporting, and Billing all need
  the same primitive. No Doctrine, no Symfony.

### Historical FX via the ECB

- New port `Shared\Market\FxRateProvider::rateOn(DateTimeImmutable, base, quote)`,
  symmetric to `PriceProvider` from [ADR 0005](0005-marketdata-minimum-scope.md).
- Adapter in `MarketData\Infrastructure\Ecb\` reads the European Central Bank's
  free daily reference rates (no API key). Rates published only on TARGET
  business days; the adapter falls back to the most recent prior publication for
  weekends and holidays.
- Per-day cache via `cache.app` (long-lived: a 2024-01-03 rate never changes).
- Crypto-to-EUR conversion is two hops when needed (BTC priced in USDT →
  USDT/USD treated as 1.00, USD/EUR from the ECB). Stablecoin pegs are a small
  constant in the adapter; if a peg ever breaks we revisit.

The ECB is chosen because it is the rate the Belgian tax authority uses for its
own published guidance. Any number we submit using it is defensible by
construction.

### FIFO realized gains

- Lots are matched **first-in-first-out**, per asset, per organization.
- A "realized gain" is created on every sell: `proceedsEur - costBasisEur`,
  both computed at the trade date via the ECB.
- Sells with no prior open lots are flagged on the query result (rather than
  silently treated as zero-cost-basis), because they almost always mean an
  incomplete import — wallet history before the user started, an exchange
  retired before export. The dashboard surfaces them so the user can fix the
  data, not the math.
- FIFO is deterministic, the same input always produces the same number, it is
  conservative when prices are trending up (which is what a tax authority would
  prefer to see), and it's what most Belgian crypto-accounting guidance
  defaults to when no other method is specified.

### Event evolution

- `TransactionRecorded` and `TransactionAmended` gain `priceCurrency` and
  `feeCurrency` (ISO codes). An upcaster reads legacy events with the missing
  fields as `EUR` — the historical assumption that produced the existing data —
  so we do not need to migrate the event store, only the read-model schema.
- The Kraken parser already knows the pair; it now extracts the quote currency
  from the pair suffix (`XBTEUR` → EUR, `XBTUSD` → USD). The manual entry form
  adds a currency selector defaulting to EUR. Binance gets the same treatment
  when its parser lands.

## Out of scope (this slice)

- **Regime classification** (normal / speculative / professional). The number
  this slice produces is *realized gains in EUR* — what you'd plug into the
  computation, not the computation itself. Regime needs an accountant in the
  loop and lives in its own ADR when that conversation happens.
- **Unrealized P&L.** The dashboard already shows current value via
  [ADR 0005](0005-marketdata-minimum-scope.md); unrealized vs realized split is
  presentation, not domain.
- **Tax-on-web / MyMinfin box mapping and PDF output.** Once the number is
  right, surfacing it in the official form is its own slice.
- **Other cost-basis methods** (LIFO, HIFO, average). FIFO until a user with a
  defensible reason asks for an alternative.
- **Fee allocation across multi-leg trades, wash-sale-style rules, staking
  cost basis.** None of those are settled in Belgian practice; we do not
  invent.
- **Stablecoin de-peg handling.** Treated as 1.00 to USD until it isn't, and
  the day it isn't we have bigger problems than this ADR.

## Consequences

- A new internal primitive (`Money`) that the rest of the domain will start
  depending on. Worth the churn — the current "decimal string + assumed
  currency" pattern is a quiet correctness bug waiting to surface in a tax
  report.
- A second `MarketData` adapter (ECB) joins the CoinGecko one — and the
  context starts to look like a real context. The shape is the same as
  [ADR 0005](0005-marketdata-minimum-scope.md): a Shared port, a context-owned
  adapter, a cache, graceful degradation.
- The dashboard gains two numbers (realized gains YTD, total cost basis) that
  give a Belgian user a *reason to pay* — the conversion lever the strategy
  has been pointing at.
- The Belgian tax authority is the implicit reviewer of every output from this
  slice onward. We document our choices (ECB, FIFO, EUR, trade date) so they
  can be defended on paper without re-derivation.

## References

- [ADR 0001](0001-event-sourcing-scope.md) — Event Sourcing scoped to Portfolio
- [ADR 0002](0002-event-sourcing-library.md) — EventSauce + upcaster pattern
- [ADR 0004](0004-csv-import.md) — `source` / `externalId` evolution prior art
- [ADR 0005](0005-marketdata-minimum-scope.md) — MarketData port/adapter shape
- `ARCHITECTURE.md` §2 — bounded contexts (`Reporting` deferred)
- `docs/internal/STRATEGY.md` — decision filter
