<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Infrastructure\Console;

use DateTimeImmutable;
use Koersa\Portfolio\Application\RecordTransaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Portfolio\Infrastructure\Console\RebuildProjectionsCommand;
use Koersa\Shared\Domain\Uuid;
use Koersa\Tests\Support\DatabaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\MessageBusInterface;

final class RebuildProjectionsCommandTest extends DatabaseTestCase
{
    public function testRebuildsTheTransactionsProjectionFromTheEventStore(): void
    {
        $container = self::getContainer();
        $commandBus = $container->get(MessageBusInterface::class);
        $transactions = $container->get(TransactionRepository::class);

        $organizationId = Uuid::generate();
        $commandBus->dispatch(new RecordTransaction($organizationId, 'BTC', Side::Buy, '1', '100', '0', new DateTimeImmutable()));
        $commandBus->dispatch(new RecordTransaction($organizationId, 'ETH', Side::Sell, '2', '200', '0', new DateTimeImmutable()));

        $this->entityManager->clear();
        self::assertCount(2, $transactions->forOrganization($organizationId));

        // Wipe the read model out from under the projection; the events remain.
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE portfolio_transactions');
        $this->entityManager->clear();
        self::assertCount(0, $transactions->forOrganization($organizationId));

        $tester = new CommandTester($container->get(RebuildProjectionsCommand::class));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        $this->entityManager->clear();
        self::assertCount(2, $transactions->forOrganization($organizationId));
    }
}
