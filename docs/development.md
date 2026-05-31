# Local development

Koersa runs entirely in Docker for local development. This guide covers setup,
the everyday workflow, and the few host-specific gotchas.

## Do I need Docker?

To run Koersa the easy way, yes — but you don't need to *know* Docker. A
container is just a pre-packaged box that already holds the right PHP, PostgreSQL,
Nginx, and so on, so the app runs the same on any machine without installing or
configuring those by hand. You install Docker once, run `make setup`, and never
touch the inside of the boxes.

It isn't strictly required — Koersa is a normal Symfony app and could run on a
machine with PHP 8.4 (plus the right extensions) and PostgreSQL installed
directly — but Docker removes all that setup and version-matching, which is why
it's the recommended path.

## Prerequisites

- **Docker Engine** and the **Compose v2** plugin
- **GNU Make**

Docker Engine is a background service plus command-line tools — there is **no
desktop application to launch** and nothing in your apps menu. (The GUI product
is "Docker Desktop", a separate, heavier install that is not needed here.) The
daemon runs via systemd and you drive it from the terminal.

Install on Fedora:

```bash
sudo dnf install -y moby-engine docker-compose make
sudo systemctl enable --now docker          # start now + on every boot
sudo usermod -aG docker "$USER"              # run docker without sudo
newgrp docker                                # apply the group (or log out/in)
```

Verify:

```bash
docker compose version
make --version
systemctl is-active docker      # -> active
```

## First run

From the repository root:

```bash
make setup
```

This builds the PHP image, starts the stack, installs Composer dependencies, and
runs the database migrations. When it finishes, the app is at
**http://localhost:8080**.

## Local secrets

`APP_SECRET` is not committed. Generate your own and put it in `.env.local`
(gitignored):

```bash
echo "APP_SECRET=$(openssl rand -hex 16)" >> .env.local
```

Tests use a fixed value from `.env.test`, so this is only for the dev env.
Production secrets (Stripe keys, the prod `APP_SECRET`) will live in Symfony's
secrets vault when we deploy — never in `.env*`.

## The stack

| Service    | Role                         | Reachable at                          |
|------------|------------------------------|---------------------------------------|
| `nginx`    | HTTP front end               | http://localhost:8080                 |
| `php`      | PHP-FPM 8.4 (the app)        | internal only                         |
| `database` | PostgreSQL 16                | `localhost:5433` (host)               |
| `redis`    | Redis 7                      | `localhost:6379`                      |
| `mailer`   | Mailpit (catches all email)  | http://localhost:8025 (SMTP `:1025`)  |

All published ports are bound to `127.0.0.1`. The database is published on
**5433**, not 5432, because the host already runs its own PostgreSQL on 5432.

## Everyday commands

Run from the repository root. `make help` lists everything.

```bash
make up                       # build (if needed) + start the stack
make down                     # stop and remove the containers
make logs                     # tail logs from all services
make sh                       # open a shell in the php container
make console c="cache:clear"  # run a Symfony console command
make migrate                  # run database migrations

make qa                       # all gates: PHP-CS-Fixer, PHPStan, Deptrac, PHPUnit
make test                     # PHPUnit
make cs                       # fix coding standards
make stan                     # PHPStan (level 9)
make deptrac                  # bounded-context + layer boundaries
```

Everything runs **inside the container**, so the host only needs Docker, Compose,
and Make.

## How it fits together

- The project directory is **bind-mounted** into the `php` container, so code edits
  take effect immediately — no rebuild needed (rebuild only when the `Dockerfile`
  or PHP extensions change: `make build`).
- Inside the container the app reaches PostgreSQL as `database:5432`. Host-side
  CLI tools (an IDE, `psql`) use `.env.local` / `.env.test.local` (gitignored),
  which point at `localhost:5433`.
- Tests run in the container against a separate `app_test` database (Doctrine
  appends the `_test` suffix automatically).
- The CSS is compiled by Tailwind to `var/tailwind/app.built.css` (gitignored);
  `make` and CI build it. After changing templates, rebuild with
  `make console c="tailwind:build"` (or run `tailwind:start` to watch).

## Docker is a service, not an app

- There is no icon to click. Check its state with `systemctl status docker` or,
  for this project, `docker compose ps` from the repo root.
- The **daemon** auto-starts on boot. The **containers** do not auto-restart after
  a reboot — bring them back with `make up`.

## Fedora / SELinux

The bind mounts carry the SELinux `:z` label (set in `compose.yaml`). Without it,
SELinux denies the containers access to the project files (you would see
`permission denied` on `/app` or on the Nginx config).

## Troubleshooting

- **`permission denied … /var/run/docker.sock`** — your shell isn't in the
  `docker` group yet. Log out and back in, or run `newgrp docker`.
- **`address already in use … 5432`** — something else (likely a host PostgreSQL)
  holds the port. The database is published on 5433 to avoid exactly this.
- **`permission denied` reading `/app` inside a container** — the SELinux `:z`
  label is missing from a bind mount.
- **A code change isn't reflected** — for templates/PHP it should be immediate;
  for CSS run a Tailwind build; for `Dockerfile`/extension changes run `make build`.

## Continuous integration

CI (GitHub Actions, `.github/workflows/ci.yml`) does **not** use this Docker
setup — it provisions its own PostgreSQL service and PHP via `setup-php`, then
runs the same quality gates plus a real-browser Panther E2E. Panther is CI-only;
it is not part of the local Docker image.
