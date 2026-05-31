<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use Koersa\Portfolio\Application\Query\GetHoldings;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Shared\Security\HasOrganization;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class PortfolioController extends AbstractController
{
    #[Route('/portfolio', name: 'portfolio', methods: ['GET'])]
    public function __invoke(
        GetHoldings $getHoldings,
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

        return $this->render('portfolio/index.html.twig', [
            'holdings' => $holdings,
            'transactions' => $transactions->forOrganization($organizationId),
            'portfolioValueEur' => $hasPrices ? number_format($totalEur, 2, '.', '') : null,
        ]);
    }
}
