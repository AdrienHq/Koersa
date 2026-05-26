<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Application;

use DateTimeImmutable;
use Koersa\Portfolio\Application\RemoveTransaction;
use Koersa\Portfolio\Application\RemoveTransactionHandler;
use Koersa\Portfolio\Domain\Event\TransactionRemoved;
use Koersa\Portfolio\Domain\PortfolioRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use Koersa\Tests\Support\Portfolios;
use PHPUnit\Framework\TestCase;

final class RemoveTransactionHandlerTest extends TestCase
{
    public function testRemovesAKnownTradeAndSavesThePortfolio(): void
    {
        $organizationId = Uuid::generate();
        $transactionId = Uuid::generate();
        $portfolio = Portfolios::empty($organizationId);
        $portfolio->recordTransaction($transactionId, $organizationId, 'BTC', Side::Buy, '1', '100', '0', new DateTimeImmutable());

        $portfolios = $this->createMock(PortfolioRepository::class);
        $portfolios->method('get')->willReturn($portfolio);
        $portfolios->expects(self::once())->method('save')->with($portfolio);

        $handler = new RemoveTransactionHandler($portfolios);
        $handler(new RemoveTransaction($organizationId, $transactionId));

        $events = $portfolio->releaseEvents();
        self::assertCount(2, $events);

        $removed = $events[1];
        self::assertInstanceOf(TransactionRemoved::class, $removed);
        self::assertTrue($removed->transactionId->equals($transactionId));
    }
}
