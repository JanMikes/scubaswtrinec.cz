<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends LegacyRepository<Contact>
 */
class ContactRepository extends LegacyRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /** The 2014 `getLast()`: newest non-deleted row (the active flag was not checked). */
    public function latest(): ?Contact
    {
        /* @var Contact|null */
        return $this->adminQb('c')->orderBy('c.id', 'DESC')->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }
}
