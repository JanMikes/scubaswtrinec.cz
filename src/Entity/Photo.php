<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PhotoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * An uploaded original. `path` is relative to the public web root (e.g. `img/gallery/12`),
 * so the public URL is `/<path>/<filename>` — unchanged since 2014.
 */
#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[ORM\Table(name: 'photo')]
class Photo extends LegacyRecord
{
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
