<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Persistence\Doctrine;

use Koersa\IAM\Domain\User;
use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use Koersa\Shared\Domain\Uuid;

final class UserMapper
{
    public function toDomain(UserEntity $entity): User
    {
        return User::reconstitute(
            Uuid::fromString($entity->id),
            new Email($entity->email),
            $entity->passwordHash,
            $entity->registeredAt,
            $entity->isAdmin,
        );
    }

    public function toEntity(User $user, ?UserEntity $entity = null): UserEntity
    {
        $entity ??= new UserEntity();
        $entity->id = (string) $user->id();
        $entity->email = (string) $user->email();
        $entity->passwordHash = $user->passwordHash();
        $entity->registeredAt = $user->registeredAt();
        $entity->isAdmin = $user->isAdmin();

        return $entity;
    }
}
