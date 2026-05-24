# Architecture

This document describes the architecture of Koersa: bounded contexts, persistence strategy, the core domain model, and the iterative delivery plan.

For specific design decisions and their rationale, see [`docs/adr/`](docs/adr/).

---

## 1. Architectural decisions

### 1.1 Event Sourcing scope

Event Sourcing applies to the `Portfolio` context only. All other contexts use standard Doctrine persistence.

The Portfolio domain has properties that make Event Sourcing genuinely valuable:

- Transactions are immutable real-world events. Recording them as events matches the reality of the domain.
- Cost-basis methods (FIFO, LIFO, weighted average) produce different P&L outputs from the same trade history. Modeling them as projections over an event log makes switching methods a rebuild rather than a destructive migration.
- Belgian tax rules evolve and are sometimes applied retroactively. Replay over historical events is a requirement, not an optimization.
- The Belgian tax authority can request justification long after the fact. The event log is the audit trail.

Other contexts (users, organizations, billing, exchange credentials, market prices) do not benefit from temporal queries or replay, and Event Sourcing in those areas would add complexity without return. See [ADR 0001](docs/adr/0001-event-sourcing-scope.md) for the full rationale.

### 1.2 User interface

The UI is server-rendered with Twig, enhanced by Stimulus, Turbo, and Live Components. REST and GraphQL endpoints are exposed in parallel through API Platform with OpenAPI documentation, available for third-party integrations and a potential future mobile client.

### 1.3 Multi-tenancy

Tenancy uses a single shared database. Every tenant-scoped table carries an `organization_id` column. A Doctrine SQL filter applies the scoping globally on every query, with explicit opt-out for cross-tenant administrative operations. A security voter governs whether a given user may act on a given organization.

---

## 2. Bounded contexts

| Context        | Persistence              | Responsibilities                                  |
|----------------|--------------------------|---------------------------------------------------|
| **IAM**        | Doctrine                 | Users, Organizations, Memberships, JWT, RBAC      |
| **Portfolio**  | Event store + projections| Transactions, Holdings, P&L                       |
| **MarketData** | Doctrine + Redis         | Asset prices, price alerts                        |
| **Integration**| Doctrine                 | Exchange and wallet credentials, sync cursors     |
| **Billing**    | Doctrine                 | Stripe customers, subscriptions, webhooks         |
| **Reporting**  | Doctrine + filesystem    | Asynchronous PDF tax reports                      |
| **Shared**     | —                        | Kernel value objects (`Uuid`, `Money`, `AssetSymbol`) |

Each context lives in `src/<Context>/` and follows a layered structure: `Domain/`, `Application/`, `Infrastructure/`, `UI/`. Cross-context calls go through application services or domain events; direct entity references across contexts are prohibited and enforced by Deptrac in CI.

---

## 3. The Portfolio aggregate

The aggregate root is `Portfolio`, with one instance per `Organization`.

### Commands

- `RecordTransaction`
- `AmendTransaction`
- `RemoveTransaction`
- `ImportTransactionsFromExchange`
- `RecalculateCostBasis`

### Domain events

- `TransactionRecorded { txId, asset, side, quantity, price, fee, occurredAt, source }`
- `TransactionAmended { txId, before, after, reason, amendedBy }`
- `TransactionRemoved { txId, reason }`
- `ExchangeSyncCompleted { exchange, fromTs, toTs, txCount }`
- `CostBasisMethodChanged { from, to }`

### Read projections

All projections are rebuildable from the event log:

- `holdings_projection` — current quantity and average cost per asset
- `pnl_history_projection` — realized profit and loss per period
- `taxable_events_projection` — events flagged under Belgian speculation tax rules
- `audit_trail_projection` — full event log per organization

### Replay scenarios

- Switching cost-basis method (FIFO ↔ LIFO ↔ weighted average) rebuilds the holdings and P&L projections from existing events.
- A change in Belgian tax interpretation triggers a rebuild of the taxable events projection with the new rule version.
- New analytical questions are answered by writing new projections that consume the existing event stream.

---

## 4. Technology choices

### Runtime

```
php             >= 8.4
symfony         ^7.4
api-platform    ^4.0
postgresql      16
redis           7
```

### Composer dependencies (production)

```
api-platform/core
lexik/jwt-authentication-bundle
doctrine/doctrine-bundle
doctrine/doctrine-migrations-bundle
eventsauce/eventsauce
symfony/messenger
symfony/ux-turbo
symfony/ux-live-component
symfony/stimulus-bundle
stripe/stripe-php
knplabs/knp-snappy-bundle
guzzlehttp/guzzle
predis/predis
```

### Composer dependencies (development)

```
symfony/maker-bundle
phpstan/phpstan
phpstan/phpstan-symfony
phpstan/phpstan-doctrine
rector/rector
phpunit/phpunit
symfony/test-pack
deptrac/deptrac
friendsofphp/php-cs-fixer
```

### Exchange API clients

Exchange integrations are implemented as thin native PHP clients under `src/Integration/<Exchange>/`. Generic libraries such as `ccxt` are not used: each exchange surface area is small enough that a dedicated client is more maintainable and produces clearer error handling.

---

## 5. Folder structure

```
src/
  IAM/
    Domain/
    Application/
    Infrastructure/
    UI/
  Portfolio/
    Domain/
      Portfolio.php
      Event/
      Command/
      ValueObject/
    Application/
      Handler/
      Projector/
      Query/
    Infrastructure/
      EventStore/
      Projection/
    UI/
  MarketData/
  Integration/
    Kraken/
    Binance/
    Web3/
  Billing/
  Reporting/
  Shared/
    Domain/
      Uuid.php
      Money.php
      AssetSymbol.php
```

---

## 6. Delivery plan

The product is built in five iterations, each ending in a deployable state.

### Iteration 1 — Foundational skeleton

- User, Organization, and Membership entities (Doctrine)
- JWT authentication with Twig-based login and registration
- Manual `Transaction` CRUD as a plain Doctrine entity
- Dashboard listing transactions with a basic holdings computation
- Docker Compose setup: PHP-FPM, Nginx, PostgreSQL, Redis, Mailpit
- CI pipeline running PHPStan and PHPUnit on every pull request

**Exit criterion:** a user can register, sign in, record a transaction, and see resulting holdings.

### Iteration 2 — Event Sourcing on Portfolio

- EventSauce integration
- `Portfolio` aggregate with the `RecordTransaction` command
- Event store table and projection tables for holdings and P&L
- CLI command for rebuilding projections from the event log
- ADR documenting the migration from CRUD to event-sourced persistence

**Exit criterion:** dropping all projection tables and rebuilding them produces the same dashboard output as before the rebuild.

### Iteration 3 — Asynchronous exchange synchronization

- Symfony Messenger with Doctrine transport (Redis transport added later)
- Synchronization workers for Kraken and Binance
- Idempotent imports keyed on exchange-side transaction IDs
- Retry policy with dead-letter queue
- CLI commands for manual triggering and inspection

**Exit criterion:** running `bin/console portfolio:sync kraken` ingests new trades and they appear in projections.

### Iteration 4 — Multi-tenancy and billing

- Doctrine SQL filter scoping every tenant-bound query by `organization_id`
- Security voter for organization-level authorization
- Organization switcher in the UI
- Stripe Checkout and Customer Portal integration
- Subscription plans (Free, Pro, Premium) with feature gates
- Webhook handler translating Stripe events into domain events

**Exit criterion:** two users in two organizations cannot observe each other's data, and Stripe billing works end-to-end against test mode.

### Iteration 5 — Belgian tax engine and PDF reports

- Cost-basis strategy interface (FIFO default, with LIFO and weighted average as alternatives)
- `BelgianSpeculationTaxRule` as a pluggable, fully tested strategy
- `TaxYearReport` query consolidating taxable events for a given fiscal year
- Asynchronous PDF generation via Messenger
- Email delivery of generated reports

**Exit criterion:** generating a tax report for a fiscal year produces a PDF whose figures match an independently computed reference.

---

## 7. Cross-cutting concerns

- **Observability** — structured JSON logs via Monolog, health endpoint with dependency checks, OpenTelemetry traces planned for iteration 3 onwards.
- **Quality gates** — PHPStan level 9, Deptrac for bounded-context boundaries, PHP-CS-Fixer for style, Rector for syntax modernization. All run in CI on every pull request.
- **Architecture decisions** — recorded as ADRs in `docs/adr/` using the Michael Nygard template.
- **Migration safety** — Doctrine migrations are reversible by default; destructive operations require explicit review.