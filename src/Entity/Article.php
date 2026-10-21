<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'article')]
class Article extends Post
{
    /** @var Collection<int, ArticlePhoto> */
    #[ORM\OneToMany(targetEntity: ArticlePhoto::class, mappedBy: 'article')]
    #[ORM\OrderBy(['order' => \SortDirection::Descending])]
    private Collection $photos;

    public function __construct()
    {
        parent::__construct();
        $this->photos = new ArrayCollection();
    }

    /** @return Collection<int, ArticlePhoto> */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }
}
