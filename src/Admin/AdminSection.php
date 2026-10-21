<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\LegacyRecord;

/**
 * One admin section. `slug` is the 2014 Nette presenter name in URL form, so the old admin URLs
 * (`/admin/actuality-photo/edit/12?actualityId=5`) are the new ones.
 */
final readonly class AdminSection
{
    /**
     * @param class-string<LegacyRecord>                                     $entityClass
     * @param class-string<\Symfony\Component\Form\FormTypeInterface<mixed>> $formType
     * @param array<string, 'ASC'|'DESC'>                                    $listOrder
     */
    public function __construct(
        public string $slug,
        public string $entityClass,
        public string $formType,
        public string $title,
        public string $singular,
        public string $listTemplate,
        public array $listOrder,
        public bool $orderable = false,
        /** Photo sections: the parent's section slug, its query parameter and association field. */
        public ?string $parentSlug = null,
        public ?string $parentParam = null,
        public ?string $parentField = null,
        /** Upload directory under the web root; `{parent}` / `{id}` are substituted. */
        public ?string $uploadDir = null,
        /** Child photo section of a post/gallery. */
        public ?string $photosSlug = null,
        public bool $canAdd = true,
    ) {
    }

    public function hasParent(): bool
    {
        return null !== $this->parentSlug;
    }
}
