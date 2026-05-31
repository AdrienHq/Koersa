<?php

declare(strict_types=1);

namespace Koersa\Shared\Infrastructure\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

// Picks the active locale: routes that set _locale (e.g. the landing) win and
// persist it in the session; everything else uses what's already in the session
// or falls back to the visitor's Accept-Language.
final class LocaleSubscriber implements EventSubscriberInterface
{
    private const array SUPPORTED = ['fr', 'nl', 'en'];

    public function __construct(private readonly string $defaultLocale = 'fr')
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }
        $session = $request->getSession();

        $fromRoute = $request->attributes->get('_locale');
        if (\is_string($fromRoute) && \in_array($fromRoute, self::SUPPORTED, true)) {
            $session->set('_locale', $fromRoute);

            return;
        }

        $stored = $session->get('_locale');
        if (\is_string($stored) && \in_array($stored, self::SUPPORTED, true)) {
            $request->setLocale($stored);

            return;
        }

        $preferred = $request->getPreferredLanguage(self::SUPPORTED) ?? $this->defaultLocale;
        $session->set('_locale', $preferred);
        $request->setLocale($preferred);
    }

    public static function getSubscribedEvents(): array
    {
        // After Symfony's RouterListener (priority 32) so we see what routing set.
        return [KernelEvents::REQUEST => [['onKernelRequest', 20]]];
    }
}
