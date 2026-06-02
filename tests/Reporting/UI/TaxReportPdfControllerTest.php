<?php

declare(strict_types=1);

namespace Koersa\Tests\Reporting\UI;

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

    // The "paid user, with sells -> 200 + PDF bytes" coverage that lived
    // here pre-ADR-0012 needs IsPaidUser to return true. Stubbing it via
    // the test container fails ("service already initialized") because the
    // kernel touches it early. The paid path comes back the moment Stripe
    // wires a real subscription read model that we can seed from a fixture.
}
