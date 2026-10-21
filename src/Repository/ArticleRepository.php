<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends LegacyRepository<Article>
 */
class ArticleRepository extends LegacyRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }
}
