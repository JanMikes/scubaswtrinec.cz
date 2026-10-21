<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * `?page=N` pagination with the 2014 semantics (Nette Paginator): out-of-range pages are clamped
 * to the first/last page instead of erroring.
 *
 * @template T
 */
final readonly class Pager
{
    /**
     * @param list<T> $items
     */
    private function __construct(
        public array $items,
        public int $page,
        public int $pageCount,
        public int $total,
    ) {
    }

    /**
     * @return self<mixed>
     */
    public static function fromQuery(QueryBuilder $qb, mixed $page, int $perPage): self
    {
        $countQb = clone $qb;
        $alias = $countQb->getRootAliases()[0];
        $total = (int) $countQb->select('COUNT('.$alias.'.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        $pageCount = max(1, (int) ceil($total / $perPage));
        $page = is_numeric($page) ? (int) $page : 1;
        $page = min(max(1, $page), $pageCount);

        $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);
        $items = iterator_to_array(new Paginator($qb->getQuery(), fetchJoinCollection: false), false);

        return new self($items, $page, $pageCount, $total);
    }
}
