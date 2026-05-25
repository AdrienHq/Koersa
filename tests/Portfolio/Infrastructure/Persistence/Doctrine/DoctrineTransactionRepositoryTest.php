<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Portfolio\Infrastructure\Persistence\Doctrine\DoctrineTransactionRepository;
use Koersa\Portfolio\Infrastructure\Persistence\Doctrine\TransactionMapper;
use Koersa\Shared\Domain\Uuid;
use Koersa\Tests\Support\DatabaseTestCase;

final class DoctrineTransactionRepositoryTest extends DatabaseTestCase
{
    private DoctrineTransactionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DoctrineTransactionRepository($this->entityManager, new TransactionMapper());
    }

    public function testSavesAndReturnsAnOrganizationsTransactionsNewestFirst(): void
    {
        $organizationId = Uuid::generate();

        $this->repository->save($this->transaction($organizationId, 'BTC', new DateTimeImmutable('2026-05-24 10:00')));
        $this->repository->save($this->transaction($organizationId, 'ETH', new DateTimeImmutable('2026-05-25 10:00')));
        // Another organization's transaction must not leak in.
        $this->repository->save($this->transaction(Uuid::generate(), 'XRP', new DateTimeImmutable()));
        $this->entityManager->clear();

        $transactions = $this->repository->forOrganization($organizationId);

        self::assertCount(2, $transactions);
        self::assertSame('ETH', $transactions[0]->asset);
        self::assertSame('BTC', $transactions[1]->asset);
    }

    public function testReturnsEmptyForAnOrganizationWithoutTransactions(): void
    {
        self::assertSame([], $this->repository->forOrganization(Uuid::generate()));
    }

    private function transaction(Uuid $organizationId, string $asset, DateTimeImmutable $occurredAt): Transaction
    {
        return Transaction::record(Uuid::generate(), $organizationId, $asset, Side::Buy, '1', '100', '0', $occurredAt);
    }
}
