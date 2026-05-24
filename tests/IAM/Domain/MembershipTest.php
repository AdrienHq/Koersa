<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Domain;

use DateTimeImmutable;
use Koersa\IAM\Domain\Membership;
use Koersa\IAM\Domain\ValueObject\Role;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class MembershipTest extends TestCase
{
    public function testCreateExposesItsState(): void
    {
        $id = Uuid::generate();
        $userId = Uuid::generate();
        $organizationId = Uuid::generate();

        $membership = Membership::create($id, $userId, $organizationId, Role::Owner, new DateTimeImmutable());

        self::assertTrue($membership->id()->equals($id));
        self::assertTrue($membership->userId()->equals($userId));
        self::assertTrue($membership->organizationId()->equals($organizationId));
        self::assertSame(Role::Owner, $membership->role());
    }

    public function testChangeRole(): void
    {
        $membership = Membership::create(
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Role::Member,
            new DateTimeImmutable(),
        );

        $membership->changeRole(Role::Admin);

        self::assertSame(Role::Admin, $membership->role());
    }
}
