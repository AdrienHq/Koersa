<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use Koersa\IAM\Domain\User;
use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\UserMapper;
use Koersa\Shared\Domain\Uuid;
use Koersa\Tests\Support\DatabaseTestCase;

final class DoctrineUserRepositoryTest extends DatabaseTestCase
{
    private DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DoctrineUserRepository($this->entityManager, new UserMapper());
    }

    public function testSavesAndLoadsByIdAcrossAClearedManager(): void
    {
        $id = Uuid::generate();
        $registeredAt = new DateTimeImmutable('2026-05-25 09:00:00');
        $this->repository->save(User::register($id, new Email('jane@example.com'), 'hash', $registeredAt));

        $this->entityManager->clear();
        $loaded = $this->repository->byId($id);

        self::assertNotNull($loaded);
        self::assertTrue($loaded->id()->equals($id));
        self::assertSame('jane@example.com', (string) $loaded->email());
        self::assertSame('hash', $loaded->passwordHash());
        self::assertEquals($registeredAt, $loaded->registeredAt());
    }

    public function testFindsByEmail(): void
    {
        $this->repository->save(
            User::register(Uuid::generate(), new Email('jane@example.com'), 'hash', new DateTimeImmutable()),
        );
        $this->entityManager->clear();

        self::assertNotNull($this->repository->byEmail(new Email('jane@example.com')));
    }

    public function testReturnsNullWhenMissing(): void
    {
        self::assertNull($this->repository->byId(Uuid::generate()));
        self::assertNull($this->repository->byEmail(new Email('nobody@example.com')));
    }

    public function testSaveUpdatesAnExistingUser(): void
    {
        $id = Uuid::generate();
        $user = User::register($id, new Email('jane@example.com'), 'hash', new DateTimeImmutable());
        $this->repository->save($user);

        $user->changeEmail(new Email('updated@example.com'));
        $this->repository->save($user);
        $this->entityManager->clear();

        $loaded = $this->repository->byId($id);
        self::assertNotNull($loaded);
        self::assertSame('updated@example.com', (string) $loaded->email());
        self::assertNull($this->repository->byEmail(new Email('jane@example.com')));
    }
}
