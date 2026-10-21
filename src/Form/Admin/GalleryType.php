<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Gallery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<Gallery>
 */
final class GalleryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Název galerie',
                'constraints' => [new Assert\NotBlank(message: 'Název je povinný!'), new Assert\Length(max: 50)],
                'attr' => ['maxlength' => 50],
            ])
            ->add('description', TextareaType::class, ['label' => 'Popis', 'required' => false, 'attr' => ['rows' => 4]]);
        ImageField::add($builder, 'image', 'Titulní fotografie', help: 'Nahráním nového obrázku nahradíte stávající.');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Gallery::class, 'record' => null]);
    }
}
