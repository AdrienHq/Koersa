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
                'label' => 'form.import.exchange_label',
                'choices' => ['Kraken' => 'kraken', 'Binance' => 'binance'],
                'choice_translation_domain' => false,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('file', FileType::class, [
                'label' => 'form.import.file_label',
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
