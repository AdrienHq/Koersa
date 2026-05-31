<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use DateTimeImmutable;
use Koersa\Portfolio\Application\Query\GetHoldings;
use Koersa\Portfolio\Application\Query\GetRealizedGains;
use Koersa\Portfolio\Application\Query\RealizedGainsReport;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Application\Tax\BelgianTaxEstimator;
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

        // Pick the most recent calendar year that had a sell — matches how
        // Belgian tax review actually happens (last year's activity is what
        // you file in May/June). The ECB fetch can fail; let the page render
        // without the report in that case.
        [$report, $reportYear] = $this->computeRealizedGains($getRealizedGains, $organizationId, $allTransactions);

        // Three regime scenarios — never picks one (ADR 0007). Hidden when no report.
        $taxEstimates = null !== $report ? $taxEstimator->estimate($report->totalGainEur) : null;

        return $this->render('portfolio/index.html.twig', [
            'holdings' => $holdings,
            'transactions' => $allTransactions,
            'portfolioValueEur' => $hasPrices ? number_format($totalEur, 2, '.', '') : null,
            'realizedGainsYear' => $reportYear,
            'realizedGains' => $report,
            'taxEstimates' => $taxEstimates,
            'realizedGainIsLoss' => null !== $report && $report->totalGainEur->isNegative(),
        ]);
    }

    /**
     * @param list<Transaction> $transactions
     *
     * @return array{0: ?RealizedGainsReport, 1: ?int}
     */
    private function computeRealizedGains(GetRealizedGains $getRealizedGains, Uuid $organizationId, array $transactions): array
    {
        $year = $this->mostRecentSellYear($transactions);
        if (null === $year) {
            return [null, null];
        }

        try {
            $since = new DateTimeImmutable($year.'-01-01T00:00:00+00:00');

            return [($getRealizedGains)($organizationId, $since), $year];
        } catch (Throwable) {
            return [null, null];
        }
    }

    /**
     * @param list<Transaction> $transactions
     */
    private function mostRecentSellYear(array $transactions): ?int
    {
        $best = null;
        foreach ($transactions as $transaction) {
            if (Side::Sell !== $transaction->side) {
                continue;
            }
            $year = (int) $transaction->occurredAt->format('Y');
            if (null === $best || $year > $best) {
                $best = $year;
            }
        }

        return $best;
    }
}
