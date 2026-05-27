<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\UI;

use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Tests\Support\PortfolioWebTestCase;
use ZipArchive;

final class ImportTransactionsControllerTest extends PortfolioWebTestCase
{
    public function testRendersTheImportForm(): void
    {
        $this->client->request('GET', '/portfolio/import');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[type=file]');
    }

    public function testImportingAKrakenExportRecordsTheTrades(): void
    {
        $crawler = $this->client->request('GET', '/portfolio/import');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form();
        $form['import_form[exchange]'] = 'kraken';
        $form['import_form[file]']->upload(__DIR__.'/../../Fixtures/Import/kraken_trades.csv');
        $this->client->submit($form);

        self::assertResponseRedirects('/portfolio');

        $transactions = static::getContainer()->get(TransactionRepository::class)->forOrganization($this->organizationId);
        self::assertCount(3, $transactions);
        self::assertSame('kraken', $transactions[0]->source);
        self::assertNotNull($transactions[0]->externalId);
    }

    public function testImportingAZippedKrakenExportRecordsTheTrades(): void
    {
        $zipPath = sys_get_temp_dir().'/koersa_kraken_'.uniqid().'.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile(__DIR__.'/../../Fixtures/Import/kraken_trades.csv', 'trades.csv');
        $zip->close();

        $crawler = $this->client->request('GET', '/portfolio/import');
        $form = $crawler->filter('form')->form();
        $form['import_form[exchange]'] = 'kraken';
        $form['import_form[file]']->upload($zipPath);
        $this->client->submit($form);

        self::assertResponseRedirects('/portfolio');

        $transactions = static::getContainer()->get(TransactionRepository::class)->forOrganization($this->organizationId);
        self::assertCount(3, $transactions);

        unlink($zipPath);
    }
}
