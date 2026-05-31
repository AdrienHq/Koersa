# ADR 0005 — MarketData: a minimum-viable price provider

**Date:** 2026-05-31
**Status:** Accepted

## Context

The Portfolio dashboard shows holdings as `quantity` and `avg cost`. Without a
current price, the page can never answer the only question a user actually
opens it to ask: *what is my portfolio worth right now?* That is the single
biggest perception upgrade between "a spreadsheet" and "a product you trust
with your money" — and the strategy's conversion target depends on it.

`MarketData` is one of the seven planned contexts in `ARCHITECTURE.md`. The
full scope (price alerts, async historical sync, multi-currency) is parked per
STRATEGY. But the minimum read — current EUR spot per asset — is small and
high-value, and on the **decision filter** it passes the rule that matters
most: *does this move toward a paying user this month?* Hard yes.

## Decision

- Introduce a `Shared\Market\PriceProvider` interface returning current EUR
  prices for a batch of asset symbols. Portfolio reads through it — same
  cross-context pattern as `Shared\Security\HasOrganization`.
- Implement it in a new `MarketData` context with `CoinGeckoPriceProvider`
  (CoinGecko's free spot-price endpoint, no API key needed).
- Per-asset cache, 5-minute TTL (Symfony's `cache.app`; filesystem in dev,
  trivially swappable to Redis later).
- Asset → CoinGecko id mapping lives in the adapter as a small constant. Add
  symbols as we see them.
- Failures are non-blocking: an HTTP error or an unknown symbol just renders as
  "—" in the UI. The portfolio always loads.

## Out of scope (this slice)

- Price alerts — explicitly parked by STRATEGY.
- Historical prices, on-write fetching, scheduled jobs.
- Currencies other than EUR.
- Fallback providers, rate-limit retries beyond a single try/catch.
- Real PnL (needs cost-basis EUR conversion, which needs the `Money` value
  object and historical FX — that's the tax engine's prerequisite).

## Consequences

- A new external HTTP dependency (CoinGecko). Acceptable: free, well-known,
  rate-limited gracefully via the cache.
- The dashboard finally shows a real EUR number, which is what makes it stop
  looking like a glorified spreadsheet.
- `Money` is still owed before tax math is honest; this slice does not need it.

## References

- `ARCHITECTURE.md` §2 — bounded contexts (MarketData is one of the seven).
- `docs/internal/STRATEGY.md` — the decision filter.
