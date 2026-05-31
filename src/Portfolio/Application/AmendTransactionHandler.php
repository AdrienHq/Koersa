<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use Koersa\Portfolio\Domain\PortfolioId;
use Koersa\Portfolio\Domain\PortfolioRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AmendTransactionHandler
{
    public function __construct(private readonly PortfolioRepository $portfolios)
    {
    }

    public function __invoke(AmendTransaction $command): void
    {
        $portfolio = $this->portfolios->get(PortfolioId::forOrganization($command->organizationId));

        $portfolio->amendTransaction(
            $command->transactionId,
            $command->organizationId,
            $command->asset,
            $command->side,
            $command->quantity,
            $command->price,
            $command->fee,
            $command->occurredAt,
            priceCurrency: $command->priceCurrency,
            feeCurrency: $command->feeCurrency,
        );

        $this->portfolios->save($portfolio);
    }
}
