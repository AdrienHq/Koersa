<?php

declare(strict_types=1);

namespace Koersa\Shared\UI\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function __invoke(Request $request): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('overview');
        }

        $locale = $request->getPreferredLanguage(['fr', 'nl', 'en']) ?? 'fr';

        return $this->redirectToRoute('landing', ['_locale' => $locale]);
    }
}
