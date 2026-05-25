<?php

declare(strict_types=1);

namespace Koersa\Tests\E2E;

use Symfony\Component\Panther\PantherTestCase;

/**
 * Real-browser (headless Chrome) coverage of the register -> login flow. This
 * exercises the JS layer that BrowserKit can't: stateless CSRF token injection
 * and Turbo. A unique email per run avoids cross-run database cleanup.
 */
final class RegistrationAndLoginE2ETest extends PantherTestCase
{
    public function testAVisitorRegistersThenSignsIn(): void
    {
        $client = static::createPantherClient();
        $email = \sprintf('e2e-%s@example.com', uniqid());
        $password = 'secret-password';

        // Register, leaving the organization blank (defaults to "Personal").
        $client->request('GET', '/register');
        $client->submitForm('Register', [
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => $password,
            'registration_form[plainPassword][second]' => $password,
        ]);

        // Registration succeeded and redirected to the login page.
        $client->waitForVisibility('input[name="_password"]');
        self::assertSelectorTextContains('h1', 'Sign in');

        // Sign in with the new credentials.
        $client->submitForm('Sign in', [
            '_username' => $email,
            '_password' => $password,
        ]);

        // Authenticated: the navigation now shows the log-out link.
        $client->waitForVisibility('a[href="/logout"]');
        self::assertSelectorTextContains('header', 'Log out');
    }
}
