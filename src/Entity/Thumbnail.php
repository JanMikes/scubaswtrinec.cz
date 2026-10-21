<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ThumbnailRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ThumbnailRepository::class)]
#[ORM\Table(name: 'thumbnail')]
#[ORM\Index(name: 'type_id', columns: ['type_id'])]
#[ORM\Index(name: 'photo_id', columns: ['photo_id'])]
class Thumbnail extends LegacyRecord
{
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'type_id', nullable: true)]
    public ?ThumbnailType $type = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'photo_id', nullable: true)]
    public ?Photo $photo = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $filename = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $path = null;

    #[ORM\Column(nullable: true, options: ['unsigned' => true])]
    public ?int $width = null;

    #[ORM\Column(nullable: true, options: ['unsigned' => true])]
    public ?int $height = null;

    public function relativePath(): string
    {
        return trim((string) $this->path, '/').'/'.$this->filename;
    }
}
