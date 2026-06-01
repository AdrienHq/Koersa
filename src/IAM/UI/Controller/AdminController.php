<?php

declare(strict_types=1);

namespace Koersa\IAM\UI\Controller;

use Koersa\IAM\Domain\OrganizationRepository;
use Koersa\IAM\Domain\UserRepository;
use Koersa\Shared\Domain\SignupRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Operator-only landing. /admin/* is gated to ROLE_ADMIN by security.yaml's
// access_control; the IsGranted attribute is belt-and-suspenders so a misconfig
// in security.yaml doesn't accidentally expose this controller.
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin', methods: ['GET'])]
    public function __invoke(
        UserRepository $users,
        OrganizationRepository $organizations,
        SignupRepository $signups,
    ): Response {
        return $this->render('admin/index.html.twig', [
            'userCount' => $users->count(),
            'organizationCount' => $organizations->count(),
            'signupCount' => $signups->count(),
            'recentUsers' => $users->recent(20),
            'recentOrganizations' => $organizations->recent(20),
            'recentSignups' => $signups->recent(20),
        ]);
    }
}
