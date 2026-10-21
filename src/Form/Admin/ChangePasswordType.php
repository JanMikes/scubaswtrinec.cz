<?php

declare(strict_types=1);

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<array{old_password: string, new_password: string}>
 */
final class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('old_password', PasswordType::class, [
                'label' => 'Současné heslo',
                'constraints' => [new Assert\NotBlank(message: 'Heslo je povinné!'), new UserPassword(message: 'Bylo zadáno chybné staré heslo!')],
                'attr' => ['autocomplete' => 'current-password'],
            ])
            ->add('new_password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Hesla se neshodují!',
                'first_options' => ['label' => 'Nové heslo', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'Nové heslo (kontrola)', 'attr' => ['autocomplete' => 'new-password']],
                'constraints' => [
                    new Assert\NotBlank(message: 'Heslo je povinné!'),
                    new Assert\Length(min: 8, max: 4096, minMessage: 'Nové heslo musí mít alespoň {{ limit }} znaků.'),
                ],
            ]);
    }
}
