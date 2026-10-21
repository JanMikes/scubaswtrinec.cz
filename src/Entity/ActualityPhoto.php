<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'actuality_photo')]
#[ORM\Index(name: 'actuality_id', columns: ['actuality_id'])]
#[ORM\Index(name: 'photo_id', columns: ['photo_id'])]
class ActualityPhoto extends PostPhoto
{
    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(name: 'actuality_id', nullable: true)]
    public ?Actuality $actuality = null;

    public function getParent(): Actuality
    {
        \assert(null !== $this->actuality);

        return $this->actuality;
    }
}
