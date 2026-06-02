<?php

declare(strict_types=1);

namespace Koersa\Shared\Infrastructure\Twig;

use Koersa\Shared\Security\IsPaidUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

// Exposes is_paid_user(app.user) to templates. The Tax tab content, the PDF
// download button and the CSV import button all branch on this.
final class PaywallExtension extends AbstractExtension
{
    public function __construct(private readonly IsPaidUser $isPaidUser)
    {
    }

    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_paid_user', fn (?UserInterface $user): bool => ($this->isPaidUser)($user)),
        ];
    }
}
