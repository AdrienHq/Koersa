<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

// One per exchange; returns buy/sell rows only (see ADR 0004).
#[AutoconfigureTag('portfolio.statement_parser')]
interface StatementParser
{
    public function exchange(): string;

    /** @return list<ParsedTrade> */
    public function parse(string $contents): array;
}
