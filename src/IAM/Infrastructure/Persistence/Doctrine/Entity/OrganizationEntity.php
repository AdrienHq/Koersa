<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Persistence\Doctrine\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'iam_organizations')]
#[ORM\UniqueConstraint(name: 'uniq_iam_organizations_slug', columns: ['slug'])]
class OrganizationEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    public string $id;

    #[ORM\Column(length: 255)]
    public string $name;

    #[ORM\Column(length: 255)]
    public string $slug;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;
}
