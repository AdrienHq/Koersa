<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use DateTimeImmutable;
use Koersa\Portfolio\Application\Query\GetHoldings;
use Koersa\Portfolio\Application\Query\GetRealizedGains;
use Koersa\Portfolio\Application\Query\RealizedGainsReport;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Security\HasOrganization;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class PortfolioController extends AbstractController
{
    #[Route('/portfolio', name: 'portfolio', methods: ['GET'])]
    public function __invoke(
        GetHoldings $getHoldings,
        GetRealizedGains $getRealizedGains,
        TransactionRepository $transactions,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof HasOrganization) {
            throw $this->createAccessDeniedException();
        }
        $organizationId = $user->organizationId();

        $holdings = ($getHoldings)($organizationId);

        $totalEur = 0.0;
        $hasPrices = false;
        foreach ($holdings as $holding) {
            if (null !== $holding->valueEur) {
                $totalEur += (float) $holding->valueEur;
                $hasPrices = true;
            }
        }

        // ECB fetch can fail; let the page render without the report in that case.
        $report = $this->safelyComputeRealizedGains($getRealizedGains, $organizationId);

        return $this->render('portfolio/index.html.twig', [
            'holdings' => $holdings,
            'transactions' => $transactions->forOrganization($organizationId),
            'portfolioValueEur' => $hasPrices ? number_format($totalEur, 2, '.', '') : null,
            'realizedGainsYear' => (int) (new DateTimeImmutable())->format('Y'),
            'realizedGains' => $report,
        ]);
    }

    private function safelyComputeRealizedGains(GetRealizedGains $getRealizedGains, Uuid $organizationId): ?RealizedGainsReport
    {
        try {
            $sinceJanuary = new DateTimeImmutable((new DateTimeImmutable())->format('Y').'-01-01T00:00:00+00:00');

            return ($getRealizedGains)($organizationId, $sinceJanuary);
        } catch (Throwable) {
            return null;
        }
    }
}
