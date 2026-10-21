<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Signed "form rendered at" timestamp — the 2014 antispam's "send delay" rule (a human needs at
 * least a few seconds to fill the discussion form), but tamper-proof.
 */
final readonly class FormTimeToken
{
    public const int MIN_SECONDS = 5;
    public const int MAX_SECONDS = 86400;

    public function __construct(#[Autowire('%kernel.secret%')] private string $secret)
    {
    }

    public function issue(?int $now = null): string
    {
        $ts = (string) ($now ?? time());

        return $ts.'.'.hash_hmac('sha256', $ts, $this->secret);
    }

    public function isValid(?string $token, ?int $now = null): bool
    {
        if (null === $token || !str_contains($token, '.')) {
            return false;
        }
        [$ts, $sig] = explode('.', $token, 2);
        if (!ctype_digit($ts) || !hash_equals(hash_hmac('sha256', $ts, $this->secret), $sig)) {
            return false;
        }
        $age = ($now ?? time()) - (int) $ts;

        return $age >= self::MIN_SECONDS && $age <= self::MAX_SECONDS;
    }
}
