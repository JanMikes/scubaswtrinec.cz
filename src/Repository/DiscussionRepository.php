<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Discussion;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends LegacyRepository<Discussion>
 */
class DiscussionRepository extends LegacyRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discussion::class);
    }
}
