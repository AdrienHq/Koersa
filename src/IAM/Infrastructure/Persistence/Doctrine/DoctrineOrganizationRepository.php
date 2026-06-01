<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Koersa\IAM\Domain\Organization;
use Koersa\IAM\Domain\OrganizationRepository;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\Entity\OrganizationEntity;
use Koersa\Shared\Domain\Uuid;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(OrganizationRepository::class)]
final class DoctrineOrganizationRepository implements OrganizationRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrganizationMapper $mapper,
    ) {
    }

    public function save(Organization $organization): void
    {
        $entity = $this->entityManager->find(OrganizationEntity::class, (string) $organization->id());
        $entity = $this->mapper->toEntity($organization, $entity);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function byId(Uuid $id): ?Organization
    {
        $entity = $this->entityManager->find(OrganizationEntity::class, (string) $id);

        return $entity instanceof OrganizationEntity ? $this->mapper->toDomain($entity) : null;
    }

    public function bySlug(string $slug): ?Organization
    {
        $entity = $this->entityManager
            ->getRepository(OrganizationEntity::class)
            ->findOneBy(['slug' => $slug]);

        return $entity instanceof OrganizationEntity ? $this->mapper->toDomain($entity) : null;
    }

    public function count(): int
    {
        return (int) $this->entityManager
            ->getRepository(OrganizationEntity::class)
            ->count([]);
    }

    public function recent(int $limit = 50): array
    {
        $entities = $this->entityManager
            ->getRepository(OrganizationEntity::class)
            ->findBy([], ['createdAt' => 'DESC'], $limit);

        return array_map(
            fn (OrganizationEntity $entity): Organization => $this->mapper->toDomain($entity),
            $entities,
        );
    }
}
