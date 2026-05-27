<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Infrastructure\EventSourcing;

use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use Koersa\Portfolio\Domain\Portfolio;
use Koersa\Portfolio\Domain\PortfolioId;
use Koersa\Portfolio\Domain\PortfolioRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

// Adapts EventSauce's aggregate repository to the Portfolio port.
#[AsAlias(PortfolioRepository::class)]
final class EventSourcingPortfolioRepository implements PortfolioRepository
{
    /** @var EventSourcedAggregateRootRepository<Portfolio> */
    private readonly EventSourcedAggregateRootRepository $repository;

    public function __construct(MessageRepository $messages, MessageDispatcher $dispatcher)
    {
        $this->repository = new EventSourcedAggregateRootRepository(Portfolio::class, $messages, $dispatcher);
    }

    public function get(PortfolioId $id): Portfolio
    {
        return $this->repository->retrieve($id);
    }

    public function save(Portfolio $portfolio): void
    {
        $this->repository->persist($portfolio);
    }
}
