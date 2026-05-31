<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use Koersa\Portfolio\Domain\PortfolioId;
use Koersa\Portfolio\Domain\PortfolioRepository;
use Koersa\Shared\Domain\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RecordTransactionHandler
{
    public function __construct(private readonly PortfolioRepository $portfolios)
    {
    }

    public function __invoke(RecordTransaction $command): void
    {
        $portfolio = $this->portfolios->get(PortfolioId::forOrganization($command->organizationId));

        $portfolio->recordTransaction(
            Uuid::generate(),
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
