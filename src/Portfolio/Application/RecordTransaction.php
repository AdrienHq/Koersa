<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use DateTimeImmutable;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;

/**
 * Command: record a trade in an organization's portfolio.
 */
final readonly class RecordTransaction
{
    public function __construct(
        public Uuid $organizationId,
        public string $asset,
        public Side $side,
        public string $quantity,
        public string $price,
        public string $fee,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
