<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\UI;

use Doctrine\ORM\EntityManagerInterface;
use Koersa\Shared\Domain\SignupRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LandingControllerTest extends WebTestCase
{
    public function testHomeRedirectsToALocalisedLanding(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseRedirects('/fr');
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

    public function testSigningUpPersistsTheEmail(): void
    {
        $client = static::createClient();
        static::getContainer()->get(EntityManagerInterface::class)
            ->getConnection()->executeStatement('TRUNCATE signups');

        $crawler = $client->request('GET', '/fr');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form(['beta_signup_form[email]' => 'jane@example.be']);
        $client->submit($form);
        self::assertResponseRedirects('/fr');

        self::assertTrue(static::getContainer()->get(SignupRepository::class)->existsByEmail('jane@example.be'));
    }
}
