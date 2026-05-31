<?php

declare(strict_types=1);

namespace Koersa\Shared\UI\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ChangeLocaleController extends AbstractController
{
    #[Route('/locale/{_locale}', name: 'change_locale', requirements: ['_locale' => 'fr|nl|en'], methods: ['GET'])]
    public function __invoke(string $_locale, Request $request): RedirectResponse
    {
        $request->getSession()->set('_locale', $_locale);

        $referer = $request->headers->get('referer');

        return new RedirectResponse($referer ?: $this->generateUrl('home'));
    }
}
