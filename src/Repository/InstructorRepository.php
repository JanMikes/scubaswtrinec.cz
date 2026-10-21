<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Instructor;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends LegacyRepository<Instructor>
 */
class InstructorRepository extends LegacyRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Instructor::class);
    }
}
