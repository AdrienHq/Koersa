# ADR 0010 — Two role levels: platform admin and organisation admin

**Date:** 2026-06-01
**Status:** Accepted

## Context

The product is about to grow two distinct kinds of "elevated user":

1. **Adrien** — the operator of Koersa, needs to see every user, every
   organisation, every signup, and have a few ops actions (rebuild
   projections, send test email, clear cache). Cross-org by definition.
2. **A user who runs an organisation** — typically the person who
   registered the org and onboarded their team. They need extra controls
   *for their own org only* (invite/remove members, edit org settings,
   later: subscription).

Both are "admins," but they live at different layers of the domain.
Conflating them ("there's one ADMIN role") would either give regular
users platform-wide visibility, or force the operator to be a member of
every org. Neither is acceptable.

## Decision

### Two domain mechanisms, one Symfony hierarchy

| Concern                          | Domain mechanism                                | Symfony role        |
|----------------------------------|-------------------------------------------------|---------------------|
| Platform admin (the operator)    | `bool $isAdmin` on `User`                       | `ROLE_ADMIN`        |
| Organisation owner               | `Role::Owner` on `Membership` (existing)        | `ROLE_ORG_OWNER`    |
| Organisation admin (delegated)   | `Role::Admin` on `Membership` (already in enum) | `ROLE_ORG_ADMIN`    |
| Regular org member               | `Role::Member` on `Membership` (existing)       | `ROLE_USER`         |

Symfony `role_hierarchy` makes one role imply lower ones:

```yaml
ROLE_ADMIN:       ROLE_ORG_OWNER
ROLE_ORG_OWNER:   ROLE_ORG_ADMIN
ROLE_ORG_ADMIN:   ROLE_USER
```

Effect: a controller gated on `ROLE_ORG_ADMIN` accepts the operator (via
`ROLE_ADMIN`), the org's owner (via `ROLE_ORG_OWNER`), and anyone with
`Role::Admin` on the *current* membership. A controller gated on
`ROLE_ADMIN` accepts only the operator.

### Why `is_admin` is a flag on `User`, not a membership

A platform admin is platform-wide, not tied to an organisation. Modelling
it as a special membership ("the platform organisation") would require
inventing a synthetic org and force every authorisation check to special-case
it. A boolean on the user aggregate is the simpler, queryable shape.

### Why the org-level role lives on `Membership`

A user can be Admin of one org and a regular Member of another. The
relationship `(user, org) → role` is exactly what `Membership` already
models, and the existing `Role::Admin` enum value covers it. Nothing
schema-changes for this side; it was already there waiting for a use site.

### Granting platform admin

Only via a console command:

```
bin/console iam:user:promote-admin <email>
bin/console iam:user:demote-admin <email>
```

No UI. Promotion happens manually, on the server, by someone with shell
access. This is **deliberate** — `ROLE_ADMIN` lets a user see every other
user's data; the harder it is to grant by accident, the better. If the
need to delegate to a second platform admin ever shows up, that's the
moment to add an admin-promotes-admin UI behind `ROLE_ADMIN`.

### Granting `Role::Admin` on a membership

Owner can promote any member to admin within their own org. Admin can
*not* promote further (only Owner can — single-pivot-point rule). The
existing `canManageMembers()` on the enum already encodes the read side
of this; the write side (UI + handlers) is a future slice.

### `SecurityUser` carries both flags

The current `SecurityUser(identifier, passwordHash, organizationId)` is
augmented to `(identifier, passwordHash, organizationId, isAdmin, currentRole)`.
`SecurityUserProvider` reads `User::isAdmin()` plus the role of the
membership it picked (currently the first one — multi-org switching is a
separate concern). `getRoles()` returns the right Symfony roles based on
both flags.

## Out of scope (this slice)

- **Multi-org user switching.** The provider currently picks the user's
  first membership; if a user is in multiple orgs, the UI to switch
  organisations doesn't exist yet. The role plumbing is ready for it.
- **Per-org admin UI** — managing members, editing the org name,
  inviting via email. Defined as roles today; built when needed.
- **Granular permissions** (read vs write vs delete on each resource).
  Three coarse roles are enough; finer-grained permissions are a clear
  smell — if we reach for them, we've overgrown what the role model
  should carry.
- **Role-based UI variations beyond gating** (e.g. hiding the Tax tab
  for non-admins). All authenticated users still see the same nav; the
  admin link only appears for `ROLE_ADMIN`. Per-tab gating doesn't make
  sense yet because every tab is currently for-everyone.
- **Audit log of role changes** — useful eventually; not before the
  first promote-admin call actually happens.

## Consequences

- Migration adds `is_admin BOOLEAN NOT NULL DEFAULT FALSE` to
  `iam_users`. Existing users default to non-admin; you promote
  yourself via the console command.
- `SecurityUser` constructor signature changes. Tests + the user
  provider both update.
- `security.yaml` gains a `role_hierarchy` block and an
  `access_control` entry for `/admin/*`.
- A new `Reporting`-style decision: when per-org-admin features land,
  controllers gate on `ROLE_ORG_ADMIN` and the Symfony hierarchy does
  the right thing automatically.

## References

- `ARCHITECTURE.md` §2 — IAM context
- `src/IAM/Domain/ValueObject/Role.php` — `Role::Admin` already exists
- Symfony `role_hierarchy` — https://symfony.com/doc/current/security.html#hierarchical-roles
