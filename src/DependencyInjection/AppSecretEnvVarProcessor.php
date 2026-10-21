<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * `%env(app_secret:APP_SECRET)%` — APP_SECRET when set, otherwise a key derived from DATABASE_PASSWORD.
 *
 * Why: the lily Infisical project `scubaswtrinec` holds only DB_PASSWORD/DB_ROOT_PASSWORD. Deriving
 * the kernel secret (HMAC with a fixed label, so the value is unrelated to the password itself) lets
 * the rebuilt app ship without a new secret; set APP_SECRET in Infisical to override it any time
 * (that only invalidates remember-me cookies). Decision: lily.srv docs/decisions.md D83.
 */
final class AppSecretEnvVarProcessor implements EnvVarProcessorInterface
{
    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        $explicit = (string) $getEnv($name);
        if ('' !== $explicit) {
            return $explicit;
        }

        $dbPassword = (string) $getEnv('DATABASE_PASSWORD');
        if ('' === $dbPassword) {
            throw new \RuntimeException('Neither APP_SECRET nor DATABASE_PASSWORD is set.');
        }

        return hash_hmac('sha256', 'scubaswtrinec.cz/app-secret/v1', $dbPassword);
    }

    public static function getProvidedTypes(): array
    {
        return ['app_secret' => 'string'];
    }
}
