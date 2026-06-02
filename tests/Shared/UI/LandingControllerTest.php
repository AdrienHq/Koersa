<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\UI;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LandingControllerTest extends WebTestCase
{
    public function testHomeRedirectsToALocalisedLanding(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'fr-BE,fr;q=0.9']);
        self::assertResponseRedirects('/fr');

        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9']);
        self::assertResponseRedirects('/en');
    }

    public function testRendersTheFrenchAndDutchHeadlines(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'plus-values');

        $client->request('GET', '/nl');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'meerwaarden');
    }

    public function testHeroLinksStraightToTheRegistrationFormNotABetaWaitlist(): void
    {
        // ADR 0012: no pricing on the landing, no beta-waitlist form;
        // the primary CTA goes to /register.
        $client = static::createClient();
        $client->request('GET', '/en');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/register"]');
        self::assertSelectorExists('a[href="/login"]');
    }
}
