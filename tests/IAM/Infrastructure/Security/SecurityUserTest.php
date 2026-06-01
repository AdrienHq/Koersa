<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Infrastructure\Security;

use InvalidArgumentException;
use Koersa\IAM\Domain\ValueObject\Role;
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

    public function testPlatformAdminFlagAddsRoleAdmin(): void
    {
        $user = new SecurityUser('admin@example.com', 'hash', (string) Uuid::generate(), isAdmin: true);

        self::assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testOrgOwnerMembershipExposesRoleOrgOwner(): void
    {
        $user = new SecurityUser('owner@example.com', 'hash', (string) Uuid::generate(), currentRole: Role::Owner);

        self::assertContains('ROLE_ORG_OWNER', $user->getRoles());
        // role_hierarchy in security.yaml chains ORG_OWNER -> ORG_ADMIN -> USER;
        // the leaf token itself only emits the explicit role.
        self::assertNotContains('ROLE_ORG_ADMIN', $user->getRoles());
    }

    public function testOrgAdminMembershipExposesRoleOrgAdmin(): void
    {
        $user = new SecurityUser('admin@example.com', 'hash', (string) Uuid::generate(), currentRole: Role::Admin);

        self::assertContains('ROLE_ORG_ADMIN', $user->getRoles());
        self::assertNotContains('ROLE_ORG_OWNER', $user->getRoles());
    }

    public function testRegularMemberStaysAtRoleUserOnly(): void
    {
        $user = new SecurityUser('member@example.com', 'hash', (string) Uuid::generate(), currentRole: Role::Member);

        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testFlagsStack(): void
    {
        $user = new SecurityUser('boss@example.com', 'hash', (string) Uuid::generate(), isAdmin: true, currentRole: Role::Owner);

        self::assertSame(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_ORG_OWNER'], $user->getRoles());
    }
}
