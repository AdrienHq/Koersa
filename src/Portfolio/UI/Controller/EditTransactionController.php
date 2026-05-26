<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use DateTimeImmutable;
use Koersa\Portfolio\Application\AmendTransaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\UI\Form\TransactionForm;
use Koersa\Portfolio\UI\Form\TransactionFormData;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Security\HasOrganization;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class EditTransactionController extends AbstractController
{
    #[Route('/portfolio/transactions/{id}/edit', name: 'portfolio_transaction_edit', methods: ['GET', 'POST'])]
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

        $data = new TransactionFormData();
        $data->asset = $transaction->asset;
        $data->side = $transaction->side;
        $data->quantity = $transaction->quantity;
        $data->price = $transaction->price;
        $data->fee = $transaction->fee;
        $data->occurredAt = $transaction->occurredAt;

        $form = $this->createForm(TransactionForm::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandBus->dispatch(new AmendTransaction(
                $organizationId,
                $transactionId,
                $data->asset,
                $data->side,
                $data->quantity,
                $data->price,
                $data->fee,
                $data->occurredAt ?? new DateTimeImmutable(),
            ));
            $this->addFlash('success', 'Transaction updated.');

            return $this->redirectToRoute('portfolio');
        }

        return $this->render('portfolio/edit.html.twig', [
            'transactionForm' => $form->createView(),
            'deleteForm' => $this->deleteForm($transactionId)->createView(),
            'transaction' => $transaction,
        ]);
    }

    /**
     * @return FormInterface<mixed>
     */
    private function deleteForm(Uuid $transactionId): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('portfolio_transaction_remove', ['id' => $transactionId->value]))
            ->setMethod('POST')
            ->getForm();
    }
}
