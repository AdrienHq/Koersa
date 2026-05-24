<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use Koersa\IAM\Domain\User;
use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\UserMapper;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class UserMapperTest extends TestCase
{
    public function testRoundTripPreservesState(): void
    {
        $mapper = new UserMapper();
        $id = Uuid::generate();
        $registeredAt = new DateTimeImmutable('2026-05-24 10:00:00');

        $entity = $mapper->toEntity(User::register($id, new Email('jane@example.com'), 'hash', $registeredAt));

        self::assertSame((string) $id, $entity->id);
        self::assertSame('jane@example.com', $entity->email);
        self::assertSame('hash', $entity->passwordHash);

        $restored = $mapper->toDomain($entity);

        self::assertTrue($restored->id()->equals($id));
        self::assertSame('jane@example.com', (string) $restored->email());
        self::assertSame('hash', $restored->passwordHash());
        self::assertEquals($registeredAt, $restored->registeredAt());
    }

    public function testToEntityReusesTheProvidedEntity(): void
    {
        $mapper = new UserMapper();
        $existing = new UserEntity();

        $user = User::register(Uuid::generate(), new Email('jane@example.com'), 'hash', new DateTimeImmutable());

        self::assertSame($existing, $mapper->toEntity($user, $existing));
    }
}
