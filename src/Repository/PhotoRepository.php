<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Photo;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends LegacyRepository<Photo>
 */
class PhotoRepository extends LegacyRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }
}
