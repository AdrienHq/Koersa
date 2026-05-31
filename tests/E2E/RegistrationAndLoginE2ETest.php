<?php

declare(strict_types=1);

namespace Koersa\Tests\E2E;

use Symfony\Component\Panther\PantherTestCase;

// Real-browser register -> login, covering the JS bits BrowserKit can't (stateless CSRF, Turbo).
final class RegistrationAndLoginE2ETest extends PantherTestCase
{
    public function testAVisitorRegistersThenSignsIn(): void
    {
        $client = static::createPantherClient();
        $email = \sprintf('e2e-%s@example.com', uniqid());
        $password = 'secret-password';

        // Pin the UI to English so the assertions below are locale-stable.
        $client->request('GET', '/locale/en');

        // Register, leaving the organization blank (defaults to "Personal").
        $client->request('GET', '/register');
        $client->submitForm('Register', [
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => $password,
            'registration_form[plainPassword][second]' => $password,
        ]);

        $client->waitForVisibility('input[name="_password"]');
        self::assertSelectorTextContains('h1', 'Sign in');

        $client->submitForm('Sign in', [
            '_username' => $email,
            '_password' => $password,
        ]);

        $client->waitForVisibility('a[href="/logout"]');
        self::assertSelectorTextContains('header', 'Log out');
    }
}
