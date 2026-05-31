<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use DateTimeImmutable;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;

final readonly class AmendTransaction
{
    public function __construct(
        public Uuid $organizationId,
        public Uuid $transactionId,
        public string $asset,
        public Side $side,
        public string $quantity,
        public string $price,
        public string $fee,
        public DateTimeImmutable $occurredAt,
        public string $priceCurrency = 'EUR',
        public string $feeCurrency = 'EUR',
    ) {
    }
}
