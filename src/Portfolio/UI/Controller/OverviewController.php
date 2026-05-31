<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use DateTimeImmutable;
use Koersa\Portfolio\Application\Query\GetCumulativeRealizedGainTimeline;
use Koersa\Portfolio\Application\Query\GetHoldings;
use Koersa\Portfolio\Application\Query\GetRealizedGains;
use Koersa\Portfolio\Application\Query\Holding;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
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
    private const int ACTIVITY_MONTHS = 12;

    #[Route('/overview', name: 'overview', methods: ['GET'])]
    public function __invoke(
        GetHoldings $getHoldings,
        GetRealizedGains $getRealizedGains,
        GetCumulativeRealizedGainTimeline $getTimeline,
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
            $timeline = ($getTimeline)($organizationId);
        } catch (Throwable) {
            $report = null;
            $reportYear = null;
            $timeline = [];
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
            'cumulativeGainData' => $this->castTimelineForChart($timeline),
            'holdingsCompositionData' => $this->composeHoldings($holdings),
            'monthlyActivityData' => $this->composeMonthlyActivity($allTransactions),
        ]);
    }

    /**
     * @param list<array{date: string, runningGainEur: string}> $timeline
     *
     * @return list<array{date: string, runningGainEur: float}>
     */
    private function castTimelineForChart(array $timeline): array
    {
        // Chart.js wants numeric y-values; bcmath strings would serialise as
        // JSON strings, which trip up axis scaling.
        return array_map(
            static fn (array $point): array => ['date' => $point['date'], 'runningGainEur' => (float) $point['runningGainEur']],
            $timeline,
        );
    }

    /**
     * @param list<Holding> $holdings
     *
     * @return list<array{label: string, valueEur: float}>
     */
    private function composeHoldings(array $holdings): array
    {
        $points = [];
        foreach ($holdings as $holding) {
            if (null === $holding->valueEur) {
                continue;
            }
            $points[] = ['label' => $holding->asset, 'valueEur' => (float) $holding->valueEur];
        }

        return $points;
    }

    /**
     * @param list<Transaction> $transactions
     *
     * @return list<array{month: string, buys: int, sells: int}>
     */
    private function composeMonthlyActivity(array $transactions): array
    {
        if ([] === $transactions) {
            return [];
        }

        // Build the last N months as keys so the chart always has the same
        // x-axis length regardless of how much activity there was.
        /** @var array<string, array{buys: int, sells: int}> $buckets */
        $buckets = [];
        $cursor = new DateTimeImmutable('first day of this month');
        for ($i = 0; $i < self::ACTIVITY_MONTHS; ++$i) {
            $buckets[$cursor->format('Y-m')] = ['buys' => 0, 'sells' => 0];
            $cursor = $cursor->modify('-1 month');
        }

        foreach ($transactions as $transaction) {
            $key = $transaction->occurredAt->format('Y-m');
            if (!isset($buckets[$key])) {
                continue;
            }
            if (Side::Buy === $transaction->side) {
                ++$buckets[$key]['buys'];
            } else {
                ++$buckets[$key]['sells'];
            }
        }

        // Reverse so the chart reads left-to-right oldest-to-newest.
        $points = [];
        foreach (array_reverse($buckets, preserve_keys: true) as $month => $counts) {
            $points[] = ['month' => $month, 'buys' => $counts['buys'], 'sells' => $counts['sells']];
        }

        return $points;
    }
}
