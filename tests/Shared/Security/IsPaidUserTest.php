<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\Security;

use Koersa\Shared\Security\HasPaidAccess;
use Koersa\Shared\Security\IsPaidUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

final class IsPaidUserTest extends TestCase
{
    public function testReturnsFalseForAnonymous(): void
    {
        self::assertFalse((new IsPaidUser())(null));
    }

    public function testReturnsFalseForARegularUser(): void
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getRoles')->willReturn(['ROLE_USER']);

        self::assertFalse((new IsPaidUser())($user));
    }

    public function testReturnsTrueForPlatformAdmin(): void
    {
        // The operator always sees the full app — otherwise the paywall
        // locks them out of their own product.
        $user = $this->createStub(UserInterface::class);
        $user->method('getRoles')->willReturn(['ROLE_USER', 'ROLE_ADMIN']);

        self::assertTrue((new IsPaidUser())($user));
    }

    public function testReturnsTrueForUserWithIsPaidFlag(): void
    {
        $user = new class implements UserInterface, HasPaidAccess {
            /** @return list<string> */
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function getUserIdentifier(): string
            {
                return 'beta@example.com';
            }

            public function eraseCredentials(): void
            {
            }

            public function isPaid(): bool
            {
                return true;
            }
        };

        self::assertTrue((new IsPaidUser())($user));
    }

    public function testReturnsFalseForUserWithIsPaidFlagOff(): void
    {
        $user = new class implements UserInterface, HasPaidAccess {
            /** @return list<string> */
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function getUserIdentifier(): string
            {
                return 'free@example.com';
            }

            public function eraseCredentials(): void
            {
            }

            public function isPaid(): bool
            {
                return false;
            }
        };

        self::assertFalse((new IsPaidUser())($user));
    }
}
