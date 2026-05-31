<?php

declare(strict_types=1);

namespace Koersa\IAM\UI\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<RegistrationFormData>
 */
final class RegistrationForm extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'form.registration.email_label',
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('organizationName', TextType::class, [
                'label' => 'form.registration.organization_label',
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'form.registration.password_label'],
                'second_options' => ['label' => 'form.registration.password_repeat_label'],
                'invalid_message' => 'form.registration.password_mismatch',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 8, max: 4096)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RegistrationFormData::class]);
    }
}
