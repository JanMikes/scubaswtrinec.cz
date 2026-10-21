<?php

declare(strict_types=1);

namespace App\Guestbook;

/**
 * Content rules for guest-book ("diskuze") posts. Pure and dependency-free: the controller and the
 * cleanup migration both use it.
 *
 * Calibrated on the real data (tests/fixtures/guestbook): the 2026 bot posts fill EVERY field with one
 * random mixed-case Latin letter run ("fwvrdrrZbkLadZRJVJmJtPAI"); the 2014 spam carried <a href> links
 * to pharmacy sites; legitimate posts (Czech, Slovak, Croatian, Russian, 2014–2022) have normal words,
 * at most 2 links and no markup.
 *
 *  - reject   = clearly a bot: the post is not stored (the poster gets a generic error).
 *  - moderate = suspicious: stored hidden (active_flag = 0) until an admin approves it.
 *  - accept   = published immediately (the 2014 behaviour for normal posts).
 */
final class SpamClassifier
{
    public const string ACCEPT = 'accept';
    public const string MODERATE = 'moderate';
    public const string REJECT = 'reject';

    /** Links a legitimate post had at most (post 17: 2 links). */
    private const int MAX_LINKS = 2;
    private const int MAX_LINKS_HARD = 5;

    public function classify(?string $author, ?string $subject, ?string $text): SpamVerdict
    {
        $author = trim((string) $author);
        $subject = trim((string) $subject);
        $text = trim((string) $text);
        $reasons = [];

        $gibberish = array_keys(array_filter(
            ['author' => $author, 'subject' => $subject, 'text' => $text],
            self::isGibberish(...),
        ));
        if (\count($gibberish) >= 2) {
            return SpamVerdict::reject('random-letter strings in '.implode(', ', $gibberish));
        }
        if (1 === \count($gibberish)) {
            $reasons[] = 'random-letter string in '.$gibberish[0];
        }

        $all = $author."\n".$subject."\n".$text;
        if (1 === preg_match('~<\s*a\s[^>]*href|\[\s*(url|link)\b~iu', $all)) {
            return SpamVerdict::reject('HTML or BBCode link markup');
        }
        if (1 === preg_match('~<\s*/?\s*(script|iframe|img|div|span|p|b|strong)\b~iu', $all)) {
            $reasons[] = 'HTML markup';
        }

        $links = self::countLinks($all);
        if ($links > self::MAX_LINKS_HARD) {
            return SpamVerdict::reject(\sprintf('%d links', $links));
        }
        if ($links > self::MAX_LINKS) {
            $reasons[] = \sprintf('%d links', $links);
        }
        if (self::countLinks($author) > 0) {
            $reasons[] = 'link in the name';
        }

        return [] === $reasons ? SpamVerdict::accept() : SpamVerdict::moderate(implode('; ', $reasons));
    }

    /**
     * One run of ASCII letters with no spaces, at least 12 long, whose case flips several times inside
     * the word — no real name, subject or sentence looks like that ("McDonald" flips twice).
     */
    public static function isGibberish(string $value): bool
    {
        $value = trim($value);
        if (1 !== preg_match('/^[A-Za-z]{12,}$/', $value)) {
            return false;
        }

        return preg_match_all('/(?<=[a-z])[A-Z]|(?<=[A-Z])[a-z]/', $value) >= 4
            && preg_match_all('/[A-Z]/', substr($value, 1)) >= 3;
    }

    public static function countLinks(string $value): int
    {
        return (int) preg_match_all('~(?:https?://|www\.)[^\s<>"\']+~iu', $value);
    }
}
