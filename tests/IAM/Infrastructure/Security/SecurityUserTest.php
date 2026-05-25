<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Infrastructure\Security;

use InvalidArgumentException;
use Koersa\IAM\Infrastructure\Security\SecurityUser;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class SecurityUserTest extends TestCase
{
    public function testExposesItsIdentityAndOrganization(): void
    {
        $organizationId = Uuid::generate();
        $user = new SecurityUser('jane@example.com', 'hash', (string) $organizationId);

        self::assertSame('jane@example.com', $user->getUserIdentifier());
        self::assertSame('hash', $user->getPassword());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertTrue($user->organizationId()->equals($organizationId));
    }

    public function testRejectsAnEmptyIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SecurityUser('', 'hash', (string) Uuid::generate());
    }
}
