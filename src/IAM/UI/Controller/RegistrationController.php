<?php

declare(strict_types=1);

namespace Koersa\IAM\UI\Controller;

use Koersa\IAM\Application\EmailAlreadyInUse;
use Koersa\IAM\Application\RegisterUser;
use Koersa\IAM\Application\RegisterUserHandler;
use Koersa\IAM\UI\Form\RegistrationForm;
use Koersa\IAM\UI\Form\RegistrationFormData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, RegisterUserHandler $registerUser, TranslatorInterface $translator): Response
    {
        $data = new RegistrationFormData();
        $form = $this->createForm(RegistrationForm::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                ($registerUser)(new RegisterUser($data->email, $data->plainPassword, $data->organizationName));
                $this->addFlash('success', $translator->trans('auth.register_success_flash'));

                return $this->redirectToRoute('login');
            } catch (EmailAlreadyInUse) {
                $form->get('email')->addError(new FormError($translator->trans('auth.register_email_in_use')));
            }
        }

        return $this->render('security/register.html.twig', ['registrationForm' => $form->createView()]);
    }
}
