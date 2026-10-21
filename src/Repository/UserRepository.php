<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends LegacyRepository<User>
 */
class UserRepository extends LegacyRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /** Same lookup as the 2014 Authenticator: by e-mail, soft-deleted rows excluded (collation is case-insensitive). */
    public function findOneByLoginEmail(string $email): ?User
    {
        /* @var User|null */
        return $this->adminQb('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', trim($email))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->findOneByLoginEmail($identifier);
    }

    /** Called by Symfony after a successful login with a legacy (or outdated) hash. */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->password = $newHashedPassword;
        $user->updProcess = __METHOD__;
        $this->getEntityManager()->flush();
    }
}
