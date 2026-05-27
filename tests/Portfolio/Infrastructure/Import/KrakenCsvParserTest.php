<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Infrastructure\Import;

use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Portfolio\Infrastructure\Import\KrakenCsvParser;
use PHPUnit\Framework\TestCase;

final class KrakenCsvParserTest extends TestCase
{
    public function testParsesCryptoTradesAndSkipsFiatPairs(): void
    {
        $contents = (string) file_get_contents(__DIR__.'/../../../Fixtures/Import/kraken_trades.csv');

        $parser = new KrakenCsvParser();
        $trades = $parser->parse($contents);

        self::assertSame('kraken', $parser->exchange());

        // The EUR/USD (fiat) row is skipped; the three crypto trades remain.
        self::assertSame(['BTC', 'XRP', 'DOGE'], array_map(static fn ($trade) => $trade->asset, $trades));

        $btc = $trades[0];
        self::assertSame('FAKE001-AAAAA-BBBBB', $btc->externalId);
        self::assertSame(Side::Buy, $btc->side);
        self::assertSame('0.00100000', $btc->quantity);
        self::assertSame('80000.00000', $btc->price);
        self::assertSame('0.20000', $btc->fee);
        self::assertSame('2025-02-01 10:00:00', $btc->occurredAt->format('Y-m-d H:i:s'));

        self::assertSame(Side::Sell, $trades[1]->side);
    }
}
