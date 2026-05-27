<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Resolves the right {@see StatementParser} for an exchange.
 */
final class StatementParserRegistry
{
    /**
     * @var array<string, StatementParser>
     */
    private array $parsers = [];

    /**
     * @param iterable<StatementParser> $parsers
     */
    public function __construct(
        #[AutowireIterator('portfolio.statement_parser')]
        iterable $parsers,
    ) {
        foreach ($parsers as $parser) {
            $this->parsers[$parser->exchange()] = $parser;
        }
    }

    public function parserFor(string $exchange): StatementParser
    {
        return $this->parsers[$exchange]
            ?? throw new InvalidArgumentException(\sprintf('No statement parser for exchange "%s".', $exchange));
    }

    /**
     * @return list<string>
     */
    public function supportedExchanges(): array
    {
        return array_keys($this->parsers);
    }
}
