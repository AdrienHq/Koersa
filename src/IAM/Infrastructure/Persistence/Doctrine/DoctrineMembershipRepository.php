<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Koersa\IAM\Domain\Membership;
use Koersa\IAM\Domain\MembershipRepository;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\Entity\MembershipEntity;
use Koersa\Shared\Domain\Uuid;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MembershipRepository::class)]
final class DoctrineMembershipRepository implements MembershipRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MembershipMapper $mapper,
    ) {
    }

    public function save(Membership $membership): void
    {
        $entity = $this->entityManager->find(MembershipEntity::class, (string) $membership->id());
        $entity = $this->mapper->toEntity($membership, $entity);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function byId(Uuid $id): ?Membership
    {
        $entity = $this->entityManager->find(MembershipEntity::class, (string) $id);

        return $entity instanceof MembershipEntity ? $this->mapper->toDomain($entity) : null;
    }

    public function forUser(Uuid $userId): array
    {
        $entities = $this->entityManager
            ->getRepository(MembershipEntity::class)
            ->findBy(['userId' => (string) $userId]);

        return array_map(
            fn (MembershipEntity $entity): Membership => $this->mapper->toDomain($entity),
            $entities,
        );
    }
}
