<?php

declare(strict_types=1);

namespace Koersa\Shared\UI\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Per ADR 0012 the landing now CTAs straight into /register — no pricing,
// no waitlist form. Authenticated visitors skip straight to the overview.
final class LandingController extends AbstractController
{
    #[Route('/{_locale}', name: 'landing', requirements: ['_locale' => 'fr|nl|en'], methods: ['GET'])]
    public function __invoke(): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('overview');
        }

        return $this->render('landing/index.html.twig');
    }
}
