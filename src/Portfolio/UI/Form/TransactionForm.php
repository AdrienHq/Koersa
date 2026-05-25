<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Form;

use Koersa\Portfolio\Domain\ValueObject\Side;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<TransactionFormData>
 */
final class TransactionForm extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('asset', TextType::class, [
                'help' => 'Ticker, e.g. BTC',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 12),
                    new Assert\Regex(pattern: '/^[A-Za-z0-9]+$/', message: 'Use letters and digits only.'),
                ],
            ])
            ->add('side', EnumType::class, [
                'class' => Side::class,
                'choice_label' => static fn (Side $side): string => ucfirst($side->value),
            ])
            ->add('quantity', TextType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\Positive()],
            ])
            ->add('price', TextType::class, [
                'help' => 'Unit price',
                'constraints' => [new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\PositiveOrZero()],
            ])
            ->add('fee', TextType::class, [
                'required' => false,
                'empty_data' => '0',
                'constraints' => [new Assert\Type('numeric'), new Assert\PositiveOrZero()],
            ])
            ->add('occurredAt', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Occurred at',
                'constraints' => [new Assert\NotNull()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TransactionFormData::class]);
    }
}
