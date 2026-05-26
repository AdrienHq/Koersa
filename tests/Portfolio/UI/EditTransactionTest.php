<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\UI;

use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Tests\Support\PortfolioWebTestCase;

final class EditTransactionTest extends PortfolioWebTestCase
{
    public function testAmendingATransactionUpdatesTheReadModel(): void
    {
        $id = $this->recordTransaction('BTC', Side::Buy, '1', '100');

        $this->client->request('GET', '/portfolio/transactions/'.$id.'/edit');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Save changes', [
            'transaction_form[quantity]' => '5',
        ]);
        self::assertResponseRedirects('/portfolio');
        $this->client->followRedirect();

        $transactions = static::getContainer()->get(TransactionRepository::class)->forOrganization($this->organizationId);
        self::assertCount(1, $transactions);
        self::assertSame('5', $transactions[0]->quantity);
    }
}
