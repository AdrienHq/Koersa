<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Persistence\Doctrine\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Koersa\IAM\Domain\ValueObject\Role;

#[ORM\Entity]
#[ORM\Table(name: 'iam_memberships')]
#[ORM\UniqueConstraint(name: 'uniq_iam_membership_user_org', columns: ['user_id', 'organization_id'])]
class MembershipEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    public string $id;

    #[ORM\Column(name: 'user_id', type: 'guid')]
    public string $userId;

    #[ORM\Column(name: 'organization_id', type: 'guid')]
    public string $organizationId;

    #[ORM\Column(length: 16, enumType: Role::class)]
    public Role $role;

    #[ORM\Column(name: 'joined_at', type: 'datetime_immutable')]
    public DateTimeImmutable $joinedAt;
}
