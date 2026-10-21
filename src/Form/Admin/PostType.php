<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Aktualita / Článek: date, title, rich text (all required, as in 2014).
 *
 * @extends AbstractType<Post>
 */
final class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Datum',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [new Assert\NotNull(message: 'Datum je povinný údaj!')],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nadpis',
                'constraints' => [new Assert\NotBlank(message: 'Nadpis je povinný!'), new Assert\Length(max: 255)],
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Text',
                'attr' => ['data-editor' => 'rich', 'rows' => 14],
                'constraints' => [new Assert\NotBlank(message: 'Text je povinný!')],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Post::class, 'record' => null]);
    }
}
