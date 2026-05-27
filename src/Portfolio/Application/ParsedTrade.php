<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use DateTimeImmutable;
use Koersa\Portfolio\Domain\ValueObject\Side;

/**
 * One trade row extracted from an exchange statement, normalized to the shape
 * the Portfolio aggregate records. `externalId` is the exchange's row id, used
 * to keep re-imports idempotent.
 */
final readonly class ParsedTrade
{
    public function __construct(
        public string $externalId,
        public string $asset,
        public Side $side,
        public string $quantity,
        public string $price,
        public string $fee,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
