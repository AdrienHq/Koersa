<?php

declare(strict_types=1);

namespace Koersa\Shared\Infrastructure\Persistence\Doctrine\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'signups')]
#[ORM\UniqueConstraint(name: 'uniq_signup_email', columns: ['email'])]
class SignupEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    public string $id;

    #[ORM\Column(length: 255)]
    public string $email;

    #[ORM\Column(length: 5)]
    public string $locale;

    #[ORM\Column(name: 'signed_up_at', type: 'datetime_immutable')]
    public DateTimeImmutable $signedUpAt;
}
