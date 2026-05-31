<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application\Query;

final readonly class Holding
{
    public function __construct(
        public string $asset,
        public string $quantity,
        public string $averageCost,
        public ?string $pricePerUnitEur = null,
        public ?string $valueEur = null,
    ) {
    }
}
