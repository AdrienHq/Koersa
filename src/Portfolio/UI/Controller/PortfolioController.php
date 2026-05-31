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

        return $this->render('portfolio/index.html.twig', [
            'holdings' => ($getHoldings)($organizationId),
            'transactions' => $transactions->forOrganization($organizationId),
        ]);
    }
}
