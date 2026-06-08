<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\UI;

use Doctrine\ORM\EntityManagerInterface;
use Koersa\IAM\Domain\UserRepository;
use Koersa\IAM\Domain\ValueObject\Email;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegistrationAndLoginTest extends WebTestCase
{
    public function testAVisitorCanRegisterThenSignIn(): void
    {
        $client = static::createClient(server: ['HTTP_ACCEPT_LANGUAGE' => 'en']);
        $this->clearIamTables();

        $this->register($client, 'jane@example.com');
        self::assertResponseRedirects('/login');

        $users = static::getContainer()->get(UserRepository::class);
        self::assertNotNull($users->byEmail(new Email('jane@example.com')));

        $client->followRedirect();
        $client->submitForm('Sign in', [
            '_username' => 'jane@example.com',
            '_password' => 'secret-password',
        ]);
        self::assertResponseRedirects('/overview');
    }

    public function testRegisteringWithoutAnOrganizationSucceeds(): void
    {
        $client = static::createClient(server: ['HTTP_ACCEPT_LANGUAGE' => 'en']);
        $this->clearIamTables();

        $client->request('GET', '/register');
        $client->submitForm('Register', [
            'registration_form[email]' => 'solo@example.com',
            'registration_form[organizationName]' => '',
            'registration_form[plainPassword][first]' => 'secret-password',
            'registration_form[plainPassword][second]' => 'secret-password',
        ]);

        self::assertResponseRedirects('/login');
        self::assertNotNull(
            static::getContainer()->get(UserRepository::class)->byEmail(new Email('solo@example.com')),
        );
    }

    public function testRegisteringADuplicateEmailIsRejected(): void
    {
        $client = static::createClient(server: ['HTTP_ACCEPT_LANGUAGE' => 'en']);
        $this->clearIamTables();

        $this->register($client, 'jane@example.com');
        $this->register($client, 'jane@example.com');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('already registered', (string) $client->getResponse()->getContent());
    }

    public function testFailedLoginShowsAGenericInvalidCredentialsMessage(): void
    {
        $client = static::createClient(server: ['HTTP_ACCEPT_LANGUAGE' => 'en']);
        $this->clearIamTables();

        // No such user — we still want a generic message (no user enumeration)
        // rendered as a visible banner, not the small Security-bundle string.
        $client->request('GET', '/login');
        $client->submitForm('Sign in', [
            '_username' => 'nope@example.com',
            '_password' => 'wrong',
        ]);
        $client->followRedirect();

        self::assertStringContainsString('Invalid email or password.', (string) $client->getResponse()->getContent());
        // The technical Security-bundle message must never leak through, otherwise
        // an attacker can enumerate which emails exist.
        self::assertStringNotContainsString('Username could not be found', (string) $client->getResponse()->getContent());
    }

    public function testAnInvalidRegisterSubmissionShowsTheSummaryBanner(): void
    {
        $client = static::createClient(server: ['HTTP_ACCEPT_LANGUAGE' => 'en']);
        $this->clearIamTables();

        // Mismatched passwords: binds successfully, fails the constraint —
        // exactly the path that should render the top-of-form summary banner
        // so the user sees a loud signal next to the (easy-to-miss) field errors.
        $client->request('GET', '/register');
        $client->submitForm('Register', [
            'registration_form[email]' => 'jane@example.com',
            'registration_form[organizationName]' => 'Acme',
            'registration_form[plainPassword][first]' => 'secret-password',
            'registration_form[plainPassword][second]' => 'different-password',
        ]);

        self::assertStringContainsString('Please correct the highlighted fields.', (string) $client->getResponse()->getContent());
    }

    private function register(KernelBrowser $client, string $email): void
    {
        $client->request('GET', '/register');
        $client->submitForm('Register', [
            'registration_form[email]' => $email,
            'registration_form[organizationName]' => 'Acme Corp',
            'registration_form[plainPassword][first]' => 'secret-password',
            'registration_form[plainPassword][second]' => 'secret-password',
        ]);
    }

    private function clearIamTables(): void
    {
        static::getContainer()->get(EntityManagerInterface::class)
            ->getConnection()
            ->executeStatement('TRUNCATE iam_memberships, iam_users, iam_organizations');
    }
}
