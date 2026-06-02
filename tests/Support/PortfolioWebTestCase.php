<?php

declare(strict_types=1);

namespace Koersa\Tests\Support;

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
use Koersa\Portfolio\Application\RecordTransaction;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Security\IsPaidUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

// Signs in a user who owns one organization; subclasses act as that user.
abstract class PortfolioWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected Uuid $organizationId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Keep the container across requests within a test so the
        // PaidUserSwitch (and any other per-test state) survives a GET->POST
        // flow. Each test still gets a fresh kernel because of createClient.
        $this->client->disableReboot();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement(
            'TRUNCATE iam_memberships, iam_users, iam_organizations, portfolio_transactions, portfolio_event_store',
        );

        $userId = Uuid::generate();
        $this->organizationId = Uuid::generate();
        (new DoctrineUserRepository($entityManager, new UserMapper()))
            ->save(User::register($userId, new Email('jane@example.com'), 'hash', new DateTimeImmutable()));
        (new DoctrineOrganizationRepository($entityManager, new OrganizationMapper()))
            ->save(Organization::create($this->organizationId, 'Personal', new DateTimeImmutable()));
        (new DoctrineMembershipRepository($entityManager, new MembershipMapper()))
            ->save(Membership::create(Uuid::generate(), $userId, $this->organizationId, Role::Owner, new DateTimeImmutable()));

        // Reset the paywall switch so per-test state doesn't leak across the
        // shared kernel between tests.
        $this->actAsFreeUser();

        // Roles passed here MUST match what the provider would refresh into,
        // otherwise ContextListener invalidates the session on the next
        // request (token roles differ from refreshed roles -> logged out).
        $this->client->loginUser(new SecurityUser(
            'jane@example.com',
            'hash',
            (string) $this->organizationId,
            isAdmin: false,
            currentRole: Role::Owner,
        ));
    }

    // Flip the test paywall switch so paid-tier paths can be exercised.
    // Backed by config/services.yaml when@test: IsPaidUser is aliased to
    // PaidUserSwitch which holds a mutable boolean.
    protected function actAsPaidUser(): void
    {
        $switch = static::getContainer()->get(IsPaidUser::class);
        \assert($switch instanceof PaidUserSwitch);
        $switch->allow = true;
    }

    protected function actAsFreeUser(): void
    {
        $switch = static::getContainer()->get(IsPaidUser::class);
        \assert($switch instanceof PaidUserSwitch);
        $switch->allow = false;
    }

    protected function recordTransaction(string $asset = 'BTC', Side $side = Side::Buy, string $quantity = '1', string $price = '100'): Uuid
    {
        static::getContainer()->get(MessageBusInterface::class)->dispatch(
            new RecordTransaction($this->organizationId, $asset, $side, $quantity, $price, '0', new DateTimeImmutable()),
        );

        $transactions = static::getContainer()->get(TransactionRepository::class)->forOrganization($this->organizationId);
        $transaction = $transactions[0] ?? null;
        self::assertInstanceOf(Transaction::class, $transaction);

        return $transaction->id;
    }
}
