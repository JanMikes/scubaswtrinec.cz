<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'gallery_photo')]
#[ORM\Index(name: 'gallery_id', columns: ['gallery_id'])]
#[ORM\Index(name: 'photo_id', columns: ['photo_id'])]
class GalleryPhoto extends PostPhoto
{
    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(name: 'gallery_id', nullable: true)]
    public ?Gallery $gallery = null;

    public function getParent(): Gallery
    {
        \assert(null !== $this->gallery);

        return $this->gallery;
    }
}
