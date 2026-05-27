<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use Koersa\Shared\Domain\Uuid;

final readonly class ImportTransactions
{
    /** @param list<ParsedTrade> $trades */
    public function __construct(
        public Uuid $organizationId,
        public string $source,
        public array $trades,
    ) {
    }
}
