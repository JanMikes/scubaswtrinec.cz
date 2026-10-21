<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GalleryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GalleryRepository::class)]
#[ORM\Table(name: 'gallery')]
#[ORM\Index(name: 'photo_id', columns: ['photo_id'])]
class Gallery extends LegacyRecord implements OrderableInterface
{
    use OrderableTrait;

    /** Cover image. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'photo_id', nullable: true)]
    public ?Photo $photo = null;

    #[ORM\Column(length: 50, nullable: true)]
    public ?string $name = null;

    #[ORM\Column(type: 'text', length: 16777215, nullable: true)]
    public ?string $description = null;

    /** @var Collection<int, GalleryPhoto> */
    #[ORM\OneToMany(targetEntity: GalleryPhoto::class, mappedBy: 'gallery')]
    #[ORM\OrderBy(['order' => 'DESC'])]
    private Collection $photos;

    public function __construct()
    {
        parent::__construct();
        $this->photos = new ArrayCollection();
    }

    /** @return Collection<int, GalleryPhoto> */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }
}
