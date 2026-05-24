<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Persistence\Doctrine;

use Koersa\IAM\Domain\Organization;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\Entity\OrganizationEntity;
use Koersa\Shared\Domain\Uuid;

final class OrganizationMapper
{
    public function toDomain(OrganizationEntity $entity): Organization
    {
        return Organization::reconstitute(
            Uuid::fromString($entity->id),
            $entity->name,
            $entity->slug,
            $entity->createdAt,
        );
    }

    public function toEntity(Organization $organization, ?OrganizationEntity $entity = null): OrganizationEntity
    {
        $entity ??= new OrganizationEntity();
        $entity->id = (string) $organization->id();
        $entity->name = $organization->name();
        $entity->slug = $organization->slug();
        $entity->createdAt = $organization->createdAt();

        return $entity;
    }
}
