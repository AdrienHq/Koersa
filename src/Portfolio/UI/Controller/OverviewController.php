<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use Koersa\Portfolio\Application\Query\GetHoldings;
use Koersa\Portfolio\Application\Query\GetRealizedGains;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Shared\Application\Tax\BelgianTaxEstimator;
use Koersa\Shared\Security\HasOrganization;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class OverviewController extends AbstractController
{
    #[Route('/overview', name: 'overview', methods: ['GET'])]
    public function __invoke(
        GetHoldings $getHoldings,
        GetRealizedGains $getRealizedGains,
        TransactionRepository $transactions,
        BelgianTaxEstimator $taxEstimator,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof HasOrganization) {
            throw $this->createAccessDeniedException();
        }
        $organizationId = $user->organizationId();

        $holdings = ($getHoldings)($organizationId);
        $allTransactions = $transactions->forOrganization($organizationId);

        $totalEur = 0.0;
        $hasPrices = false;
        foreach ($holdings as $holding) {
            if (null !== $holding->valueEur) {
                $totalEur += (float) $holding->valueEur;
                $hasPrices = true;
            }
        }

        // ECB fetch can fail; let the page render without the report in that case.
        try {
            [$report, $reportYear] = $getRealizedGains->forMostRecentYear($organizationId);
        } catch (Throwable) {
            $report = null;
            $reportYear = null;
        }

        $taxEstimates = null !== $report ? $taxEstimator->estimate($report->totalGainEur) : null;

        return $this->render('overview/index.html.twig', [
            'holdings' => $holdings,
            'transactions' => $allTransactions,
            'portfolioValueEur' => $hasPrices ? number_format($totalEur, 2, '.', '') : null,
            'realizedGainsYear' => $reportYear,
            'realizedGains' => $report,
            'taxEstimates' => $taxEstimates,
            'unmatchedSellCount' => null !== $report ? $report->unmatchedSellCount : 0,
        ]);
    }
}
