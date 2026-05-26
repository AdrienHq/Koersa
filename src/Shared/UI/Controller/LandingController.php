<?php

declare(strict_types=1);

namespace Koersa\Shared\UI\Controller;

use Koersa\Shared\Application\SignUpForBeta;
use Koersa\Shared\UI\Form\BetaSignupData;
use Koersa\Shared\UI\Form\BetaSignupForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LandingController extends AbstractController
{
    #[Route('/{_locale}', name: 'landing', requirements: ['_locale' => 'fr|nl'], methods: ['GET', 'POST'])]
    public function __invoke(Request $request, MessageBusInterface $commandBus, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(BetaSignupForm::class, new BetaSignupData());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $commandBus->dispatch(new SignUpForBeta($data->email, $request->getLocale()));
            $this->addFlash('success', $translator->trans('landing.success'));

            return $this->redirectToRoute('landing', ['_locale' => $request->getLocale()]);
        }

        return $this->render('landing/index.html.twig', [
            'signupForm' => $form->createView(),
        ]);
    }
}
