<?php

declare(strict_types=1);

namespace Koersa\Tests\Reporting\UI;

use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Tests\Support\PortfolioWebTestCase;

final class TaxReportPdfControllerTest extends PortfolioWebTestCase
{
    public function testFreeUserIsRedirectedToTax(): void
    {
        // ADR 0012: the PDF is a paid feature; the dialog is the user-
        // facing gate, the controller redirect is the defence-in-depth.
        $this->client->request('GET', '/tax/report.pdf');

        self::assertResponseRedirects('/tax');
    }

    public function testPaidUserWithNoSellsIsRedirectedToTax(): void
    {
        $this->actAsPaidUser();

        $this->client->request('GET', '/tax/report.pdf');

        self::assertResponseRedirects('/tax');
    }

    public function testPaidUserWithARealizedSellGetsAPdf(): void
    {
        $this->actAsPaidUser();
        $this->recordTransaction('BTC', Side::Buy, '1', '20000');
        $this->recordTransaction('BTC', Side::Sell, '1', '25000');

        $this->client->request('GET', '/tax/report.pdf');

        self::assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        self::assertStringStartsWith('%PDF-', (string) $response->getContent());
    }
}
