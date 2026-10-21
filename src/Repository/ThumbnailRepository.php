<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Photo;
use App\Entity\Thumbnail;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends LegacyRepository<Thumbnail>
 */
class ThumbnailRepository extends LegacyRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Thumbnail::class);
    }

    public function findFor(Photo $photo, string $typeCode): ?Thumbnail
    {
        /* @var Thumbnail|null */
        return $this->adminQb('t')
            ->join('t.type', 'tt')
            ->andWhere('t.photo = :photo')->setParameter('photo', $photo)
            ->andWhere('tt.code = :code')->setParameter('code', $typeCode)
            ->orderBy('t.id', \SortDirection::Descending)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /** @return list<Thumbnail> */
    public function allFor(Photo $photo): array
    {
        /* @var list<Thumbnail> */
        return $this->adminQb('t')->andWhere('t.photo = :photo')->setParameter('photo', $photo)->getQuery()->getResult();
    }
}
