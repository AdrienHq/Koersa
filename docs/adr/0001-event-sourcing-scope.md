# ADR 0001 — Event Sourcing scoped to the Portfolio context

**Date:** 2026-05-24
**Status:** Accepted

## Context

Koersa manages crypto transactions, holdings, P&L, and Belgian tax reporting on top of a multi-tenant SaaS skeleton (users, organizations, billing, exchange integrations).

Event Sourcing is a candidate persistence style. The question this ADR answers is *where* it should apply within the system.

Three options were considered:

1. **Event Sourcing across all contexts.** Every aggregate — User, Organization, Subscription, Transaction — persists as a stream of events.
2. **Event Sourcing scoped to the Portfolio context.** Apply Event Sourcing to the trading domain; use Doctrine elsewhere.
3. **No Event Sourcing.** Use Doctrine throughout, with domain events used only as in-process signals.

## Decision

Option 2 is adopted: **Event Sourcing applies only to the `Portfolio` context.**

All other contexts (`IAM`, `MarketData`, `Integration`, `Billing`, `Reporting`) use standard Doctrine entities and repositories.

## Rationale

### Why Event Sourcing fits Portfolio

- **Transactions are immutable real-world events.** A trade that executed on an exchange at a specific timestamp has happened. The system records it; it does not own its truth.
- **Cost-basis methods are projections over the same data.** FIFO, LIFO, and weighted-average produce different P&L outputs from identical trade histories. Treating them as projections makes switching a rebuild operation rather than a destructive migration.
- **Tax rules change retroactively.** Belgian rules for crypto taxation are interpretive and evolve. The ability to replay the full transaction history against a new rule version is a domain requirement, not an optimization.
- **Audit defensibility.** The Belgian tax authority may request justification years after a fiscal year is closed. The event log serves as the audit trail without additional engineering.
- **Amendments without rewriting history.** A user importing a wrong CSV needs the original recorded *and* the correction preserved — both are tax-relevant.

### Why the other contexts do not benefit

- A `User` entity does not have a rich history with replay value. "User changed email" is a fact to track, not a projection to rebuild.
- Billing state is authoritatively owned by Stripe. Duplicating it into an event store adds reconciliation work without benefit.
- Exchange credentials, alert configurations, and market price snapshots have no replay or temporal-query requirement.

### Why some form of Event Sourcing is required

- Retroactive cost-basis changes and tax-rule updates cannot be modeled cleanly with destructive recalculations on CRUD-style tables. Workarounds — snapshot/history tables, soft deletes, manual versioning — reimplement Event Sourcing partially and inconsistently.

## Consequences

### Positive

- Each context uses the persistence style suited to its domain.
- Contributors and maintainers only encounter Event Sourcing when touching the Portfolio context.
- Replay-driven scenarios (cost-basis switching, tax rule updates, audit) are first-class operations.

### Negative

- Two persistence styles coexist in the codebase. Mitigated by the bounded-context layout: Event Sourcing code is physically contained in `src/Portfolio/Infrastructure/EventStore/` and `src/Portfolio/Application/Projector/`.
- Queries that compose Portfolio projections with Doctrine-backed data require an application-layer query service. This is acceptable and explicit.

### Open question

The Event Sourcing library choice (EventSauce versus `patchlevel/event-sourcing`) is left to ADR 0002. The tentative choice is EventSauce based on documentation maturity and stability; this will be revisited if integration friction arises.

## References

- Greg Young, *CQRS Documents*, 2010
- Vaughn Vernon, *Implementing Domain-Driven Design*, Chapter 8
- EventSauce documentation — https://eventsauce.io
- `patchlevel/event-sourcing` — https://patchlevel.github.io/event-sourcing-docs/