<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain;

use DateTimeImmutable;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;

final readonly class Transaction
{
    private function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public string $asset,
        public Side $side,
        public string $quantity,
        public string $price,
        public string $fee,
        public DateTimeImmutable $occurredAt,
        public string $source = 'manual',
        public ?string $externalId = null,
    ) {
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        string $asset,
        Side $side,
        string $quantity,
        string $price,
        string $fee,
        DateTimeImmutable $occurredAt,
        string $source = 'manual',
        ?string $externalId = null,
    ): self {
        return new self($id, $organizationId, $asset, $side, $quantity, $price, $fee, $occurredAt, $source, $externalId);
    }
}
