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
                'label' => 'form.transaction.asset_label',
                'help' => 'form.transaction.asset_help',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 12),
                    new Assert\Regex(pattern: '/^[A-Za-z0-9]+$/', message: 'form.transaction.asset_regex_error'),
                ],
            ])
            ->add('side', EnumType::class, [
                'class' => Side::class,
                'label' => 'form.transaction.side_label',
                'choice_label' => static fn (Side $side): string => 'transaction.side.'.$side->value,
                'choice_translation_domain' => 'messages',
            ])
            ->add('quantity', TextType::class, [
                'label' => 'form.transaction.quantity_label',
                'constraints' => [new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\Positive()],
            ])
            ->add('price', TextType::class, [
                'label' => 'form.transaction.price_label',
                'help' => 'form.transaction.price_help',
                'constraints' => [new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\PositiveOrZero()],
            ])
            ->add('fee', TextType::class, [
                'label' => 'form.transaction.fee_label',
                'required' => false,
                'empty_data' => '0',
                'constraints' => [new Assert\Type('numeric'), new Assert\PositiveOrZero()],
            ])
            ->add('occurredAt', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'form.transaction.occurred_at_label',
                'constraints' => [new Assert\NotNull()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TransactionFormData::class]);
    }
}
