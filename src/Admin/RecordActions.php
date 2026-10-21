<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\LegacyRecord;
use App\Entity\OrderableInterface;
use App\Entity\PostPhoto;
use Doctrine\ORM\EntityManagerInterface;

/** The 2014 list actions (TRecordHandlers): activate, deactivate, activate all, soft delete, move up/down. */
final readonly class RecordActions
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function find(AdminSection $section, int $id): ?LegacyRecord
    {
        $record = $this->em->find($section->entityClass, $id);

        return $record instanceof LegacyRecord && !$record->deleted ? $record : null;
    }

    /**
     * Non-deleted records of a section, in admin list order, optionally limited to one parent.
     *
     * @return list<LegacyRecord>
     */
    public function list(AdminSection $section, ?LegacyRecord $parent = null): array
    {
        $qb = $this->em->createQueryBuilder()->select('r')->from($section->entityClass, 'r')->andWhere('r.deleted = false');
        if (null !== $parent && null !== $section->parentField) {
            $qb->andWhere('r.'.$section->parentField.' = :parent')->setParameter('parent', $parent);
        }
        foreach ($section->listOrder as $field => $dir) {
            $qb->addOrderBy('r.'.$field, 'DESC' === $dir ? \SortDirection::Descending : \SortDirection::Ascending);
        }

        /* @var list<LegacyRecord> */
        return $qb->getQuery()->getResult();
    }

    public function setActive(LegacyRecord $record, bool $active): void
    {
        $record->active = $active;
        $this->em->flush();
    }

    public function activateAll(AdminSection $section, ?LegacyRecord $parent): int
    {
        $n = 0;
        foreach ($this->list($section, $parent) as $record) {
            if (!$record->active) {
                $record->active = true;
                ++$n;
            }
        }
        $this->em->flush();

        return $n;
    }

    public function delete(LegacyRecord $record): void
    {
        $record->deleted = true;
        $this->em->flush();
    }

    /** Swap with the neighbour above/below in the displayed (order DESC) list, within the same parent. */
    public function move(AdminSection $section, LegacyRecord $record, string $direction): bool
    {
        if (!$record instanceof OrderableInterface) {
            return false;
        }
        $parent = $record instanceof PostPhoto ? $record->getParent() : null;
        $rows = array_values(array_filter($this->list($section, $parent), static fn ($r): bool => $r instanceof OrderableInterface));

        // Normalise first when values collide or are missing (keeps the current visual order).
        $orders = array_map(static fn (OrderableInterface $r): ?int => $r->getOrder(), $rows);
        if (\in_array(null, $orders, true) || \count(array_unique($orders)) !== \count($orders)) {
            $n = \count($rows);
            foreach ($rows as $i => $r) {
                $r->setOrder(($n - $i) * 10);
            }
        }

        $index = array_search($record, $rows, true);
        if (false === $index) {
            return false;
        }
        $neighbourIndex = 'up' === $direction ? $index - 1 : $index + 1;
        if (!isset($rows[$neighbourIndex])) {
            return false;
        }
        $neighbour = $rows[$neighbourIndex];
        $tmp = $record->getOrder();
        $record->setOrder($neighbour->getOrder());
        $neighbour->setOrder($tmp);
        $this->em->flush();

        return true;
    }
}
