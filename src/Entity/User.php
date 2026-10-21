<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Admin account. `password` holds either the 2014 hash (md5 with static salts, 32 hex chars —
 * verified by App\Security\LegacyMd5PasswordHasher) or, after the first login on the new app,
 * a modern hash (transparently upgraded, see security.yaml `migrate_from`).
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User extends LegacyRecord implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(length: 100, nullable: true)]
    public ?string $name = null;

    #[ORM\Column(length: 100, nullable: true)]
    public ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $password = null;

    public function getUserIdentifier(): string
    {
        \assert(null !== $this->email && '' !== $this->email);

        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
    }
}
