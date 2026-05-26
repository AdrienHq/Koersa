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

### Changed
- Portfolio is now event-sourced with EventSauce: trades are recorded, amended, and removed as events through a command bus, and the holdings/transactions read models are rebuildable projections over the event stream (ADR 0002)

### Fixed
- Transaction quantities, prices, and fees no longer render with trailing zeros from the NUMERIC column
