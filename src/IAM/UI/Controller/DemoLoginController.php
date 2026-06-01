<?php

declare(strict_types=1);

namespace Koersa\IAM\UI\Controller;

use Koersa\IAM\Infrastructure\Security\SecurityUserProvider;
use Koersa\Shared\Security\IsDemoUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

// /demo: programmatically signs the visitor in as the public demo account
// (ADR 0011). The demo user is created by `bin/console demo:seed` — if it's
// missing the route fails clearly rather than silently.
final class DemoLoginController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly SecurityUserProvider $provider,
    ) {
    }

    #[Route('/demo', name: 'demo_login', methods: ['GET'])]
    public function __invoke(): Response
    {
        try {
            $user = $this->provider->loadUserByIdentifier(IsDemoUser::EMAIL);
        } catch (UserNotFoundException) {
            // Operator hasn't run demo:seed yet on this environment.
            throw $this->createNotFoundException('The demo account is not provisioned.');
        }

        $this->security->login($user);

        return $this->redirectToRoute('overview');
    }
}
