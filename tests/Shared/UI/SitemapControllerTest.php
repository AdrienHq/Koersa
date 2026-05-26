<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\UI;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SitemapControllerTest extends WebTestCase
{
    public function testListsTheLocalisedLandingUrls(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();

        $content = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('<urlset', $content);
        self::assertStringContainsString('/fr', $content);
        self::assertStringContainsString('/nl', $content);
    }
}
