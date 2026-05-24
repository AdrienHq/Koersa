<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Persistence\Doctrine;

use Koersa\IAM\Domain\Membership;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\Entity\MembershipEntity;
use Koersa\Shared\Domain\Uuid;

final class MembershipMapper
{
    public function toDomain(MembershipEntity $entity): Membership
    {
        return Membership::reconstitute(
            Uuid::fromString($entity->id),
            Uuid::fromString($entity->userId),
            Uuid::fromString($entity->organizationId),
            $entity->role,
            $entity->joinedAt,
        );
    }

    public function toEntity(Membership $membership, ?MembershipEntity $entity = null): MembershipEntity
    {
        $entity ??= new MembershipEntity();
        $entity->id = (string) $membership->id();
        $entity->userId = (string) $membership->userId();
        $entity->organizationId = (string) $membership->organizationId();
        $entity->role = $membership->role();
        $entity->joinedAt = $membership->joinedAt();

        return $entity;
    }
}
