<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\UI;

use Koersa\Tests\Support\PortfolioWebTestCase;

final class ImportTransactionsControllerTest extends PortfolioWebTestCase
{
    public function testRendersTheImportForm(): void
    {
        $this->client->request('GET', '/portfolio/import');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[type=file]');
    }
}
