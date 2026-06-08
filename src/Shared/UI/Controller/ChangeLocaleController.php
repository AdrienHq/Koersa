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
        if (null === $referer || '' === $referer) {
            return new RedirectResponse($this->generateUrl('home'));
        }

        // The landing route bakes the locale into the URL (/{_locale}), so a
        // plain redirect back to Referer would re-trigger the old locale on
        // the next request. Rewrite the prefix when present.
        $rewritten = preg_replace('#^(https?://[^/]+)?/(fr|nl|en)(/|$)#', '$1/'.$_locale.'$3', $referer, 1);

        return new RedirectResponse($rewritten ?? $referer);
    }
}
