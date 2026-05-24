<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use Koersa\IAM\Domain\Membership;
use Koersa\IAM\Domain\ValueObject\Role;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\MembershipMapper;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class MembershipMapperTest extends TestCase
{
    public function testRoundTripPreservesStateIncludingTheRoleEnum(): void
    {
        $mapper = new MembershipMapper();
        $id = Uuid::generate();
        $userId = Uuid::generate();
        $organizationId = Uuid::generate();
        $joinedAt = new DateTimeImmutable('2026-05-24 10:00:00');

        $entity = $mapper->toEntity(
            Membership::create($id, $userId, $organizationId, Role::Admin, $joinedAt),
        );

        self::assertSame((string) $id, $entity->id);
        self::assertSame((string) $userId, $entity->userId);
        self::assertSame((string) $organizationId, $entity->organizationId);
        self::assertSame(Role::Admin, $entity->role);

        $restored = $mapper->toDomain($entity);

        self::assertTrue($restored->id()->equals($id));
        self::assertTrue($restored->userId()->equals($userId));
        self::assertTrue($restored->organizationId()->equals($organizationId));
        self::assertSame(Role::Admin, $restored->role());
        self::assertEquals($joinedAt, $restored->joinedAt());
    }
}
