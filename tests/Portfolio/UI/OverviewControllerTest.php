<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\UI;

use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Tests\Support\PortfolioWebTestCase;

final class OverviewControllerTest extends PortfolioWebTestCase
{
    public function testRendersForAnEmptyPortfolio(): void
    {
        $this->client->request('GET', '/overview');

        // Empty portfolio renders the empty-state messages — exercises every
        // controller dependency without hitting the ECB.
        self::assertResponseIsSuccessful();
    }

    public function testRendersWithTransactions(): void
    {
        $this->recordTransaction('BTC', Side::Buy, '0.5', '20000');

        $this->client->request('GET', '/overview');

        self::assertResponseIsSuccessful();
    }

    public function testHomeRedirectsAuthenticatedUserToOverview(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseRedirects('/overview');
    }
}
