# Koersa

> Crypto portfolio manager with Belgian tax reporting.

[![CI](https://github.com/AdrienHq/Koersa/actions/workflows/ci.yml/badge.svg)](https://github.com/AdrienHq/Koersa/actions)
[![codecov](https://codecov.io/gh/AdrienHq/Koersa/branch/main/graph/badge.svg)](https://codecov.io/gh/AdrienHq/Koersa)
[![PHP Version](https://img.shields.io/badge/php-8.4-blue)](https://php.net)
[![Symfony](https://img.shields.io/badge/symfony-7.4-black)](https://symfony.com)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen)](phpstan.dist.neon)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

**Status:** 🚧 In active development — see [roadmap](#roadmap) for current progress.

🔗 **Demo:** _coming soon_

---

## What it does

Koersa tracks crypto holdings across multiple exchanges and self-custodied wallets, replays trade history to compute realized and unrealized P&L under different cost-basis methods (FIFO, LIFO, weighted average), and generates tax reports adapted to Belgian rules — including the 33% speculation tax on short-term gains and the "bon père de famille" framework for long-term holdings.

The product targets traders who want auditable records, accurate P&L, and tax reports they can hand to an accountant without spreadsheets.

## Key capabilities

- Portfolio tracking across multiple exchanges and wallets
- Historical P&L computation, replayable when cost-basis method or tax rules change
- Configurable price alerts
- Belgian tax reports generated as PDF, with full audit trail
- Multi-tenant SaaS architecture with role-based access control

## Architecture

- **Symfony 7** with **API Platform 4** exposing REST and GraphQL with OpenAPI documentation at `/api/docs`
- **Event Sourcing** on the `Portfolio` aggregate — transactions are recorded as immutable events; holdings and P&L are projections rebuildable from the event log
- **CQRS** through Symfony Messenger — commands write to the event store, queries read from projections
- **Multi-tenant** with single-database isolation: every tenant-scoped table carries `organization_id`, enforced globally by a Doctrine SQL filter
- **Server-rendered UI** with Twig, Stimulus, Turbo, and Live Components
- **Asynchronous processing** via Messenger workers for exchange synchronization, alerts, and PDF generation
- **Bounded contexts** organize the codebase into `IAM`, `Portfolio`, `MarketData`, `Integration`, `Billing`, `Reporting`, and `Shared`. Boundaries are enforced by Deptrac in CI.

Full architecture details in [`ARCHITECTURE.md`](ARCHITECTURE.md). Design decisions are recorded as ADRs in [`docs/adr/`](docs/adr/).

## Roadmap

- [ ] **Iteration 1** — Skeleton: authentication, organizations, manual transactions, basic dashboard
- [ ] **Iteration 2** — Event Sourcing on the Portfolio aggregate with projections and replay
- [ ] **Iteration 3** — Asynchronous Kraken and Binance synchronization via Messenger
- [ ] **Iteration 4** — Multi-tenancy enforcement and Stripe billing
- [ ] **Iteration 5** — Belgian tax engine with PDF report generation

## Running locally

```bash
git clone https://github.com/AdrienHq/Koersa.git
cd Koersa
make up           # docker compose up + composer install + migrations + seed
make test         # PHPUnit + PHPStan + Deptrac
```

Application available at http://localhost:8000.

## Tech stack

| Layer            | Choice                                        |
|------------------|-----------------------------------------------|
| Language         | PHP 8.4                                       |
| Framework        | Symfony 7.4                                   |
| API              | API Platform 4 (REST + GraphQL)               |
| Authentication   | LexikJWT + Symfony Security                   |
| Database         | PostgreSQL 16                                 |
| Cache / queues   | Redis 7                                       |
| Event store      | EventSauce                                    |
| Async            | Symfony Messenger                             |
| Frontend         | Twig + Stimulus + Turbo + Live Components     |
| PDF              | KnpSnappyBundle (wkhtmltopdf)                 |
| Payments         | Stripe                                        |
| Static analysis  | PHPStan level 9, Deptrac, Rector              |
| CI               | GitHub Actions                                |

## Contributing

Contributions are welcome. Open an issue to discuss substantive changes before submitting a pull request. See [`CHANGELOG.md`](CHANGELOG.md) for release history.

## License

Released under the MIT License — see [`LICENSE`](LICENSE).