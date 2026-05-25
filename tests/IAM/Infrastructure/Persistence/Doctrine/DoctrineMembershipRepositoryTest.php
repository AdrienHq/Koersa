<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use Koersa\IAM\Domain\Membership;
use Koersa\IAM\Domain\ValueObject\Role;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\DoctrineMembershipRepository;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\MembershipMapper;
use Koersa\Shared\Domain\Uuid;
use Koersa\Tests\Support\DatabaseTestCase;

final class DoctrineMembershipRepositoryTest extends DatabaseTestCase
{
    private DoctrineMembershipRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DoctrineMembershipRepository($this->entityManager, new MembershipMapper());
    }

    public function testSavesAndLoadsByIdWithItsRole(): void
    {
        $id = Uuid::generate();
        $this->repository->save(
            Membership::create($id, Uuid::generate(), Uuid::generate(), Role::Owner, new DateTimeImmutable()),
        );
        $this->entityManager->clear();

        $loaded = $this->repository->byId($id);
        self::assertNotNull($loaded);
        self::assertSame(Role::Owner, $loaded->role());
    }

    public function testForUserReturnsOnlyThatUsersMemberships(): void
    {
        $userId = Uuid::generate();
        $otherUserId = Uuid::generate();

        $this->repository->save(Membership::create(Uuid::generate(), $userId, Uuid::generate(), Role::Owner, new DateTimeImmutable()));
        $this->repository->save(Membership::create(Uuid::generate(), $userId, Uuid::generate(), Role::Member, new DateTimeImmutable()));
        $this->repository->save(Membership::create(Uuid::generate(), $otherUserId, Uuid::generate(), Role::Admin, new DateTimeImmutable()));
        $this->entityManager->clear();

        $memberships = $this->repository->forUser($userId);

        self::assertCount(2, $memberships);
        foreach ($memberships as $membership) {
            self::assertTrue($membership->userId()->equals($userId));
        }
    }

    public function testForUserReturnsEmptyWhenNone(): void
    {
        self::assertSame([], $this->repository->forUser(Uuid::generate()));
    }
}
