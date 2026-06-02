# Changelog

All notable changes to this project are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning: [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial project scaffolding
- Architecture blueprint (`ARCHITECTURE.md`)
- ADR 0001: Event Sourcing scoped to the Portfolio context
- Pull request template
- Symfony 7.4 application skeleton on PHP 8.4 under the `Koersa\` namespace
- Bounded-context source layout (`Domain`/`Application`/`Infrastructure`/`UI`)
- Docker Compose stack: PHP-FPM, Nginx, PostgreSQL 16, Redis 7, Mailpit
- Quality tooling: PHPStan (level 9), Deptrac (context and layer boundaries), PHP-CS-Fixer, Rector
- Makefile with stack and quality-gate targets
- GitHub Actions CI running the quality gates on every push and pull request
- ADR 0003: persistence and mapping strategy for Doctrine-backed contexts
- IAM domain model — `User`, `Organization`, `Membership` with `Email`/`Role`/`Uuid` value objects and repository ports
- IAM Doctrine persistence (an entity and a mapper per aggregate) and the database migration for the `iam_*` tables
- Security user provider backed by the IAM user repository
- Account registration and password sign-in
- Tailwind-based UI foundation (no Node build, via AssetMapper)
- Portfolio: record buy/sell transactions and a per-asset holdings dashboard
- Portfolio: edit and remove recorded transactions
- ADR 0002: Event Sourcing library (EventSauce) and Portfolio integration
- Portfolio event store with optimistic concurrency, synchronous projectors, and a `portfolio:projections:rebuild` console command
- Bilingual (French/Dutch) public landing page with a beta-access signup form
- Internationalisation foundation (FR/NL message catalogs, locale-prefixed routing)
- English UI alongside French and Dutch, with a footer toggle that remembers the visitor's choice for the rest of their session
- Realized gains in EUR on the dashboard (year-to-date), computed FIFO from the trade history with each leg converted at the ECB reference rate of the trade date
- Per-asset realized-gains table with cost basis, proceeds, and net result
- `Shared\Domain\Money` value object (decimal amount + ISO currency, bcmath arithmetic, no float exposure)
- Historical EUR FX rates via the European Central Bank (`Shared\Market\FxRateProvider` + ECB adapter; cached daily)
- Transaction events now carry the price and fee currency; the Kraken parser reads it from the pair (e.g. `XBT/USD` is settled in USD), and legacy events default to EUR
- Belgian tax regime estimates on the dashboard: three scenarios (normal management 0%, speculative 33%, professional progressive) shown side by side with a persistent reminder that Koersa shows scenarios, not advice
- Signed-in app split into three tabs (Overview / Portfolio / Tax); `/` now lands authenticated users on the Overview
- Charts on the Overview tab: cumulative realized gain over time, current holdings composition, and last-12-months buy/sell activity (Chart.js via importmap, no Node build)
- "How to declare" guidance on the Tax tab: per regime, the Belgian Cadre / Vak and codes (1440-1441 / 2440-2441 for the speculative case) plus the amount to enter, with a verify-with-SPF disclaimer
- One-page accountant-ready PDF tax report (Dompdf, pure PHP, no binary) — summary, per-asset gains, regime scenarios, filing guidance and methodology, in the user's current language; downloadable from the Tax tab
- Binance Spot Trade History CSV import; built against the documented legacy format, pending real-data validation
- Two role levels: platform-wide `ROLE_ADMIN` (flag on User, granted via `iam:user:promote-admin` console command) and per-organisation `ROLE_ORG_ADMIN` (existing `Role::Admin` enum value on Membership), with a Symfony role hierarchy that cascades
- Operator-only admin landing at `/admin` showing recent signups, users and organisations with counts; nav link visible only to platform admins
- Each new registration is auto-seeded with a realistic 18-trade demo portfolio so first-time users land in a populated dashboard — edit, delete or add your own trades anytime; demo and your data live in the same account (ADR 0012)
- Dismissible "you're starting with sample data" hint on the Overview tab, cookie-dismissed so it never reappears
- SEO metadata on the landing (description, Open Graph, hreflang) and an XML sitemap
- Baseline security response headers and a `security.txt` disclosure contact
- Health-check endpoint at `/health`

### Changed
- Portfolio is now event-sourced with EventSauce: trades are recorded, amended, and removed as events through a command bus, and the holdings/transactions read models are rebuildable projections over the event stream (ADR 0002)

### Fixed
- Transaction quantities, prices, and fees no longer render with trailing zeros from the NUMERIC column
