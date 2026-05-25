<?php

declare(strict_types=1);

namespace Koersa\IAM\UI\Controller;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function logout(): never
    {
        // @codeCoverageIgnoreStart — never executed; the firewall's logout key intercepts this route.
        throw new LogicException('This method is intercepted by the logout key on the firewall.');
        // @codeCoverageIgnoreEnd
    }
}
