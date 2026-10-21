<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Link;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends LegacyRepository<Link>
 */
class LinkRepository extends LegacyRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Link::class);
    }
}
