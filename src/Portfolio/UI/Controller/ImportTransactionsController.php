<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use Koersa\Portfolio\Application\ImportTransactions;
use Koersa\Portfolio\Application\StatementParserRegistry;
use Koersa\Portfolio\Application\StatementReader;
use Koersa\Portfolio\UI\Form\ImportForm;
use Koersa\Portfolio\UI\Form\ImportFormData;
use Koersa\Shared\Security\HasOrganization;
use Koersa\Shared\Security\IsPaidUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ImportTransactionsController extends AbstractController
{
    #[Route('/portfolio/import', name: 'portfolio_import', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        MessageBusInterface $commandBus,
        StatementParserRegistry $parsers,
        StatementReader $reader,
        TranslatorInterface $translator,
        IsPaidUser $isPaidUser,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof HasOrganization) {
            throw $this->createAccessDeniedException();
        }

        // Paid feature (ADR 0012). Defence in depth: the button on the
        // Portfolio tab opens the paywall dialog instead of this route,
        // but a direct GET / form-POST still ends up here, so the server
        // enforces the gate too.
        if (!$isPaidUser($this->getUser())) {
            return $this->redirectToRoute('portfolio');
        }

        $form = $this->createForm(ImportForm::class, new ImportFormData());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $file = $data->file;

            if ($file instanceof UploadedFile) {
                try {
                    $contents = $reader->read($file->getPathname());
                    $trades = $parsers->parserFor($data->exchange)->parse($contents);
                    $commandBus->dispatch(new ImportTransactions($user->organizationId(), $data->exchange, $trades));
                } catch (Throwable) {
                    $this->addFlash('error', $translator->trans('portfolio.import_error'));

                    return $this->redirectToRoute('portfolio_import');
                }

                $this->addFlash('success', $translator->trans('portfolio.import_success', ['%exchange%' => ucfirst($data->exchange)]));

                return $this->redirectToRoute('portfolio');
            }
        }

        return $this->render('portfolio/import.html.twig', [
            'importForm' => $form->createView(),
        ]);
    }
}
