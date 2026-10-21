<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;

/** Append-only: every admin save inserts a row, the page shows the newest one. */
#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\Table(name: 'contact')]
class Contact extends LegacyRecord
{
    #[ORM\Column(type: 'text', length: 16777215, nullable: true)]
    public ?string $text = null;
}
