# ADR 0002 — Event Sourcing library and Portfolio integration

**Date:** 2026-05-25
**Status:** Accepted

## Context

ADR 0001 scoped Event Sourcing to the `Portfolio` context. Iteration 1 shipped
`Portfolio` as a plain Doctrine `Transaction` aggregate (a deliberate stepping
stone). Iteration 2 turns it into an event-sourced aggregate. Two decisions:
which library, and how it sits next to the rest of the (Doctrine) system.

Library candidates:

1. **EventSauce** — framework-agnostic, small, explicit; you wire the store and
   projectors yourself.
2. **`patchlevel/event-sourcing`** — more batteries-included and Symfony-integrated
   (attributes, bundle, subscriptions), but more opinionated.

## Decision

Adopt **EventSauce**, as anticipated in ADR 0001 and listed in `ARCHITECTURE.md`.

- **Event store:** a Doctrine/DBAL-backed, append-only message store table. Events
  are the source of truth for the Portfolio context.
- **Aggregate:** `Portfolio` (one per organization) becomes the event-sourced root.
  `RecordTransaction` (later `AmendTransaction`, `RemoveTransaction`) appends domain
  events such as `TransactionRecorded`, rather than writing a row.
- **Command bus:** writes go through a Symfony Messenger command bus with the
  `doctrine_transaction` middleware, so appending events + dispatching is atomic.
  This also retires the transaction-boundary shortcut noted in the IAM registration
  handler.
- **Projections:** read models — `holdings` and a `transactions` list — are built by
  **synchronous projectors** consuming the events. The dashboard reads projection
  tables, not a live computation.
- **Rebuild:** `bin/console portfolio:projections:rebuild` truncates the projection
  tables and replays the event store. Exit criterion for the iteration: dropping
  and rebuilding the projections reproduces the same dashboard.
- **Migration of Iteration-1 data:** the dev data is disposable, so no backfill —
  the `portfolio_transactions` CRUD table is dropped and replaced by the event store
  plus a rebuildable `transactions` projection.

## Rationale

- EventSauce keeps the moving parts explicit, which suits a project whose goal is to
  *learn* Event Sourcing rather than hide it behind a framework. `patchlevel` would
  do more for us but obscure the mechanics.
- Synchronous projectors keep this iteration tractable; asynchronous projection
  (Messenger workers) belongs to Iteration 3.
- A Doctrine message store reuses the existing database and migration tooling.
- Floating-point holdings (Iteration 1, display-only) can move to exact decimal math
  inside the projector when the tax engine needs it (Iteration 5).

## Consequences

- Two persistence styles coexist — accepted in ADR 0001 and physically contained in
  `Portfolio/Infrastructure`.
- Querying across Portfolio projections and Doctrine-backed contexts goes through an
  application query service, as today.
- EventSauce has no official Symfony bundle, so wiring (message store, repository,
  projectors) is a modest amount of explicit DI configuration — acceptable, and in
  keeping with the "explicit over magic" rationale above.

## References

- [ADR 0001](0001-event-sourcing-scope.md) — Event Sourcing scoped to Portfolio
- EventSauce documentation — https://eventsauce.io
- Vaughn Vernon, *Implementing Domain-Driven Design*, Ch. 4 & 8
