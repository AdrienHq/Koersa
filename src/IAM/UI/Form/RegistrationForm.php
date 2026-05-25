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
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('organizationName', TextType::class, [
                'label' => 'Organization name',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat password'],
                'invalid_message' => 'The two passwords must match.',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 8, max: 4096)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RegistrationFormData::class]);
    }
}
