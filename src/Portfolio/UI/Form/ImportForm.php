<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<ImportFormData>
 */
final class ImportForm extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('exchange', ChoiceType::class, [
                'choices' => ['Kraken' => 'kraken', 'Binance' => 'binance'],
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('file', FileType::class, [
                'label' => 'CSV or ZIP export',
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\File(maxSize: '5M'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ImportFormData::class]);
    }
}
