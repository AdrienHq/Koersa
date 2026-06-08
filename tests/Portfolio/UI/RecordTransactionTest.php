<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\UI;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Koersa\IAM\Domain\Membership;
use Koersa\IAM\Domain\Organization;
use Koersa\IAM\Domain\User;
use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\IAM\Domain\ValueObject\Role;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\DoctrineMembershipRepository;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\DoctrineOrganizationRepository;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\MembershipMapper;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\OrganizationMapper;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\UserMapper;
use Koersa\IAM\Infrastructure\Security\SecurityUser;
use Koersa\Shared\Domain\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RecordTransactionTest extends WebTestCase
{
    public function testALoggedInUserRecordsATransactionAndSeesIt(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement(
            'TRUNCATE iam_memberships, iam_users, iam_organizations, portfolio_transactions, portfolio_event_store',
        );

        $userId = Uuid::generate();
        $organizationId = Uuid::generate();
        (new DoctrineUserRepository($entityManager, new UserMapper()))
            ->save(User::register($userId, new Email('jane@example.com'), 'hash', new DateTimeImmutable()));
        (new DoctrineOrganizationRepository($entityManager, new OrganizationMapper()))
            ->save(Organization::create($organizationId, 'Personal', new DateTimeImmutable()));
        (new DoctrineMembershipRepository($entityManager, new MembershipMapper()))
            ->save(Membership::create(Uuid::generate(), $userId, $organizationId, Role::Owner, new DateTimeImmutable()));

        $client->loginUser(new SecurityUser('jane@example.com', 'hash', (string) $organizationId, isAdmin: false, currentRole: Role::Owner));

        $crawler = $client->request('GET', '/portfolio/transactions/new');
        self::assertResponseIsSuccessful();
        // Explicit form action — the form is injected into a modal on /portfolio,
        // so an empty action would POST to the listing (405 Method Not Allowed).
        self::assertSame('/portfolio/transactions/new', $crawler->filter('form[name="transaction_form"]')->attr('action'));

        $client->submitForm('Record', [
            'transaction_form[asset]' => 'BTC',
            'transaction_form[side]' => 'buy',
            'transaction_form[quantity]' => '0.5',
            'transaction_form[price]' => '40000',
            'transaction_form[fee]' => '10',
            'transaction_form[occurredAt]' => '2026-05-25T10:00',
        ]);
        self::assertResponseRedirects('/portfolio');

        $client->followRedirect();
        self::assertSelectorTextContains('body', 'BTC');
    }
}
