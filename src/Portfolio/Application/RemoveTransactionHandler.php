<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use Koersa\Portfolio\Domain\PortfolioId;
use Koersa\Portfolio\Domain\PortfolioRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RemoveTransactionHandler
{
    public function __construct(private readonly PortfolioRepository $portfolios)
    {
    }

    public function __invoke(RemoveTransaction $command): void
    {
        $portfolio = $this->portfolios->get(PortfolioId::forOrganization($command->organizationId));

        $portfolio->removeTransaction($command->transactionId);

        $this->portfolios->save($portfolio);
    }
}
