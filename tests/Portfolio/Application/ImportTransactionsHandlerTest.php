<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Application;

use DateTimeImmutable;
use Koersa\Portfolio\Application\ImportTransactions;
use Koersa\Portfolio\Application\ImportTransactionsHandler;
use Koersa\Portfolio\Application\ParsedTrade;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\PortfolioRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use Koersa\Tests\Support\Portfolios;
use PHPUnit\Framework\TestCase;

final class ImportTransactionsHandlerTest extends TestCase
{
    public function testRecordsEachParsedTradeWithProvenance(): void
    {
        $organizationId = Uuid::generate();
        $portfolio = Portfolios::empty($organizationId);

        $portfolios = $this->createMock(PortfolioRepository::class);
        $portfolios->method('get')->willReturn($portfolio);
        $portfolios->expects(self::once())->method('save')->with($portfolio);

        $trades = [
            new ParsedTrade('TX-1', 'BTC', Side::Buy, '1', '100', '0', new DateTimeImmutable()),
            new ParsedTrade('TX-2', 'ETH', Side::Sell, '2', '200', '1', new DateTimeImmutable()),
        ];

        (new ImportTransactionsHandler($portfolios))(new ImportTransactions($organizationId, 'kraken', $trades));

        $events = $portfolio->releaseEvents();
        self::assertCount(2, $events);

        $first = $events[0];
        self::assertInstanceOf(TransactionRecorded::class, $first);
        self::assertSame('kraken', $first->source);
        self::assertSame('TX-1', $first->externalId);
        self::assertSame('BTC', $first->asset);
    }

    public function testSkipsRowsAlreadyImportedInTheSameBatch(): void
    {
        $organizationId = Uuid::generate();
        $portfolio = Portfolios::empty($organizationId);

        $portfolios = $this->createStub(PortfolioRepository::class);
        $portfolios->method('get')->willReturn($portfolio);

        $trades = [
            new ParsedTrade('TX-1', 'BTC', Side::Buy, '1', '100', '0', new DateTimeImmutable()),
            new ParsedTrade('TX-1', 'BTC', Side::Buy, '1', '100', '0', new DateTimeImmutable()),
        ];

        (new ImportTransactionsHandler($portfolios))(new ImportTransactions($organizationId, 'kraken', $trades));

        self::assertCount(1, $portfolio->releaseEvents());
    }
}
