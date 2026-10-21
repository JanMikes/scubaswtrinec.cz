<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'lst_thumbnail_tp')]
class ThumbnailType extends LegacyRecord
{
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $name = null;

    #[ORM\Column(type: 'text', length: 16777215, nullable: true)]
    public ?string $description = null;

    #[ORM\Column(nullable: true, options: ['unsigned' => true])]
    public ?int $width = null;

    #[ORM\Column(nullable: true, options: ['unsigned' => true])]
    public ?int $height = null;
}
