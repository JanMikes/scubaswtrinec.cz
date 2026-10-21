<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait OrderableTrait
{
    #[ORM\Column(name: '`order`', nullable: true, options: ['unsigned' => true])]
    private ?int $order = null;

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(?int $order): void
    {
        $this->order = $order;
    }
}
