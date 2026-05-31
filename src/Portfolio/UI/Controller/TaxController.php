<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use Koersa\Portfolio\Application\Query\GetRealizedGains;
use Koersa\Shared\Application\Tax\BelgianTaxEstimator;
use Koersa\Shared\Security\HasOrganization;
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
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof HasOrganization) {
            throw $this->createAccessDeniedException();
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

        return $this->render('tax/index.html.twig', [
            'realizedGainsYear' => $year,
            'realizedGains' => $report,
            'taxEstimates' => $taxEstimates,
            'realizedGainIsLoss' => null !== $report && $report->totalGainEur->isNegative(),
        ]);
    }
}
