<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\UI;

use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Tests\Support\PortfolioWebTestCase;

final class TaxControllerTest extends PortfolioWebTestCase
{
    public function testRendersForAnEmptyPortfolio(): void
    {
        $this->client->request('GET', '/tax');

        // Empty portfolio renders the empty-state — no realized gains yet.
        self::assertResponseIsSuccessful();
    }

    public function testRendersWithABuyButNoSell(): void
    {
        $this->recordTransaction('BTC', Side::Buy, '0.5', '20000');

        $this->client->request('GET', '/tax');

        // A buy alone produces no realized-gains report yet; empty-state again.
        self::assertResponseIsSuccessful();
    }
}
