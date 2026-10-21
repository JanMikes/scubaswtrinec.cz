<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Actuality;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends LegacyRepository<Actuality>
 */
class ActualityRepository extends LegacyRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actuality::class);
    }
}
