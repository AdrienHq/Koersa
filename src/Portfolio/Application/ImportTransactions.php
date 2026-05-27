<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use Koersa\Shared\Domain\Uuid;

/**
 * Command: record a batch of trades parsed from an exchange statement.
 */
final readonly class ImportTransactions
{
    /**
     * @param list<ParsedTrade> $trades
     */
    public function __construct(
        public Uuid $organizationId,
        public string $source,
        public array $trades,
    ) {
    }
}
