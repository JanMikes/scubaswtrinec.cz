<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Shared shape of `actuality` (Aktuality) and `article` (Články). */
#[ORM\MappedSuperclass]
abstract class Post extends LegacyRecord
{
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    public ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $name = null;

    /** Admin-authored HTML (CKEditor); contains YouTube iframes, rendered raw. */
    #[ORM\Column(type: 'text', length: 16777215, nullable: true)]
    public ?string $text = null;

    /** @return iterable<PostPhoto> */
    abstract public function getPhotos(): iterable;
}
