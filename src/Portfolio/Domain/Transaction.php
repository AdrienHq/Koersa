<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain;

use DateTimeImmutable;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;

/**
 * A single recorded trade as it appears in the transactions read model. Since
 * Iteration 2 the write side is the event-sourced Portfolio aggregate; this is
 * a projection row rebuilt from TransactionRecorded events, so it carries no
 * behaviour or validation of its own — the aggregate already guarded the data.
 */
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
    ): self {
        return new self($id, $organizationId, $asset, $side, $quantity, $price, $fee, $occurredAt);
    }
}
