<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Verifies the 2014 Nette app's password hashes, so existing admins keep their passwords.
 *
 * The old App\Services\HashService computed `md5($leadingSalt . $password . $trailingSalt)` with the
 * static salts from app/config/config.parameters.neon (`authentication.leadingSalt: 'scuba'`,
 * `trailingSalt: 'sw'`) and stored the 32-char lowercase hex digest in `user.password`.
 *
 * This hasher is registered ONLY as a `migrate_from` source (config/packages/security.yaml): Symfony
 * tries the modern hasher first, falls back to this one, and on success re-hashes the password with
 * the modern algorithm (UserRepository::upgradePassword). It never produces new legacy hashes.
 */
final class LegacyMd5PasswordHasher implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    private const string LEADING_SALT = 'scuba';
    private const string TRAILING_SALT = 'sw';

    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        throw new \LogicException('The legacy md5 scheme is verify-only; new hashes use the modern algorithm.');
    }

    public function verify(string $hashedPassword, #[\SensitiveParameter] string $plainPassword): bool
    {
        if ('' === $plainPassword || $this->isPasswordTooLong($plainPassword)) {
            return false;
        }

        if (1 !== preg_match('/^[0-9a-f]{32}$/', $hashedPassword)) {
            return false;
        }

        return hash_equals($hashedPassword, md5(self::LEADING_SALT.$plainPassword.self::TRAILING_SALT));
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return true;
    }
}
