<?php

declare(strict_types=1);

namespace Koersa\Reporting\UI\Controller;

use DateTimeImmutable;
use DateTimeZone;
use Koersa\Portfolio\Application\Query\GetRealizedGains;
use Koersa\Reporting\Application\PdfRenderer;
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

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class TaxReportPdfController extends AbstractController
{
    #[Route('/tax/report.pdf', name: 'tax_report_pdf', methods: ['GET'])]
    public function __invoke(
        GetRealizedGains $getRealizedGains,
        BelgianTaxEstimator $taxEstimator,
        BelgianBoxMapper $boxMapper,
        PdfRenderer $pdfRenderer,
        IsPaidUser $isPaidUser,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof HasOrganization) {
            throw $this->createAccessDeniedException();
        }

        // Paid feature (ADR 0012). Defence in depth: the Tax page hides
        // the download button for free users, but a direct GET still ends
        // up here, so the server enforces the gate too.
        if (!$isPaidUser($this->getUser())) {
            return $this->redirectToRoute('tax');
        }

        $organizationId = $user->organizationId();

        [$report, $year] = $getRealizedGains->forMostRecentYear($organizationId);
        if (null === $report || null === $year) {
            // No report yet -> nothing to PDF. The Tax page guards the link,
            // but a manual GET still ends up here; redirect gracefully.
            return $this->redirectToRoute('tax');
        }

        $taxEstimates = $taxEstimator->estimate($report->totalGainEur);
        $filingGuidances = $this->buildFilingGuidances($boxMapper, $report->totalGainEur, $year);

        $html = $this->renderView('reporting/tax_report.html.twig', [
            'report' => $report,
            'year' => $year,
            'taxEstimates' => $taxEstimates,
            'filingGuidances' => $filingGuidances,
            'realizedGainIsLoss' => $report->totalGainEur->isNegative(),
            'accountEmail' => $user->getUserIdentifier(),
            'generatedAt' => new DateTimeImmutable('now', new DateTimeZone('UTC')),
        ]);

        $pdf = $pdfRenderer->render($html);

        $response = new Response($pdf);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', \sprintf('attachment; filename="koersa-tax-report-%d.pdf"', $year));

        return $response;
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
