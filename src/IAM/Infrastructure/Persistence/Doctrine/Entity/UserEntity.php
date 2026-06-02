<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Persistence\Doctrine\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'iam_users')]
#[ORM\UniqueConstraint(name: 'uniq_iam_users_email', columns: ['email'])]
class UserEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    public string $id;

    #[ORM\Column(length: 255)]
    public string $email;

    #[ORM\Column(name: 'password_hash')]
    public string $passwordHash;

    #[ORM\Column(name: 'registered_at', type: 'datetime_immutable')]
    public DateTimeImmutable $registeredAt;

    #[ORM\Column(name: 'is_admin', type: 'boolean', options: ['default' => false])]
    public bool $isAdmin = false;

    #[ORM\Column(name: 'is_paid', type: 'boolean', options: ['default' => false])]
    public bool $isPaid = false;
}
