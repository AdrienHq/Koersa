<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application\Query;

/**
 * A read-model row for the dashboard: the net position in one asset and its
 * weighted-average buy cost. Values are display-formatted strings.
 */
final readonly class Holding
{
    public function __construct(
        public string $asset,
        public string $quantity,
        public string $averageCost,
    ) {
    }
}
