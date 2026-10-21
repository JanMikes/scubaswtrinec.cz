<?php

declare(strict_types=1);

namespace App\Form\Admin;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** The unmapped image upload used by every admin form (JPG, PNG, GIF or WebP, up to 25 MB each). */
final class ImageField
{
    /** @param FormBuilderInterface<mixed> $builder */
    public static function add(FormBuilderInterface $builder, string $name, string $label, bool $required = false, bool $multiple = false, ?string $help = null): void
    {
        $image = new Assert\Image(
            maxSize: '25M',
            mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            mimeTypesMessage: 'Obrázek musí být ve formátu JPG, PNG, GIF nebo WebP!',
        );
        $constraints = $multiple ? [new Assert\All([$image])] : [$image];
        if ($required) {
            $constraints[] = new Assert\NotBlank(message: $multiple ? 'Vyberte alespoň jednu fotografii.' : 'Vyberte obrázek.');
            if ($multiple) {
                $constraints[] = new Assert\Count(min: 1, minMessage: 'Vyberte alespoň jednu fotografii.');
            }
        }

        $builder->add($name, FileType::class, [
            'label' => $label,
            'mapped' => false,
            'required' => $required,
            'multiple' => $multiple,
            'help' => $help,
            'constraints' => $constraints,
            'attr' => ['accept' => 'image/jpeg,image/png,image/gif,image/webp'],
        ]);
    }
}
