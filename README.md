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

## Today

What actually ships right now — the rest is on the roadmap.

- Public landing page in French, Dutch, and English with beta signup; the rest of the UI follows the same three languages, switchable from the footer and remembered for the session
- Account registration and password sign-in
- Three-tab signed-in app: **Overview** (digestible recap with cumulative-gain, holdings and activity charts), **Portfolio** (operational view: holdings + transactions + import/record/edit modals), **Tax** (realized gains + regime estimates)
- Record, amend, and remove portfolio transactions
- Per-asset holdings dashboard with live EUR prices (CoinGecko, cached 5 minutes)
- **Realized gains in EUR**, computed FIFO over the trade history, with every leg converted at the ECB reference rate of the trade date
- **Potential tax owed under each Belgian regime** (normal management / speculative / professional) shown side by side — Koersa never picks one for you
- **How-to-declare guidance per regime**: which Cadre / Vak and code numbers go on the Belgian tax-on-web declaration, with the amount pre-computed
- **Accountant-ready PDF report** — one page covering realized gains per asset, regime scenarios, filing guidance and methodology, in the user's language
- Kraken CSV import — drop the export (CSV or the ZIP Kraken gives you); buy/sell trades are recorded, re-imports are idempotent, and the quote currency is read from the pair
- Binance Spot Trade History CSV import — built from the documented format; first real export will validate any post-2023 layout drift
- Two-tier role model: platform admin (operator) and per-organisation admin, with a read-only operator landing at `/admin`
- Event-sourced Portfolio context (EventSauce) with rebuildable projections and a `portfolio:projections:rebuild` command

## Architecture

- **Bounded contexts**: `IAM`, `Portfolio`, `MarketData`, `Integration`, `Billing`, `Reporting`, `Shared`. Boundaries (between contexts and between layers) are enforced by Deptrac in CI.
- **Event Sourcing** on the `Portfolio` aggregate — transactions are recorded as immutable events; the holdings and transactions read models are projections rebuilt from the event log.
- **CQRS** through Symfony Messenger — writes go through a command bus wrapped in `doctrine_transaction` so the event append and its projection commit together. Projectors are synchronous today; async workers come later.
- **Server-rendered UI** with Twig, Stimulus, and Turbo (Tailwind via AssetMapper, no Node build).
- **API Platform 4** and **multi-tenancy enforcement** are planned (see roadmap).

Full architecture details in [`ARCHITECTURE.md`](ARCHITECTURE.md). Design decisions are recorded as ADRs in [`docs/adr/`](docs/adr/).

## Roadmap

- [x] **Iteration 1** — Skeleton: authentication, organizations, manual transactions, basic dashboard
- [x] **Iteration 2** — Event Sourcing on the Portfolio aggregate with projections and replay
- [ ] **Iteration 3** — Asynchronous Kraken and Binance synchronization via Messenger (Kraken + Binance CSV import shipped; async API sync deferred until paying users)
- [ ] **Iteration 4** — Multi-tenancy enforcement and Stripe billing
- [x] **Iteration 5** — Belgian tax engine with PDF report generation: Money VO, ECB historical FX, FIFO realized gains, regime-aware tax estimates, Tax-on-web box mapping, accountant-ready PDF report

## Running locally

```bash
git clone https://github.com/AdrienHq/Koersa.git
cd Koersa
make setup        # build + start the Docker stack, install dependencies, migrate
make qa           # quality gates: PHP-CS-Fixer, PHPStan, Deptrac, PHPUnit
```

The app is served at **http://localhost:8080**, Mailpit at **http://localhost:8025**.
Run `make` (or `make help`) to list every target.

See **[`docs/development.md`](docs/development.md)** for the full guide — what each
service is, the everyday commands, and troubleshooting (including the Fedora
SELinux note).

## Tech stack

| Layer            | Choice                                        |
|------------------|-----------------------------------------------|
| Language         | PHP 8.4                                       |
| Framework        | Symfony 7.4                                   |
| Authentication   | Symfony Security (form login)                 |
| Database         | PostgreSQL 16                                 |
| Cache / queues   | Redis 7                                       |
| Event store      | EventSauce                                    |
| Messaging        | Symfony Messenger                             |
| Frontend         | Twig + Stimulus + Turbo, Tailwind v4 via AssetMapper |
| Static analysis  | PHPStan level 9, Deptrac, Rector              |
| CI               | GitHub Actions, Codecov                       |

## Contributing

Contributions are welcome. Open an issue to discuss substantive changes before submitting a pull request. See [`CHANGELOG.md`](CHANGELOG.md) for release history.

## License

Released under the MIT License — see [`LICENSE`](LICENSE).