<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Security;

use InvalidArgumentException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Adapts the domain user to Symfony Security. It carries only what the
 * security layer needs; the domain User stays free of framework interfaces.
 */
final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /** @var non-empty-string */
    private readonly string $identifier;

    public function __construct(string $identifier, private readonly string $passwordHash)
    {
        if ('' === $identifier) {
            throw new InvalidArgumentException('A security user identifier cannot be empty.');
        }

        $this->identifier = $identifier;
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
        // No transient credentials to erase: the password hash is the only
        // secret carried, and it is needed to re-authenticate.
    }
}
