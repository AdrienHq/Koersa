<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use Koersa\Portfolio\Application\Query\GetRealizedGains;
use Koersa\Shared\Application\Tax\BelgianBoxMapper;
use Koersa\Shared\Application\Tax\BelgianTaxEstimator;
use Koersa\Shared\Domain\Money;
use Koersa\Shared\Domain\Tax\FilingGuidance;
use Koersa\Shared\Domain\Tax\Regime;
use Koersa\Shared\Security\HasOrganization;
use Koersa\Shared\Security\IsPaidUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class TaxController extends AbstractController
{
    #[Route('/tax', name: 'tax', methods: ['GET'])]
    public function __invoke(
        GetRealizedGains $getRealizedGains,
        BelgianTaxEstimator $taxEstimator,
        BelgianBoxMapper $boxMapper,
        IsPaidUser $isPaidUser,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof HasOrganization) {
            throw $this->createAccessDeniedException();
        }

        // Paid feature (ADR 0012). The page still renders — with a locked
        // card explaining what the tab is and a notify-me CTA — so free
        // users discover the value rather than hitting a 403.
        if (!$isPaidUser($this->getUser())) {
            return $this->render('tax/locked.html.twig');
        }

        $organizationId = $user->organizationId();

        // ECB fetch can fail; let the page render without the report in that case.
        try {
            [$report, $year] = $getRealizedGains->forMostRecentYear($organizationId);
        } catch (Throwable) {
            $report = null;
            $year = null;
        }

        $taxEstimates = null !== $report ? $taxEstimator->estimate($report->totalGainEur) : null;
        $filingGuidances = null !== $report && null !== $year
            ? $this->buildFilingGuidances($boxMapper, $report->totalGainEur, $year)
            : null;

        return $this->render('tax/index.html.twig', [
            'realizedGainsYear' => $year,
            'realizedGains' => $report,
            'taxEstimates' => $taxEstimates,
            'realizedGainIsLoss' => null !== $report && $report->totalGainEur->isNegative(),
            'filingGuidances' => $filingGuidances,
        ]);
    }

    /**
     * @return list<FilingGuidance>
     */
    private function buildFilingGuidances(BelgianBoxMapper $mapper, Money $gainEur, int $year): array
    {
        return [
            $mapper->guide(Regime::NormalManagement, $gainEur, $year),
            $mapper->guide(Regime::Speculative, $gainEur, $year),
            $mapper->guide(Regime::Professional, $gainEur, $year),
        ];
    }
}
