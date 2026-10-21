<?php

declare(strict_types=1);

namespace App\Guestbook;

final readonly class SpamVerdict
{
    private function __construct(public string $decision, public string $reason)
    {
    }

    public static function accept(): self
    {
        return new self(SpamClassifier::ACCEPT, '');
    }

    public static function moderate(string $reason): self
    {
        return new self(SpamClassifier::MODERATE, $reason);
    }

    public static function reject(string $reason): self
    {
        return new self(SpamClassifier::REJECT, $reason);
    }

    public function isAccepted(): bool
    {
        return SpamClassifier::ACCEPT === $this->decision;
    }

    public function isRejected(): bool
    {
        return SpamClassifier::REJECT === $this->decision;
    }
}
