<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Link;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<Link>
 */
final class LinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Název',
                'constraints' => [new Assert\NotBlank(message: 'Název je povinný!'), new Assert\Length(max: 50)],
                'attr' => ['maxlength' => 50],
            ])
            ->add('url', TextType::class, [
                'label' => 'Odkaz',
                'constraints' => [new Assert\NotBlank(message: 'Odkaz je povinný!'), new Assert\Length(max: 255)],
                'attr' => ['placeholder' => 'https://…'],
            ])
            ->add('description', TextareaType::class, ['label' => 'Popis', 'required' => false, 'attr' => ['rows' => 3]]);
        ImageField::add($builder, 'image', 'Ikonka', help: 'Nahráním nového obrázku nahradíte stávající.');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Link::class, 'record' => null]);
    }
}
