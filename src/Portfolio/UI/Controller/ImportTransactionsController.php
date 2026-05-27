<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Controller;

use Koersa\Portfolio\Application\ImportTransactions;
use Koersa\Portfolio\Application\StatementParserRegistry;
use Koersa\Portfolio\UI\Form\ImportForm;
use Koersa\Portfolio\UI\Form\ImportFormData;
use Koersa\Shared\Security\HasOrganization;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ImportTransactionsController extends AbstractController
{
    #[Route('/portfolio/import', name: 'portfolio_import', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        MessageBusInterface $commandBus,
        StatementParserRegistry $parsers,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof HasOrganization) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ImportForm::class, new ImportFormData());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $file = $data->file;

            if ($file instanceof UploadedFile) {
                try {
                    $contents = (string) file_get_contents($file->getPathname());
                    $trades = $parsers->parserFor($data->exchange)->parse($contents);
                } catch (Throwable) {
                    $this->addFlash('error', 'That file could not be read as a valid export. Please check the exchange and the file.');

                    return $this->redirectToRoute('portfolio_import');
                }

                $commandBus->dispatch(new ImportTransactions($user->organizationId(), $data->exchange, $trades));
                $this->addFlash('success', \sprintf('Imported your %s statement.', ucfirst($data->exchange)));

                return $this->redirectToRoute('portfolio');
            }
        }

        return $this->render('portfolio/import.html.twig', [
            'importForm' => $form->createView(),
        ]);
    }
}
