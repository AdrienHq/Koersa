<?php

declare(strict_types=1);

namespace Koersa\Shared\UI\Controller;

use InvalidArgumentException;
use Koersa\Shared\Application\SignUpForBeta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

// Captures an email from inside the paywall dialog and pipes it through the
// existing SignUpForBeta command. Until Stripe lands this is the conversion
// path; afterwards it stays as the "let me know when there's news" fallback
// for users who aren't ready to subscribe today.
final class NotifyMeController extends AbstractController
{
    #[Route('/waitlist/notify', name: 'waitlist_notify', methods: ['POST'])]
    public function __invoke(Request $request, MessageBusInterface $commandBus, TranslatorInterface $translator): RedirectResponse
    {
        $email = (string) $request->request->get('email', '');
        $referer = $request->headers->get('referer');
        $fallback = $this->generateUrl('overview');

        try {
            $commandBus->dispatch(new SignUpForBeta($email, $request->getLocale()));
            $this->addFlash('success', $translator->trans('paywall.success'));
        } catch (InvalidArgumentException) {
            // Email validation lives in the Signup aggregate; surface a
            // generic message rather than echoing the raw exception text.
            $this->addFlash('error', $translator->trans('paywall.invalid_email'));
        }

        return new RedirectResponse($referer ?: $fallback);
    }
}
