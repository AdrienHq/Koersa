# Architecture guide

How the code is organised and where to put things. For *why* the major
decisions were made, see [`ARCHITECTURE.md`](../ARCHITECTURE.md) and the
[ADRs](adr/). This document is the day-to-day map.

## Mental model in one line

The code is split into **bounded contexts** (business areas), and each context
is split into four **layers**. Two ideas, applied consistently.

## Bounded contexts

A bounded context is a self-contained slice of the business with its own model.
A `User` in IAM and a position in Portfolio are unrelated concepts; keeping them
in separate contexts stops one area's complexity from leaking into another.

| Context        | Owns                                              |
|----------------|---------------------------------------------------|
| **IAM**        | Users, organizations, memberships, authentication |
| **Portfolio**  | Transactions, holdings, P&L (event-sourced)       |
| **MarketData** | Asset prices, alerts                              |
| **Integration**| Exchange and wallet sync                          |
| **Billing**    | Stripe customers, subscriptions                   |
| **Reporting**  | Tax reports (PDF)                                 |
| **Shared**     | Cross-cutting value objects (`Uuid`, `Money`)     |

Rule: **contexts never reach into each other's internals.** They communicate
through application services or domain events. This is enforced in CI by
`deptrac.yaml` — a context may only depend on `Shared`.

## The four layers

Every context has the same four layers, each with one responsibility:

| Layer            | What lives here                                              | May depend on            |
|------------------|-------------------------------------------------------------|--------------------------|
| `Domain/`        | Aggregates, value objects, domain events, repository **interfaces**. Pure PHP, the business rules. | nothing (not even Symfony/Doctrine) |
| `Application/`   | Use-case orchestration: commands, command/query handlers, projectors. | Domain                   |
| `Infrastructure/`| The technical "how": Doctrine entities/mappers/repositories, HTTP clients, the security adapter. | Domain, Application, framework |
| `UI/`            | Entry points: controllers (Twig + API Platform), form types. Thin. | Domain, Application, framework |

## The dependency rule

Dependencies only ever point **inward**:

```
        UI ─┐
            ├──► Application ──► Domain   (Domain depends on nothing)
Infrastructure ─┘        ▲
            └────────────┘  (implements the interfaces Domain/Application declare)
```

So the business rules in `Domain/` never import Symfony, Doctrine, or HTTP. The
payoff: the domain is unit-testable with no database, and the storage or
framework can change without touching it. This direction is enforced in CI by
`deptrac.layers.yaml`; if a `Domain/` class ever imports `Doctrine\…`,
`Symfony\…`, or `ApiPlatform\…`, the build fails.

## Worked example — registering a user

One feature, traced top to bottom. (✓ = already exists, ▢ = built with the auth feature.)

```
UI/RegistrationController.php   ▢  receives the HTTP form, dispatches a command
UI/RegistrationFormType.php     ▢  the form definition
        │
        ▼
Application/RegisterUser.php        ▢  a command: the intent + its data
Application/RegisterUserHandler.php ▢  orchestrates: hash password, build the
                                       aggregates, save them via the interfaces
        │
        ▼
Domain/User.php                 ✓  the aggregate + its rules (no framework)
Domain/ValueObject/Email.php    ✓  a validated value object
Domain/UserRepository.php       ✓  an interface (a "port") — what we need, not how
        ▲
        │  implemented by
Infrastructure/Persistence/Doctrine/DoctrineUserRepository.php ✓
Infrastructure/Persistence/Doctrine/Entity/UserEntity.php      ✓  the DB row
Infrastructure/Persistence/Doctrine/UserMapper.php             ✓  entity ↔ model
Infrastructure/Security/SecurityUser.php                       ✓  Symfony adapter
```

The controller never talks to Doctrine. It dispatches a command; the handler
works with domain objects and an interface; Doctrine only appears behind that
interface, in `Infrastructure/`. See [ADR 0003](adr/0003-doctrine-persistence-mapping.md)
for why the model and the entity are separate.

## Where do I put …?

| You're adding…                          | It goes in…                                  |
|-----------------------------------------|----------------------------------------------|
| A business rule or invariant            | the aggregate in `Domain/`                   |
| A typed wrapper (email, money, slug)     | a value object in `Domain/ValueObject/`      |
| "When X happens, do Y" (a use case)     | a command + handler in `Application/`        |
| A database table / query                | a Doctrine entity + repository in `Infrastructure/` |
| A web page or API endpoint              | a controller in `UI/`                        |
| Something used by every context         | `Shared/Domain/`                             |

## How much structure is enough

The layers are worth their cost when a context has real complexity. **Portfolio**
(event sourcing, cost-basis methods, retroactive tax replay) is the clearest
case. Simpler contexts can stay lighter, and **empty layers should not be
created before there is something to put in them** — add a folder when the first
file needs it. The goal is clarity, not ceremony.

## See also

- [`ARCHITECTURE.md`](../ARCHITECTURE.md) — the locked decisions and delivery plan
- [ADR 0001](adr/0001-event-sourcing-scope.md) — why Event Sourcing is limited to Portfolio
- [ADR 0003](adr/0003-doctrine-persistence-mapping.md) — model + entity + mapper
