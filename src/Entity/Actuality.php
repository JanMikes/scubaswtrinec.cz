<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ActualityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActualityRepository::class)]
#[ORM\Table(name: 'actuality')]
class Actuality extends Post
{
    /** @var Collection<int, ActualityPhoto> */
    #[ORM\OneToMany(targetEntity: ActualityPhoto::class, mappedBy: 'actuality')]
    #[ORM\OrderBy(['order' => \SortDirection::Descending])]
    private Collection $photos;

    public function __construct()
    {
        parent::__construct();
        $this->photos = new ArrayCollection();
    }

    /** @return Collection<int, ActualityPhoto> */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }
}
