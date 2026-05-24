<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Koersa\IAM\Domain\User;
use Koersa\IAM\Domain\UserRepository;
use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use Koersa\Shared\Domain\Uuid;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(UserRepository::class)]
final class DoctrineUserRepository implements UserRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserMapper $mapper,
    ) {
    }

    public function save(User $user): void
    {
        $entity = $this->entityManager->find(UserEntity::class, (string) $user->id());
        $entity = $this->mapper->toEntity($user, $entity);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function byId(Uuid $id): ?User
    {
        $entity = $this->entityManager->find(UserEntity::class, (string) $id);

        return $entity instanceof UserEntity ? $this->mapper->toDomain($entity) : null;
    }

    public function byEmail(Email $email): ?User
    {
        $entity = $this->entityManager
            ->getRepository(UserEntity::class)
            ->findOneBy(['email' => (string) $email]);

        return $entity instanceof UserEntity ? $this->mapper->toDomain($entity) : null;
    }
}
