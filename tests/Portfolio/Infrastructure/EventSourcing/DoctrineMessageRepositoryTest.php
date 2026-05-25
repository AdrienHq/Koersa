<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Infrastructure\EventSourcing;

use DateTimeImmutable;
use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;
use EventSauce\EventSourcing\OffsetCursor;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\Portfolio;
use Koersa\Portfolio\Domain\PortfolioId;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Portfolio\Infrastructure\EventSourcing\DoctrineMessageRepository;
use Koersa\Shared\Domain\Uuid;
use Koersa\Tests\Support\DatabaseTestCase;

final class DoctrineMessageRepositoryTest extends DatabaseTestCase
{
    private DoctrineMessageRepository $messages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messages = new DoctrineMessageRepository(
            $this->entityManager->getConnection(),
            new ConstructingMessageSerializer(),
        );
    }

    public function testPersistsAndReplaysAnAggregateStream(): void
    {
        $organizationId = Uuid::generate();
        $portfolioId = PortfolioId::forOrganization($organizationId);
        $repository = $this->aggregateRepository();

        $portfolio = $repository->retrieve($portfolioId);
        $portfolio->recordTransaction(Uuid::generate(), $organizationId, 'BTC', Side::Buy, '1', '100', '0', new DateTimeImmutable());
        $portfolio->recordTransaction(Uuid::generate(), $organizationId, 'ETH', Side::Sell, '2', '200', '0', new DateTimeImmutable());
        $repository->persist($portfolio);

        self::assertSame(2, $repository->retrieve($portfolioId)->aggregateRootVersion());

        $afterFirst = iterator_to_array($this->messages->retrieveAllAfterVersion($portfolioId, 1), false);
        self::assertCount(1, $afterFirst);

        $payload = $afterFirst[0]->payload();
        self::assertInstanceOf(TransactionRecorded::class, $payload);
        self::assertSame('ETH', $payload->asset);
    }

    public function testPaginatesAcrossTheWholeStore(): void
    {
        $organizationId = Uuid::generate();
        $repository = $this->aggregateRepository();

        $portfolio = $repository->retrieve(PortfolioId::forOrganization($organizationId));
        $portfolio->recordTransaction(Uuid::generate(), $organizationId, 'BTC', Side::Buy, '1', '100', '0', new DateTimeImmutable());
        $repository->persist($portfolio);

        $messages = iterator_to_array($this->messages->paginate(OffsetCursor::fromStart()), false);
        self::assertCount(1, $messages);
        self::assertInstanceOf(TransactionRecorded::class, $messages[0]->payload());
    }

    /**
     * @return EventSourcedAggregateRootRepository<Portfolio>
     */
    private function aggregateRepository(): EventSourcedAggregateRootRepository
    {
        return new EventSourcedAggregateRootRepository(Portfolio::class, $this->messages, new SynchronousMessageDispatcher());
    }
}
