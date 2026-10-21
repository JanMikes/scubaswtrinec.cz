<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Discussion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Public discussion post (and the admin's edit form, with `public: false`).
 *
 * @extends AbstractType<Discussion>
 */
final class DiscussionPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', TextType::class, [
                'label' => 'Jméno',
                'constraints' => [new Assert\NotBlank(message: 'Jméno je povinné!'), new Assert\Length(max: 50)],
                'attr' => ['maxlength' => 50, 'autocomplete' => 'name'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'constraints' => [
                    new Assert\NotBlank(message: 'E-mail je povinný!'),
                    new Assert\Email(message: 'E-mail musí být zadán ve správném formátu!'),
                    new Assert\Length(max: 50),
                ],
                'attr' => ['maxlength' => 50, 'autocomplete' => 'email'],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Předmět',
                'required' => false,
                'constraints' => [new Assert\Length(max: 50)],
                'attr' => ['maxlength' => 50],
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Vaše zpráva',
                'constraints' => [new Assert\NotBlank(message: 'Text je povinný!'), new Assert\Length(max: 5000)],
                'attr' => ['rows' => 5],
            ]);

        if ($options['public']) {
            $builder
                // Honeypot: hidden from humans by CSS, bots fill it in.
                ->add('website', TextType::class, ['mapped' => false, 'required' => false, 'label' => 'Toto pole nevyplňujte'])
                ->add('ts', HiddenType::class, ['mapped' => false]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Discussion::class,
            'public' => true,
            'record' => null,
        ]);
        $resolver->setAllowedTypes('public', 'bool');
    }
}
