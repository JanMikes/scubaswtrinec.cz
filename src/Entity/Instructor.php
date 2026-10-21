<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InstructorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InstructorRepository::class)]
#[ORM\Table(name: 'instructor')]
#[ORM\Index(name: 'photo_id', columns: ['photo_id'])]
class Instructor extends LegacyRecord implements OrderableInterface
{
    use OrderableTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'photo_id', nullable: true)]
    public ?Photo $photo = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $name = null;

    #[ORM\Column(type: 'text', length: 16777215, nullable: true)]
    public ?string $text = null;
}
