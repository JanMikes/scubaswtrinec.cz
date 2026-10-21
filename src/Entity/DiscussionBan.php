<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DiscussionBanRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DiscussionBanRepository::class)]
#[ORM\Table(name: 'discussion_ban')]
class DiscussionBan extends LegacyRecord
{
    #[ORM\Column(length: 45, nullable: true, options: ['default' => ''])]
    public ?string $ip = '';

    #[ORM\Column(type: 'text', length: 16777215, nullable: true)]
    public ?string $reason = null;
}
