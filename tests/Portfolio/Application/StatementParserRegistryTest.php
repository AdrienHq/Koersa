<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Application;

use InvalidArgumentException;
use Koersa\Portfolio\Application\StatementParser;
use Koersa\Portfolio\Application\StatementParserRegistry;
use PHPUnit\Framework\TestCase;

final class StatementParserRegistryTest extends TestCase
{
    public function testResolvesAParserByExchange(): void
    {
        $parser = $this->stubParser('kraken');
        $registry = new StatementParserRegistry([$parser]);

        self::assertSame($parser, $registry->parserFor('kraken'));
        self::assertSame(['kraken'], $registry->supportedExchanges());
    }

    public function testThrowsForAnUnknownExchange(): void
    {
        $registry = new StatementParserRegistry([]);

        $this->expectException(InvalidArgumentException::class);
        $registry->parserFor('coinbase');
    }

    private function stubParser(string $exchange): StatementParser
    {
        $parser = $this->createStub(StatementParser::class);
        $parser->method('exchange')->willReturn($exchange);

        return $parser;
    }
}
