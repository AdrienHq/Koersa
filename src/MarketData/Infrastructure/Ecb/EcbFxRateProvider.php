<?php

declare(strict_types=1);

namespace Koersa\MarketData\Infrastructure\Ecb;

use DateTimeImmutable;
use Koersa\Shared\Market\FxRateProvider;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

// ECB daily reference rates, EUR-base. We pull the full historical bundle once
// per day and cache it; lookups go against the in-memory map. ECB publishes on
// TARGET business days only — weekends/holidays roll back to the most recent
// prior day. Stablecoins peg to USD here; if a peg ever breaks the rate this
// returns will be wrong, but so will the daily news.
#[AsAlias(FxRateProvider::class)]
final class EcbFxRateProvider implements FxRateProvider
{
    private const string ENDPOINT = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-hist.xml';
    private const string CACHE_KEY = 'marketdata.fx.ecb.history';
    private const int TTL_SECONDS = 86400;

    /** @var array<string, string> stablecoin => USD */
    private const array STABLECOIN_PEGS = [
        'USDT' => 'USD',
        'USDC' => 'USD',
        'DAI' => 'USD',
        'BUSD' => 'USD',
    ];

    /** @var array<string, array<string, string>>|null in-memory copy of the parsed bundle */
    private ?array $rates = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function rateOn(DateTimeImmutable $date, string $from, string $to): string
    {
        $from = $this->normalize($from);
        $to = $this->normalize($to);

        if ($from === $to) {
            return '1';
        }

        $rates = $this->load();
        $effectiveDay = $this->mostRecentPublication($date, $rates);
        $dayRates = $rates[$effectiveDay];

        // ECB publishes "1 EUR = X currency". We rebase by pivoting through EUR.
        $fromEur = 'EUR' === $from ? '1' : $this->rateFor($dayRates, $from, $effectiveDay);
        $toEur = 'EUR' === $to ? '1' : $this->rateFor($dayRates, $to, $effectiveDay);

        \assert(is_numeric($fromEur));
        \assert(is_numeric($toEur));

        // amount_in_from * (eur_per_from) * (to_per_eur) = amount_in_from * toEur / fromEur
        $rate = self::canonical(bcdiv($toEur, $fromEur, 18));
        \assert(is_numeric($rate));

        return $rate;
    }

    private static function canonical(string $amount): string
    {
        if (str_contains($amount, '.')) {
            $amount = rtrim(rtrim($amount, '0'), '.');
        }

        return '' === $amount ? '0' : $amount;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function load(): array
    {
        if (null !== $this->rates) {
            return $this->rates;
        }

        $item = $this->cache->getItem(self::CACHE_KEY);
        if ($item->isHit()) {
            $cached = $item->get();
            if (\is_array($cached) && $this->looksLikeRateBundle($cached)) {
                return $this->rates = $cached;
            }
        }

        $parsed = $this->fetchAndParse();
        $item->set($parsed)->expiresAfter(self::TTL_SECONDS);
        $this->cache->save($item);

        return $this->rates = $parsed;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function fetchAndParse(): array
    {
        try {
            $body = $this->httpClient->request('GET', self::ENDPOINT, ['timeout' => 10])->getContent();
        } catch (Throwable $e) {
            throw new RuntimeException('ECB reference rates are not reachable.', 0, $e);
        }

        try {
            $xml = new SimpleXMLElement($body);
        } catch (Throwable $e) {
            throw new RuntimeException('ECB reference rates response is not valid XML.', 0, $e);
        }

        $rates = [];
        foreach ($xml->Cube->Cube as $dayNode) {
            $day = (string) $dayNode['time'];
            if ('' === $day) {
                continue;
            }

            $dayRates = [];
            foreach ($dayNode->Cube as $currencyNode) {
                $code = strtoupper((string) $currencyNode['currency']);
                $rate = (string) $currencyNode['rate'];
                if ('' === $code || !is_numeric($rate)) {
                    continue;
                }
                $dayRates[$code] = $rate;
            }

            if ([] !== $dayRates) {
                $rates[$day] = $dayRates;
            }
        }

        if ([] === $rates) {
            throw new RuntimeException('ECB reference rates response contained no usable data.');
        }

        return $rates;
    }

    /**
     * @param array<string, array<string, string>> $rates
     */
    private function mostRecentPublication(DateTimeImmutable $date, array $rates): string
    {
        $cursor = $date;
        for ($i = 0; $i < 14; ++$i) {
            $day = $cursor->format('Y-m-d');
            if (isset($rates[$day])) {
                return $day;
            }
            $cursor = $cursor->modify('-1 day');
        }

        throw new RuntimeException(\sprintf('No ECB rate available on or before %s.', $date->format('Y-m-d')));
    }

    /**
     * @param array<string, string> $dayRates
     */
    private function rateFor(array $dayRates, string $currency, string $day): string
    {
        $rate = $dayRates[$currency] ?? null;
        if (null === $rate) {
            throw new RuntimeException(\sprintf('No ECB rate for "%s" on %s.', $currency, $day));
        }

        return $rate;
    }

    private function normalize(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        return self::STABLECOIN_PEGS[$currency] ?? $currency;
    }

    /**
     * Runtime check for the cached shape, since PSR-6 returns mixed and we want
     * a corrupted cache to fall through to a fresh fetch rather than blow up.
     *
     * @phpstan-assert-if-true array<string, array<string, string>> $cached
     */
    private function looksLikeRateBundle(mixed $cached): bool
    {
        if (!\is_array($cached)) {
            return false;
        }
        foreach ($cached as $day => $dayRates) {
            if (!\is_string($day) || !\is_array($dayRates)) {
                return false;
            }
            foreach ($dayRates as $code => $rate) {
                if (!\is_string($code) || !\is_string($rate)) {
                    return false;
                }
            }
        }

        return true;
    }
}
