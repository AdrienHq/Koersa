<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\UI;

use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Tests\Support\PortfolioWebTestCase;

final class PortfolioControllerTest extends PortfolioWebTestCase
{
    public function testDashboardRendersForAnEmptyPortfolio(): void
    {
        $this->client->request('GET', '/portfolio');

        // Empty portfolio is the simplest path — exercises every controller
        // dependency without hitting the ECB. Catches DI-wiring regressions
        // like service-shaped classes accidentally ending up in Domain/.
        self::assertResponseIsSuccessful();
    }

    public function testDashboardRendersWithATransaction(): void
    {
        $this->recordTransaction('BTC', Side::Buy, '0.5', '20000');

        $this->client->request('GET', '/portfolio');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'portfolio');
    }
}
