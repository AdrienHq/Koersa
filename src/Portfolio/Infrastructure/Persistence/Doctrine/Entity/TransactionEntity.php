<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Infrastructure\Persistence\Doctrine\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Koersa\Portfolio\Domain\ValueObject\Side;

#[ORM\Entity]
#[ORM\Table(name: 'portfolio_transactions')]
#[ORM\Index(name: 'idx_portfolio_tx_org', columns: ['organization_id'])]
class TransactionEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    public string $id;

    #[ORM\Column(name: 'organization_id', type: 'guid')]
    public string $organizationId;

    #[ORM\Column(length: 12)]
    public string $asset;

    #[ORM\Column(length: 8, enumType: Side::class)]
    public Side $side;

    #[ORM\Column(type: 'decimal', precision: 36, scale: 18)]
    public string $quantity;

    #[ORM\Column(type: 'decimal', precision: 36, scale: 18)]
    public string $price;

    #[ORM\Column(type: 'decimal', precision: 36, scale: 18)]
    public string $fee;

    #[ORM\Column(name: 'occurred_at', type: 'datetime_immutable')]
    public DateTimeImmutable $occurredAt;
}
