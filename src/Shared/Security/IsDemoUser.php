<?php

declare(strict_types=1);

namespace Koersa\Shared\Security;

use Symfony\Component\Security\Core\User\UserInterface;

// Single source of truth for "is this the public demo account" (ADR 0011).
// Used by the persistent banner, the write-lock voter and the seed command.
final class IsDemoUser
{
    public const string EMAIL = 'demo@koersa.local';

    public function __invoke(?UserInterface $user): bool
    {
        return null !== $user && self::EMAIL === $user->getUserIdentifier();
    }
}
