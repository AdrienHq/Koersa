# ADR 0003 — Persistence and mapping strategy for Doctrine-backed contexts

**Date:** 2026-05-24
**Status:** Accepted

> ADR 0002 is reserved for the Event Sourcing library decision (Iteration 2),
> as noted in ADR 0001. This ADR was written first because the persistence
> approach for the non-event-sourced contexts is needed in Iteration 1.

## Context

Every context except `Portfolio` persists with Doctrine (ADR 0001). A project
rule — enforced in CI by Deptrac — forbids `Symfony`, `Doctrine`, and
`ApiPlatform` imports under any `Domain/` directory. Doctrine attribute mapping
places an `#[ORM\Entity]` attribute, and therefore a `Doctrine\` import, on the
mapped class. That makes "attribute-mapped class as the domain model" incompatible
with the rule.

Four approaches were considered:

1. **Pure domain models mapped with Doctrine XML.** Models stay free of
   attributes; mapping lives in XML files in the infrastructure layer.
2. **Separate persistence entity plus a mapper.** A pure domain model in the
   domain layer and an attribute-mapped Doctrine entity in the infrastructure
   layer, with a mapper translating between the two.
3. **Attribute-mapped entity as the model, relocated to infrastructure.** The
   Doctrine entity is the model; the domain layer holds only value objects.
4. **Relax the rule.** Allow Doctrine attributes inside `Domain/`.

## Decision

Option 2 is adopted for all Doctrine-backed contexts (`IAM`, `MarketData`,
`Integration`, `Billing`, `Reporting`):

- The **domain model** is a plain PHP class in `…/Domain/` with behaviour and no
  framework imports.
- A **Doctrine entity** in `…/Infrastructure/Persistence/Doctrine/Entity/` carries
  the `#[ORM\*]` attributes and mirrors the persisted shape.
- A **mapper** in the same infrastructure namespace converts entity ↔ model.
- A **repository** implements a domain-owned port interface and uses the mapper.

Aggregates expose two constructors: `register()` / `create()` for new instances
and `reconstitute()` for rebuilding from storage.

## Rationale

- XML mapping (option 1) was rejected: it is a niche, dated skill the team does
  not use, and offers no advantage over attributes here beyond keeping a single
  model — which option 2 also achieves conceptually while staying on attributes.
- Option 3 produces a thin, near-anemic domain layer, which the project
  conventions explicitly discourage, and blurs the persistence/domain boundary
  that this project exists to practise.
- Option 4 would remove a guardrail the project deliberately put in place, and is
  not warranted by any real friction.
- Option 2 keeps the domain framework-free and verifiable without a database,
  uses only attribute mapping (familiar, terse), and makes the persistence
  boundary an explicit, testable seam. The mapper is the single place coupling
  the two representations.

## Consequences

### Positive

- The domain layer is unit-testable with no database and no Doctrine.
- Persistence can change (column types, table layout, even the store) without
  touching the domain model.
- The Deptrac domain-purity rule holds with no exceptions.

### Negative

- More files per aggregate: a model, an entity, and a mapper. Accepted as the
  cost of the separation; mitigated by keeping aggregates small and the mappers
  covered by tests.
- Value objects are converted by the mapper (e.g. `Email` ↔ `string`) rather than
  by Doctrine embeddables or custom types, keeping Doctrine out of the domain.
- A risk of model/entity drift, mitigated by mapper round-trip tests.

## References

- Matthias Noback, *Advanced Web Application Architecture*, 2022
- Vaughn Vernon, *Implementing Domain-Driven Design*, Chapter 12 (Repositories)
- Sylius resource model and mapping conventions
