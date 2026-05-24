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
