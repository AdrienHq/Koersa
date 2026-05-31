<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use Koersa\Portfolio\Domain\PortfolioId;
use Koersa\Portfolio\Domain\PortfolioRepository;
use Koersa\Shared\Domain\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ImportTransactionsHandler
{
    public function __construct(private readonly PortfolioRepository $portfolios)
    {
    }

    public function __invoke(ImportTransactions $command): void
    {
        $portfolio = $this->portfolios->get(PortfolioId::forOrganization($command->organizationId));

        foreach ($command->trades as $trade) {
            $portfolio->recordTransaction(
                Uuid::generate(),
                $command->organizationId,
                $trade->asset,
                $trade->side,
                $trade->quantity,
                $trade->price,
                $trade->fee,
                $trade->occurredAt,
                $command->source,
                $trade->externalId,
                $trade->priceCurrency,
                $trade->feeCurrency,
            );
        }

        $this->portfolios->save($portfolio);
    }
}
