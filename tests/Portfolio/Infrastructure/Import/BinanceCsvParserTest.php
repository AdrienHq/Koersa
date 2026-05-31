<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Infrastructure\Import;

use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Portfolio\Infrastructure\Import\BinanceCsvParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BinanceCsvParserTest extends TestCase
{
    private const string FIXTURE = __DIR__.'/../../../Fixtures/Import/binance_trades.csv';

    public function testParsesEverySpotTrade(): void
    {
        $contents = (string) file_get_contents(self::FIXTURE);

        $parser = new BinanceCsvParser();
        $trades = $parser->parse($contents);

        self::assertSame('binance', $parser->exchange());
        self::assertCount(5, $trades);
    }

    public function testReadsBaseAndQuoteAssetsFromTheSuffixesNotThePair(): void
    {
        // The Pair column is concatenated (BTCUSDT). Splitting it requires
        // guessing where the boundary is. The Executed and Amount columns
        // carry the assets as suffixes, which is unambiguous.
        $contents = (string) file_get_contents(self::FIXTURE);

        $trades = (new BinanceCsvParser())->parse($contents);

        self::assertSame('BTC', $trades[0]->asset);
        self::assertSame('USDT', $trades[0]->priceCurrency);
        self::assertSame('ETH', $trades[1]->asset);
        self::assertSame('SOL', $trades[3]->asset);
    }

    public function testNumericValuesAreSeparatedFromTheirAssetSuffix(): void
    {
        $contents = (string) file_get_contents(self::FIXTURE);

        $trades = (new BinanceCsvParser())->parse($contents);

        self::assertSame('0.00500000', $trades[0]->quantity);
        self::assertSame('65000.00', $trades[0]->price);
        self::assertSame('0.32500000', $trades[0]->fee);
    }

    public function testRecordsBnbAsTheFeeCurrencyWhenTheUserPaidInBnb(): void
    {
        // The ETH buy on row 2 paid its fee in BNB (Binance's discount path).
        $contents = (string) file_get_contents(self::FIXTURE);

        $trades = (new BinanceCsvParser())->parse($contents);

        self::assertSame('BNB', $trades[1]->feeCurrency);
        self::assertSame('0.00075000', $trades[1]->fee);
    }

    public function testSideUppercaseIsNormalized(): void
    {
        $contents = (string) file_get_contents(self::FIXTURE);

        $trades = (new BinanceCsvParser())->parse($contents);

        self::assertSame(Side::Buy, $trades[0]->side);
        self::assertSame(Side::Sell, $trades[2]->side);
    }

    public function testSynthesizesAStableExternalIdForDedup(): void
    {
        // Re-parsing the same row must yield the same external id so the
        // aggregate's (source, externalId) dedup catches re-imports.
        $contents = (string) file_get_contents(self::FIXTURE);

        $first = (new BinanceCsvParser())->parse($contents);
        $second = (new BinanceCsvParser())->parse($contents);

        self::assertSame($first[0]->externalId, $second[0]->externalId);
        self::assertNotSame($first[0]->externalId, $first[1]->externalId);
    }

    public function testRejectsAStatementThatIsMissingExpectedColumns(): void
    {
        $contents = "Date(UTC),Pair,Side\n2024-01-01 00:00:00,BTCUSDT,BUY\n";

        $this->expectException(RuntimeException::class);
        (new BinanceCsvParser())->parse($contents);
    }
}
