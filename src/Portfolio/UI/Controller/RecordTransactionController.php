<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use DateTimeImmutable;
use Koersa\Portfolio\Application\RecordTransaction;
use Koersa\Portfolio\UI\Form\TransactionForm;
use Koersa\Portfolio\UI\Form\TransactionFormData;
use Koersa\Shared\Security\HasOrganization;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class RecordTransactionController extends AbstractController
{
    #[Route('/portfolio/transactions/new', name: 'portfolio_transaction_new', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, MessageBusInterface $commandBus, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        if (!$user instanceof HasOrganization) {
            throw $this->createAccessDeniedException();
        }

        $data = new TransactionFormData();
        $data->occurredAt = new DateTimeImmutable();
        $form = $this->createForm(TransactionForm::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandBus->dispatch(new RecordTransaction(
                $user->organizationId(),
                $data->asset,
                $data->side,
                $data->quantity,
                $data->price,
                $data->fee,
                $data->occurredAt ?? new DateTimeImmutable(),
            ));
            $this->addFlash('success', $translator->trans('portfolio.record_success'));

            return $this->redirectToRoute('portfolio');
        }

        return $this->render('portfolio/record.html.twig', [
            'transactionForm' => $form->createView(),
        ]);
    }
}
