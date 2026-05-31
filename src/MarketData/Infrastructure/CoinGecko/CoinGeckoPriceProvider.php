<?php

declare(strict_types=1);

namespace Koersa\MarketData\Infrastructure\CoinGecko;

use Koersa\Shared\Market\PriceProvider;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

// Free CoinGecko spot endpoint, per-asset cached for 5 minutes. Failures and
// unknown symbols just don't return — the dashboard renders them as "—".
#[AsAlias(PriceProvider::class)]
final class CoinGeckoPriceProvider implements PriceProvider
{
    private const string ENDPOINT = 'https://api.coingecko.com/api/v3/simple/price';
    private const int TTL_SECONDS = 300;

    /** @var array<string, string> */
    private const array ASSET_TO_COINGECKO = [
        'BTC' => 'bitcoin',
        'ETH' => 'ethereum',
        'XRP' => 'ripple',
        'DOGE' => 'dogecoin',
        'TRUMP' => 'official-trump',
        'SOL' => 'solana',
        'ADA' => 'cardano',
        'USDT' => 'tether',
        'USDC' => 'usd-coin',
        'BNB' => 'binancecoin',
        'MATIC' => 'matic-network',
        'DOT' => 'polkadot',
        'AVAX' => 'avalanche-2',
        'LTC' => 'litecoin',
        'BCH' => 'bitcoin-cash',
        'LINK' => 'chainlink',
        'UNI' => 'uniswap',
        'ATOM' => 'cosmos',
        'XLM' => 'stellar',
        'SHIB' => 'shiba-inu',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function pricesInEur(array $assets): array
    {
        $result = [];
        /** @var array<string, string> $missing — coingecko id => uppercase symbol */
        $missing = [];

        foreach (array_unique(array_map('strtoupper', $assets)) as $symbol) {
            $cgId = self::ASSET_TO_COINGECKO[$symbol] ?? null;
            if (null === $cgId) {
                continue;
            }

            $item = $this->cache->getItem('marketdata.price.eur.'.$cgId);
            if ($item->isHit()) {
                $cached = $item->get();
                if (\is_string($cached)) {
                    $result[$symbol] = $cached;
                }
                continue;
            }

            $missing[$cgId] = $symbol;
        }

        if ([] === $missing) {
            return $result;
        }

        foreach ($this->fetch(array_keys($missing)) as $cgId => $price) {
            $symbol = $missing[$cgId] ?? null;
            if (null === $symbol) {
                continue;
            }

            $result[$symbol] = $price;

            $item = $this->cache->getItem('marketdata.price.eur.'.$cgId);
            $item->set($price)->expiresAfter(self::TTL_SECONDS);
            $this->cache->save($item);
        }

        return $result;
    }

    /**
     * @param non-empty-list<string> $coingeckoIds
     *
     * @return array<string, string> coingecko id => EUR price as a decimal string
     */
    private function fetch(array $coingeckoIds): array
    {
        try {
            $response = $this->httpClient->request('GET', self::ENDPOINT, [
                'query' => ['ids' => implode(',', $coingeckoIds), 'vs_currencies' => 'eur'],
                'timeout' => 5,
            ]);
            $data = $response->toArray();
        } catch (Throwable) {
            return [];
        }

        $prices = [];
        foreach ($data as $id => $byCurrency) {
            if (!\is_string($id) || !\is_array($byCurrency) || !isset($byCurrency['eur'])) {
                continue;
            }

            $eur = $byCurrency['eur'];
            if (!\is_int($eur) && !\is_float($eur) && !\is_string($eur)) {
                continue;
            }

            $prices[$id] = (string) $eur;
        }

        return $prices;
    }
}
