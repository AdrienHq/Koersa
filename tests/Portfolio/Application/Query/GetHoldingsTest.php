<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Application\Query;

use DateTimeImmutable;
use Koersa\Portfolio\Application\Query\GetHoldings;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Market\PriceProvider;
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

        $holdings = (new GetHoldings($transactions, $this->priceProvider([])))($organizationId);

        self::assertCount(1, $holdings);
        self::assertSame('BTC', $holdings[0]->asset);
        self::assertSame('1.5', $holdings[0]->quantity);          // 1 + 1 - 0.5
        self::assertSame('150.00', $holdings[0]->averageCost);    // (1*100 + 1*200) / 2
        self::assertNull($holdings[0]->pricePerUnitEur);
        self::assertNull($holdings[0]->valueEur);
    }

    public function testReturnsNothingWithoutTransactions(): void
    {
        $transactions = $this->createStub(TransactionRepository::class);
        $transactions->method('forOrganization')->willReturn([]);

        self::assertSame([], (new GetHoldings($transactions, $this->priceProvider([])))(Uuid::generate()));
    }

    public function testHidesClosedAndNegativePositions(): void
    {
        $organizationId = Uuid::generate();
        $transactions = $this->createStub(TransactionRepository::class);
        $transactions->method('forOrganization')->willReturn([
            $this->buy($organizationId, 'BTC', '1', '100'),
            $this->sell($organizationId, 'BTC', '1', '120'),
            $this->sell($organizationId, 'DOGE', '50', '0.1'),
            $this->buy($organizationId, 'ETH', '2', '3000'),
        ]);

        $holdings = (new GetHoldings($transactions, $this->priceProvider([])))($organizationId);

        self::assertCount(1, $holdings);
        self::assertSame('ETH', $holdings[0]->asset);
    }

    public function testEnrichesHoldingsWithEurPricesAndValue(): void
    {
        $organizationId = Uuid::generate();
        $transactions = $this->createStub(TransactionRepository::class);
        $transactions->method('forOrganization')->willReturn([
            $this->buy($organizationId, 'BTC', '0.5', '50000'),
        ]);

        $holdings = (new GetHoldings($transactions, $this->priceProvider(['BTC' => '60000'])))($organizationId);

        self::assertSame('60000', $holdings[0]->pricePerUnitEur);
        self::assertSame('30000.00', $holdings[0]->valueEur); // 0.5 × 60000
    }

    private function buy(Uuid $organizationId, string $asset, string $quantity, string $price): Transaction
    {
        return Transaction::reconstitute(Uuid::generate(), $organizationId, $asset, Side::Buy, $quantity, $price, '0', new DateTimeImmutable());
    }

    private function sell(Uuid $organizationId, string $asset, string $quantity, string $price): Transaction
    {
        return Transaction::reconstitute(Uuid::generate(), $organizationId, $asset, Side::Sell, $quantity, $price, '0', new DateTimeImmutable());
    }

    /**
     * @param array<string, string> $prices
     */
    private function priceProvider(array $prices): PriceProvider
    {
        $provider = $this->createStub(PriceProvider::class);
        $provider->method('pricesInEur')->willReturn($prices);

        return $provider;
    }
}
