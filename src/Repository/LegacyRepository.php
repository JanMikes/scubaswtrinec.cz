<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LegacyRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @template T of LegacyRecord
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class LegacyRepository extends ServiceEntityRepository
{
    /** Public site: active and not deleted. */
    public function visibleQb(string $alias = 'r'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->andWhere($alias.'.deleted = false')
            ->andWhere($alias.'.active = true');
    }

    /** Admin: everything that is not soft-deleted. */
    public function adminQb(string $alias = 'r'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)->andWhere($alias.'.deleted = false');
    }

    /** @return T|null */
    public function findVisible(int $id): ?LegacyRecord
    {
        /* @var T|null */
        return $this->visibleQb()->andWhere('r.id = :id')->setParameter('id', $id)->getQuery()->getOneOrNullResult();
    }

    /** @return T|null */
    public function findForAdmin(int $id): ?LegacyRecord
    {
        /* @var T|null */
        return $this->adminQb()->andWhere('r.id = :id')->setParameter('id', $id)->getQuery()->getOneOrNullResult();
    }
}
