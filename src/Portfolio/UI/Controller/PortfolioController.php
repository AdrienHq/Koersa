<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use DateTimeImmutable;
use Koersa\Portfolio\Application\Query\GetHoldings;
use Koersa\Portfolio\Application\RecordTransaction;
use Koersa\Portfolio\Application\RecordTransactionHandler;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\UI\Form\TransactionForm;
use Koersa\Portfolio\UI\Form\TransactionFormData;
use Koersa\Shared\Security\HasOrganization;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class PortfolioController extends AbstractController
{
    #[Route('/portfolio', name: 'portfolio', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        RecordTransactionHandler $recordTransaction,
        GetHoldings $getHoldings,
        TransactionRepository $transactions,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof HasOrganization) {
            throw $this->createAccessDeniedException();
        }
        $organizationId = $user->organizationId();

        $data = new TransactionFormData();
        $data->occurredAt = new DateTimeImmutable();
        $form = $this->createForm(TransactionForm::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            ($recordTransaction)(new RecordTransaction(
                $organizationId,
                $data->asset,
                $data->side,
                $data->quantity,
                $data->price,
                $data->fee,
                $data->occurredAt ?? new DateTimeImmutable(),
            ));
            $this->addFlash('success', 'Transaction recorded.');

            return $this->redirectToRoute('portfolio');
        }

        return $this->render('portfolio/index.html.twig', [
            'holdings' => ($getHoldings)($organizationId),
            'transactions' => $transactions->forOrganization($organizationId),
            'transactionForm' => $form->createView(),
        ]);
    }
}
