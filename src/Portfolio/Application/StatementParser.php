<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Parses an exchange's CSV statement into normalized trades. One implementation
 * per exchange; only buy/sell rows are returned (deposits, withdrawals, staking
 * and transfers are skipped — see ADR 0004).
 */
#[AutoconfigureTag('portfolio.statement_parser')]
interface StatementParser
{
    /**
     * The exchange key this parser handles, e.g. "kraken".
     */
    public function exchange(): string;

    /**
     * @return list<ParsedTrade>
     */
    public function parse(string $contents): array;
}
