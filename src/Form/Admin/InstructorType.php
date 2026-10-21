<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Instructor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<Instructor>
 */
final class InstructorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Jméno',
                'constraints' => [new Assert\NotBlank(message: 'Jméno je povinné!'), new Assert\Length(max: 255)],
            ])
            ->add('text', TextareaType::class, ['label' => 'Text', 'required' => false, 'attr' => ['data-editor' => 'rich', 'rows' => 10]]);
        ImageField::add($builder, 'image', 'Fotka', help: 'Nahráním nového obrázku nahradíte stávající.');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Instructor::class, 'record' => null]);
    }
}
