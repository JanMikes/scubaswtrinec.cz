<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DiscussionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DiscussionRepository::class)]
#[ORM\Table(name: 'discussion')]
class Discussion extends LegacyRecord
{
    #[ORM\Column(length: 45, nullable: true)]
    public ?string $ip = null;

    #[ORM\Column(length: 50, nullable: true)]
    public ?string $author = null;

    #[ORM\Column(length: 50, nullable: true)]
    public ?string $subject = null;

    #[ORM\Column(length: 50, nullable: true)]
    public ?string $email = null;

    #[ORM\Column(type: 'text', length: 16777215, nullable: true)]
    public ?string $text = null;

    /** User agent of the poster. */
    #[ORM\Column(name: 'additional_info', type: 'text', length: 16777215, nullable: true)]
    public ?string $additionalInfo = null;
}
