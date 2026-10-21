<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\PostPhoto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A photo of an aktualita / článek / galerie. Adding accepts several files at once (each becomes
 * its own record with the same description); editing replaces the image of one record.
 *
 * @extends AbstractType<PostPhoto>
 */
final class PhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = null === $options['record'];
        $builder->add('description', TextType::class, [
            'label' => 'Popis fotky',
            'required' => false,
            'constraints' => [new Assert\Length(max: 100)],
            'attr' => ['maxlength' => 100],
        ]);
        if ($isNew) {
            ImageField::add($builder, 'images', 'Fotografie', required: true, multiple: true, help: 'Můžete vybrat více souborů najednou.');
        } else {
            ImageField::add($builder, 'image', 'Nahradit fotografii', help: 'Nechte prázdné, pokud fotografii neměníte.');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PostPhoto::class, 'record' => null]);
    }
}
