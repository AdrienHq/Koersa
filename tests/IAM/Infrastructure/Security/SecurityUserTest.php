<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Infrastructure\Security;

use InvalidArgumentException;
use Koersa\IAM\Infrastructure\Security\SecurityUser;
use PHPUnit\Framework\TestCase;

final class SecurityUserTest extends TestCase
{
    public function testExposesIdentifierPasswordAndDefaultRole(): void
    {
        $user = new SecurityUser('jane@example.com', 'hash');

        self::assertSame('jane@example.com', $user->getUserIdentifier());
        self::assertSame('hash', $user->getPassword());
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testRejectsAnEmptyIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SecurityUser('', 'hash');
    }
}
