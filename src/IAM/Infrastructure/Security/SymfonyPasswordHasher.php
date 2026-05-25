<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Security;

use Koersa\IAM\Application\PasswordHasher;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

#[AsAlias(PasswordHasher::class)]
final class SymfonyPasswordHasher implements PasswordHasher
{
    public function __construct(private readonly PasswordHasherFactoryInterface $factory)
    {
    }

    public function hash(string $plainPassword): string
    {
        // Hash with the algorithm configured for SecurityUser, so login can
        // verify it through the same hasher.
        return $this->factory->getPasswordHasher(SecurityUser::class)->hash($plainPassword);
    }
}
