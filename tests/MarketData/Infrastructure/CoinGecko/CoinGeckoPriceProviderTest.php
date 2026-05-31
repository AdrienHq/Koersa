<?php

declare(strict_types=1);

namespace Koersa\Tests\MarketData\Infrastructure\CoinGecko;

use const JSON_THROW_ON_ERROR;

use Koersa\MarketData\Infrastructure\CoinGecko\CoinGeckoPriceProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CoinGeckoPriceProviderTest extends TestCase
{
    public function testMapsKnownSymbolsAndDropsUnknownOnes(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode(['bitcoin' => ['eur' => 67234.5], 'ethereum' => ['eur' => 2456.7]], JSON_THROW_ON_ERROR)),
        ]);

        $provider = new CoinGeckoPriceProvider($client, new ArrayAdapter());
        $prices = $provider->pricesInEur(['BTC', 'ETH', 'NOTASYMBOL']);

        self::assertSame('67234.5', $prices['BTC']);
        self::assertSame('2456.7', $prices['ETH']);
        self::assertArrayNotHasKey('NOTASYMBOL', $prices);
        self::assertSame(1, $client->getRequestsCount());
    }

    public function testCachesPerAssetAndSkipsTheNetworkOnTheSecondCall(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode(['bitcoin' => ['eur' => 67234.5]], JSON_THROW_ON_ERROR)),
            // No second response: the test fails if the provider tries to hit the network again.
        ]);

        $provider = new CoinGeckoPriceProvider($client, new ArrayAdapter());

        $first = $provider->pricesInEur(['BTC']);
        $second = $provider->pricesInEur(['BTC']);

        self::assertSame('67234.5', $first['BTC']);
        self::assertSame('67234.5', $second['BTC']);
        self::assertSame(1, $client->getRequestsCount());
    }

    public function testReturnsEmptyWhenCoinGeckoFails(): void
    {
        $client = new MockHttpClient([new MockResponse('upstream error', ['http_code' => 500])]);
        $provider = new CoinGeckoPriceProvider($client, new ArrayAdapter());

        self::assertSame([], $provider->pricesInEur(['BTC']));
    }
}
