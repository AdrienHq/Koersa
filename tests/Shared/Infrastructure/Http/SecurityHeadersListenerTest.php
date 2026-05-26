<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityHeadersListenerTest extends WebTestCase
{
    public function testBaselineHeadersArePresent(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        self::assertResponseHeaderSame('X-Frame-Options', 'SAMEORIGIN');
        self::assertResponseHeaderSame('Referrer-Policy', 'same-origin');
    }
}
