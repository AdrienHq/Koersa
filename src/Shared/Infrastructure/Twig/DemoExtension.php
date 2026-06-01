<?php

declare(strict_types=1);

namespace Koersa\Shared\Infrastructure\Twig;

use Koersa\Shared\Security\IsDemoUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

// Exposes `is_demo_user(app.user)` to templates. The banner and the
// write-lock data attributes both branch on this in base.html.twig.
final class DemoExtension extends AbstractExtension
{
    public function __construct(private readonly IsDemoUser $isDemoUser)
    {
    }

    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_demo_user', fn (?UserInterface $user): bool => ($this->isDemoUser)($user)),
        ];
    }
}
