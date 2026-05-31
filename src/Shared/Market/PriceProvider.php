<?php

declare(strict_types=1);

namespace Koersa\Shared\Market;

interface PriceProvider
{
    /**
     * @param list<string> $assets uppercase symbols, e.g. ["BTC", "ETH"]
     *
     * @return array<string, string> symbol => EUR price as a decimal string;
     *                               unknown symbols are simply absent
     */
    public function pricesInEur(array $assets): array;
}
