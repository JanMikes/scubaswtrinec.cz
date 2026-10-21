<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Every table of the 2014 schema carries the same audit + flag columns.
 * Rows are never physically deleted: `del_flag = 1` hides them (soft delete),
 * `active_flag = 0` keeps them in the admin but off the public site.
 */
#[ORM\MappedSuperclass]
abstract class LegacyRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    protected ?int $id = null;

    #[ORM\Column(name: 'ins_dt', type: 'datetime_immutable')]
    public \DateTimeImmutable $insDt;

    #[ORM\Column(name: 'ins_process', length: 255, nullable: true)]
    public ?string $insProcess = null;

    #[ORM\Column(name: 'ins_user_id', length: 100, nullable: true)]
    public ?string $insUserId = null;

    /** Maintained by MariaDB itself (TIMESTAMP ... ON UPDATE current_timestamp()). */
    #[ORM\Column(name: 'upd_dt', type: 'datetime_immutable', insertable: false, updatable: false, options: ['default' => 'CURRENT_TIMESTAMP'])]
    public \DateTimeImmutable $updDt;

    #[ORM\Column(name: 'upd_process', length: 255, nullable: true)]
    public ?string $updProcess = null;

    #[ORM\Column(name: 'upd_user_id', length: 100, nullable: true)]
    public ?string $updUserId = null;

    #[ORM\Column(name: 'active_flag', type: 'boolean', options: ['unsigned' => true, 'default' => 1])]
    public bool $active = true;

    #[ORM\Column(name: 'del_flag', type: 'boolean', options: ['unsigned' => true, 'default' => 0])]
    public bool $deleted = false;

    public function __construct()
    {
        $this->insDt = new \DateTimeImmutable();
        $this->updDt = $this->insDt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isVisible(): bool
    {
        return $this->active && !$this->deleted;
    }
}
