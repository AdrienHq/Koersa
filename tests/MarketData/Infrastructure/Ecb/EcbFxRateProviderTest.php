<?php

declare(strict_types=1);

namespace Koersa\Tests\MarketData\Infrastructure\Ecb;

use DateTimeImmutable;
use Koersa\MarketData\Infrastructure\Ecb\EcbFxRateProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class EcbFxRateProviderTest extends TestCase
{
    private const string FIXTURE = __DIR__.'/../../../Fixtures/Market/ecb_history.xml';

    public function testRebasesEuroBaseRatesIntoTheRequestedDirection(): void
    {
        $provider = $this->provider();

        // ECB on 2025-02-03: 1 EUR = 1.0350 USD. So 1 USD = 1 / 1.0350 EUR.
        $usdToEur = $provider->rateOn(new DateTimeImmutable('2025-02-03'), 'USD', 'EUR');
        self::assertSame('0.966183574879227053', $usdToEur);

        // The inverse direction simply returns the EUR-base rate verbatim.
        self::assertSame('1.035', $provider->rateOn(new DateTimeImmutable('2025-02-03'), 'EUR', 'USD'));
    }

    public function testIdentityRateIsAlwaysOne(): void
    {
        $provider = $this->provider();

        self::assertSame('1', $provider->rateOn(new DateTimeImmutable('2025-02-03'), 'EUR', 'EUR'));
    }

    public function testTreatsStablecoinsAsUsd(): void
    {
        $provider = $this->provider();

        $usdtToEur = $provider->rateOn(new DateTimeImmutable('2025-02-03'), 'USDT', 'EUR');
        $usdToEur = $provider->rateOn(new DateTimeImmutable('2025-02-03'), 'USD', 'EUR');

        self::assertSame($usdToEur, $usdtToEur);
    }

    public function testFallsBackToTheMostRecentPriorPublication(): void
    {
        $provider = $this->provider();

        // 2025-02-01 and 02-02 are a weekend; the rate must come from 2025-01-31.
        $rate = $provider->rateOn(new DateTimeImmutable('2025-02-02'), 'EUR', 'USD');

        self::assertSame('1.03', $rate);
    }

    public function testFetchesTheHistoricalBundleOnlyOnce(): void
    {
        $client = new MockHttpClient([new MockResponse((string) file_get_contents(self::FIXTURE))]);
        $provider = new EcbFxRateProvider($client, new ArrayAdapter());

        $provider->rateOn(new DateTimeImmutable('2025-02-03'), 'USD', 'EUR');
        $provider->rateOn(new DateTimeImmutable('2025-01-31'), 'USD', 'EUR');

        self::assertSame(1, $client->getRequestsCount());
    }

    public function testThrowsForACurrencyTheEcbDoesNotPublish(): void
    {
        $provider = $this->provider();

        $this->expectException(RuntimeException::class);
        $provider->rateOn(new DateTimeImmutable('2025-02-03'), 'JPY', 'EUR');
    }

    private function provider(): EcbFxRateProvider
    {
        $client = new MockHttpClient([new MockResponse((string) file_get_contents(self::FIXTURE))]);

        return new EcbFxRateProvider($client, new ArrayAdapter());
    }
}
