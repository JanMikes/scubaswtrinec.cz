<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LinkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LinkRepository::class)]
#[ORM\Table(name: 'link')]
#[ORM\Index(name: 'photo_id', columns: ['photo_id'])]
class Link extends LegacyRecord implements OrderableInterface
{
    use OrderableTrait;

    /** Icon. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'photo_id', nullable: true)]
    public ?Photo $photo = null;

    #[ORM\Column(length: 50, nullable: true)]
    public ?string $name = null;

    #[ORM\Column(length: 255, nullable: true, options: ['default' => ''])]
    public ?string $url = '';

    #[ORM\Column(type: 'text', length: 16777215, nullable: true)]
    public ?string $description = null;
}
