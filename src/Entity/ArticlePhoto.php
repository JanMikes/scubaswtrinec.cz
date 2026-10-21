<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'article_photo')]
#[ORM\Index(name: 'article_id', columns: ['article_id'])]
#[ORM\Index(name: 'photo_id', columns: ['photo_id'])]
class ArticlePhoto extends PostPhoto
{
    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(name: 'article_id', nullable: true)]
    public ?Article $article = null;

    public function getParent(): Article
    {
        \assert(null !== $this->article);

        return $this->article;
    }
}
