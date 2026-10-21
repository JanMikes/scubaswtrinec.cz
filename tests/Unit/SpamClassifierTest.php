<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Guestbook\SpamClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Fixtures are the real guest-book rows (author/subject/text only — already public on the site):
 * spam-2026.json = the 11 bot posts (ids 24-34), spam-2014.json = the pharmacy link spam (ids 10-15),
 * legitimate.json = every legitimate post (ids 2-9, 16-23: Czech, Slovak, Croatian, Russian).
 */
final class SpamClassifierTest extends TestCase
{
    /** @return iterable<string, array{array{id: int, author: ?string, subject: ?string, text: ?string}}> */
    private static function fixture(string $name): iterable
    {
        /** @var list<array{id: int, author: ?string, subject: ?string, text: ?string}> $rows */
        $rows = json_decode((string) file_get_contents(\dirname(__DIR__).'/fixtures/guestbook/'.$name.'.json'), true, flags: \JSON_THROW_ON_ERROR);
        foreach ($rows as $row) {
            yield $name.' #'.$row['id'] => [$row];
        }
    }

    /** @return iterable<string, array{array{id: int, author: ?string, subject: ?string, text: ?string}}> */
    public static function spam2026(): iterable
    {
        return self::fixture('spam-2026');
    }

    /** @return iterable<string, array{array{id: int, author: ?string, subject: ?string, text: ?string}}> */
    public static function spam2014(): iterable
    {
        return self::fixture('spam-2014');
    }

    /** @return iterable<string, array{array{id: int, author: ?string, subject: ?string, text: ?string}}> */
    public static function legitimate(): iterable
    {
        return self::fixture('legitimate');
    }

    /** @param array{id: int, author: ?string, subject: ?string, text: ?string} $row */
    #[DataProvider('spam2026')]
    public function testEveryRandomLetterBotPostIsRejected(array $row): void
    {
        $verdict = (new SpamClassifier())->classify($row['author'], $row['subject'], $row['text']);
        self::assertTrue($verdict->isRejected(), $verdict->decision.' '.$verdict->reason);
        self::assertStringContainsString('random-letter', $verdict->reason);
    }

    /** @param array{id: int, author: ?string, subject: ?string, text: ?string} $row */
    #[DataProvider('spam2014')]
    public function testEveryLinkSpamPostIsRejected(array $row): void
    {
        self::assertTrue((new SpamClassifier())->classify($row['author'], $row['subject'], $row['text'])->isRejected());
    }

    /** @param array{id: int, author: ?string, subject: ?string, text: ?string} $row */
    #[DataProvider('legitimate')]
    public function testEveryLegitimatePostIsPublishedImmediately(array $row): void
    {
        $verdict = (new SpamClassifier())->classify($row['author'], $row['subject'], $row['text']);
        self::assertTrue($verdict->isAccepted(), \sprintf('post %d: %s %s', $row['id'], $verdict->decision, $verdict->reason));
    }

    /** @return iterable<string, array{?string, ?string, ?string, string}> */
    public static function rules(): iterable
    {
        yield 'one gibberish field only -> moderation' => ['Petr', 'xTUMVzhnGMmXJoffwhsEZ', 'Ahoj, kdy je trénink?', SpamClassifier::MODERATE];
        yield 'bbcode link' => ['Petr', null, 'super [url=http://x.example]klik[/url]', SpamClassifier::REJECT];
        yield 'three links -> moderation' => ['Petr', null, 'https://a.example https://b.example https://c.example', SpamClassifier::MODERATE];
        yield 'six links -> reject' => ['Petr', null, str_repeat('https://a.example ', 6), SpamClassifier::REJECT];
        yield 'link in the name -> moderation' => ['https://shop.example', null, 'Dobrý den', SpamClassifier::MODERATE];
        yield 'html markup -> moderation' => ['Petr', null, 'Ahoj <script>alert(1)</script>', SpamClassifier::MODERATE];
        yield 'two links are fine' => ['Roman', 'Video', 'https://youtu.be/a a https://youtu.be/b', SpamClassifier::ACCEPT];
        yield 'camel-case brand is not gibberish' => ['McDonaldovaSkupina', null, 'Ahoj všichni', SpamClassifier::ACCEPT];
        yield 'all caps subject is fine' => ['Jan', 'POZVÁNKANATECHMEETING', 'Zdravím', SpamClassifier::ACCEPT];
        yield 'long lowercase word is fine' => ['Jan', null, 'nejneobhospodařovávatelnějšími', SpamClassifier::ACCEPT];
        yield 'emoji post is fine' => ['Jana', 'Díky', 'Super ponor 🤿🐟', SpamClassifier::ACCEPT];
    }

    #[DataProvider('rules')]
    public function testRules(?string $author, ?string $subject, ?string $text, string $expected): void
    {
        self::assertSame($expected, (new SpamClassifier())->classify($author, $subject, $text)->decision);
    }
}
