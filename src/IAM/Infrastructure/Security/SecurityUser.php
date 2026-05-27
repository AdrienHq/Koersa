<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Security;

use InvalidArgumentException;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Security\HasOrganization;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

// Adapts the domain user to Symfony Security, keeping the domain free of framework interfaces.
final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface, HasOrganization
{
    /** @var non-empty-string */
    private readonly string $identifier;

    public function __construct(
        string $identifier,
        private readonly string $passwordHash,
        private readonly string $organizationId,
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
        return ['ROLE_USER'];
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
