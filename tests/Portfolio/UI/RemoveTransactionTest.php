<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\UI;

use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Tests\Support\PortfolioWebTestCase;

final class RemoveTransactionTest extends PortfolioWebTestCase
{
    public function testRemovingATransactionDeletesItFromTheReadModel(): void
    {
        $id = $this->recordTransaction('BTC');

        $this->client->request('GET', '/portfolio/transactions/'.$id.'/edit');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Delete');
        self::assertResponseRedirects('/portfolio');
        $this->client->followRedirect();

        $transactions = static::getContainer()->get(TransactionRepository::class)->forOrganization($this->organizationId);
        self::assertCount(0, $transactions);
    }
}
