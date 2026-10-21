<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Shared shape of `actuality_photo`, `article_photo` and `gallery_photo`. */
#[ORM\MappedSuperclass]
abstract class PostPhoto extends LegacyRecord implements OrderableInterface
{
    use OrderableTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'photo_id', nullable: true)]
    public ?Photo $photo = null;

    #[ORM\Column(length: 100, nullable: true)]
    public ?string $description = null;

    abstract public function getParent(): LegacyRecord;
}
