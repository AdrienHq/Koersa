<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Application\Query;

use DateTimeImmutable;
use Koersa\Portfolio\Application\Query\GetHoldings;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class GetHoldingsTest extends TestCase
{
    public function testComputesNetQuantityAndWeightedAverageCost(): void
    {
        $organizationId = Uuid::generate();
        $transactions = $this->createStub(TransactionRepository::class);
        $transactions->method('forOrganization')->willReturn([
            $this->buy($organizationId, 'BTC', '1', '100'),
            $this->buy($organizationId, 'BTC', '1', '200'),
            $this->sell($organizationId, 'BTC', '0.5', '300'),
        ]);

        $holdings = (new GetHoldings($transactions))($organizationId);

        self::assertCount(1, $holdings);
        self::assertSame('BTC', $holdings[0]->asset);
        self::assertSame('1.5', $holdings[0]->quantity);          // 1 + 1 - 0.5
        self::assertSame('150.00', $holdings[0]->averageCost);    // (1*100 + 1*200) / 2
    }

    public function testReturnsNothingWithoutTransactions(): void
    {
        $transactions = $this->createStub(TransactionRepository::class);
        $transactions->method('forOrganization')->willReturn([]);

        self::assertSame([], (new GetHoldings($transactions))(Uuid::generate()));
    }

    private function buy(Uuid $organizationId, string $asset, string $quantity, string $price): Transaction
    {
        return Transaction::record(Uuid::generate(), $organizationId, $asset, Side::Buy, $quantity, $price, '0', new DateTimeImmutable());
    }

    private function sell(Uuid $organizationId, string $asset, string $quantity, string $price): Transaction
    {
        return Transaction::record(Uuid::generate(), $organizationId, $asset, Side::Sell, $quantity, $price, '0', new DateTimeImmutable());
    }
}
