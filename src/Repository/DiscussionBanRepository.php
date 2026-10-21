<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DiscussionBan;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends LegacyRepository<DiscussionBan>
 */
class DiscussionBanRepository extends LegacyRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscussionBan::class);
    }

    public function isBanned(?string $ip): bool
    {
        if (null === $ip || '' === $ip) {
            return false;
        }

        return (int) $this->adminQb('b')->select('COUNT(b.id)')->andWhere('b.ip = :ip')->setParameter('ip', $ip)
            ->getQuery()->getSingleScalarResult() > 0;
    }

    /** @return list<string> */
    public function bannedIps(): array
    {
        /* @var list<string> */
        return array_values(array_filter(array_map(
            static fn (DiscussionBan $b): string => (string) $b->ip,
            $this->adminQb('b')->getQuery()->getResult(),
        )));
    }
}
