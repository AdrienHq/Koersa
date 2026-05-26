<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use Koersa\Portfolio\Application\RemoveTransaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Security\HasOrganization;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class RemoveTransactionController extends AbstractController
{
    #[Route('/portfolio/transactions/{id}/remove', name: 'portfolio_transaction_remove', methods: ['POST'])]
    public function __invoke(
        string $id,
        Request $request,
        MessageBusInterface $commandBus,
        TransactionRepository $transactions,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof HasOrganization) {
            throw $this->createAccessDeniedException();
        }
        $organizationId = $user->organizationId();

        $transactionId = Uuid::fromString($id);
        $transaction = $transactions->find($transactionId);
        if (null === $transaction || !$transaction->organizationId->equals($organizationId)) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()->setMethod('POST')->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandBus->dispatch(new RemoveTransaction($organizationId, $transactionId));
            $this->addFlash('success', 'Transaction removed.');
        }

        return $this->redirectToRoute('portfolio');
    }
}
