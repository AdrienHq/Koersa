<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Application;

use DateTimeImmutable;
use Koersa\Portfolio\Application\AmendTransaction;
use Koersa\Portfolio\Application\AmendTransactionHandler;
use Koersa\Portfolio\Domain\Event\TransactionAmended;
use Koersa\Portfolio\Domain\PortfolioRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use Koersa\Tests\Support\Portfolios;
use PHPUnit\Framework\TestCase;

final class AmendTransactionHandlerTest extends TestCase
{
    public function testAmendsAKnownTradeAndSavesThePortfolio(): void
    {
        $organizationId = Uuid::generate();
        $transactionId = Uuid::generate();
        $portfolio = Portfolios::empty($organizationId);
        $portfolio->recordTransaction($transactionId, $organizationId, 'BTC', Side::Buy, '1', '100', '0', new DateTimeImmutable());

        $portfolios = $this->createMock(PortfolioRepository::class);
        $portfolios->method('get')->willReturn($portfolio);
        $portfolios->expects(self::once())->method('save')->with($portfolio);

        $handler = new AmendTransactionHandler($portfolios);
        $handler(new AmendTransaction($organizationId, $transactionId, 'eth', Side::Sell, '2', '3000', '5', new DateTimeImmutable()));

        $events = $portfolio->releaseEvents();
        self::assertCount(2, $events);

        $amended = $events[1];
        self::assertInstanceOf(TransactionAmended::class, $amended);
        self::assertTrue($amended->transactionId->equals($transactionId));
        self::assertSame('ETH', $amended->asset);
        self::assertSame(Side::Sell, $amended->side);
    }
}
