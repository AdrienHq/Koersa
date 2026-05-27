# ADR 0004 — CSV import of exchange statements

**Date:** 2026-05-27
**Status:** Accepted

## Context

Sprint 2 turns Kraken and Binance CSV exports into portfolio transactions — the
first slice of real value. `ARCHITECTURE.md` assigns exchange ingestion to an
`Integration` context, yet also lists `ImportTransactionsFromExchange` as a
`Portfolio` command, and Deptrac forbids one context referencing another's
classes directly (cross-context handoff needs a Shared port). So there is a
fast, single-context path and a purist two-context path.

## Decision

- **The CSV import lives in `Portfolio` for now.** Per-exchange parsers sit in
  `Portfolio/Infrastructure`, an upload form in `Portfolio/UI`, and an
  `ImportTransactions` command records the parsed rows on the aggregate. No new
  bounded context. When asynchronous exchange-API sync arrives (deferred until
  there are paying users), connectors move into the `Integration` context behind
  a Shared port, and the CSV parsers can move with them.
- **`TransactionRecorded` gains `source` and `externalId`.** `source` is
  `manual | kraken | binance`; `externalId` is the exchange's row id (null for
  manual entries). This is the provenance the event in `ARCHITECTURE.md` already
  anticipated. Both are creation-time and immutable, so only the record event
  carries them; amendments preserve them in the projection.
- **Idempotency by external reference.** The aggregate tracks the set of
  imported `(source, externalId)` references; recording an already-seen
  reference emits no event. Re-uploading the same export adds only new rows.
- **Trades only.** Parsers handle buy/sell rows. Deposits, withdrawals,
  staking, and transfers are skipped — they have no defined Belgian cost-basis
  treatment yet (see the deferred tax engine; do not invent rules).
- **Real exports drive the mapping.** The column mapping is built against actual
  Kraken/Binance exports. Small, anonymized fixtures are committed for the
  parser tests; the real exports are never committed.

## Consequences

- An event-schema change; dev data is reset, so there is no backfill.
- `Portfolio` temporarily owns exchange-format knowledge. This is revisited when
  the `Integration` context and API sync land.
- The dashboard can display provenance. Tax classification of imported trades is
  out of scope until an accountant verifies the rules.

## References

- [ADR 0001](0001-event-sourcing-scope.md), [ADR 0002](0002-event-sourcing-library.md)
- `ARCHITECTURE.md` §2–3 (bounded contexts, the Portfolio aggregate)
