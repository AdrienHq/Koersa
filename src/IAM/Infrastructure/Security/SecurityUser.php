<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Security;

use InvalidArgumentException;
use Koersa\IAM\Domain\ValueObject\Role;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Security\HasOrganization;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

// Adapts the domain user to Symfony Security, keeping the domain free of
// framework interfaces. Symfony roles are derived from two domain inputs:
// the platform-admin flag on User, and the role of the current Membership.
// See ADR 0010.
final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface, HasOrganization
{
    /** @var non-empty-string */
    private readonly string $identifier;

    public function __construct(
        string $identifier,
        private readonly string $passwordHash,
        private readonly string $organizationId,
        private readonly bool $isAdmin = false,
        private readonly Role $currentRole = Role::Member,
    ) {
        if ('' === $identifier) {
            throw new InvalidArgumentException('A security user identifier cannot be empty.');
        }

        $this->identifier = $identifier;
    }

    public function organizationId(): Uuid
    {
        return Uuid::fromString($this->organizationId);
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        if ($this->isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }

        // role_hierarchy in security.yaml makes ROLE_ORG_OWNER imply
        // ROLE_ORG_ADMIN; we still emit the leaf role explicitly so the
        // Profiler shows what's actually active for the current membership.
        if (Role::Owner === $this->currentRole) {
            $roles[] = 'ROLE_ORG_OWNER';
        } elseif (Role::Admin === $this->currentRole) {
            $roles[] = 'ROLE_ORG_ADMIN';
        }

        return $roles;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function eraseCredentials(): void
    {
        // nothing transient to erase
    }
}
