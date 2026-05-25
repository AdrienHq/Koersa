<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Application;

use DateTimeImmutable;
use Koersa\Portfolio\Application\RecordTransaction;
use Koersa\Portfolio\Application\RecordTransactionHandler;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\PortfolioId;
use Koersa\Portfolio\Domain\PortfolioRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use Koersa\Tests\Support\Portfolios;
use PHPUnit\Framework\TestCase;

final class RecordTransactionHandlerTest extends TestCase
{
    public function testRecordsTheTradeOnThePortfolioAndSavesIt(): void
    {
        $organizationId = Uuid::generate();
        $portfolio = Portfolios::empty($organizationId);

        $portfolios = $this->createMock(PortfolioRepository::class);
        $portfolios->expects(self::once())->method('get')
            ->with(self::callback(static fn (PortfolioId $id): bool => $id->toString() === $organizationId->value))
            ->willReturn($portfolio);
        $portfolios->expects(self::once())->method('save')->with($portfolio);

        $handler = new RecordTransactionHandler($portfolios);
        $handler(new RecordTransaction($organizationId, 'eth', Side::Sell, '2', '3000', '5', new DateTimeImmutable()));

        // save() is mocked, so the recorded event is still buffered and can be inspected.
        $events = $portfolio->releaseEvents();
        self::assertCount(1, $events);

        $event = $events[0];
        self::assertInstanceOf(TransactionRecorded::class, $event);
        self::assertTrue($event->organizationId->equals($organizationId));
        self::assertSame('ETH', $event->asset);
        self::assertSame(Side::Sell, $event->side);
        self::assertSame('2', $event->quantity);
    }
}
