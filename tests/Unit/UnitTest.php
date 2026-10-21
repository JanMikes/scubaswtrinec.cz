<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\EventSubscriber\LegacyUrlSubscriber;
use App\Security\LegacyMd5PasswordHasher;
use App\Service\FormTimeToken;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UnitTest extends TestCase
{
    public function testLegacyHasherVerifiesThe2014Scheme(): void
    {
        $hasher = new LegacyMd5PasswordHasher();
        $hash = md5('scubatajne-heslosw');

        self::assertTrue($hasher->verify($hash, 'tajne-heslo'));
        self::assertFalse($hasher->verify($hash, 'tajne-heslo '));
        self::assertFalse($hasher->verify($hash, ''));
        self::assertFalse($hasher->verify(md5('tajne-heslo'), 'tajne-heslo'), 'unsalted md5 is not the 2014 scheme');
        self::assertFalse($hasher->verify(password_hash('tajne-heslo', \PASSWORD_BCRYPT), 'tajne-heslo'), 'only legacy hashes');
        self::assertTrue($hasher->needsRehash($hash));
    }

    public function testLegacyHasherNeverCreatesLegacyHashes(): void
    {
        $this->expectException(\LogicException::class);
        (new LegacyMd5PasswordHasher())->hash('x');
    }

    /** @return iterable<array{string, string}> */
    public static function urls(): iterable
    {
        yield ['/kontakt/', '/kontakt'];
        yield ['/Kontakt', '/kontakt'];
        yield ['/detailAktuality/12', '/detail-aktuality/12'];
        yield ['/detail-galerie/5/', '/detail-galerie/5'];
        yield ['/aktuality', '/'];
        yield ['/aktuality/3', '/'];
        yield ['/default', '/'];
        yield ['/odkazy/1', '/odkazy'];
        yield ['/clanky', '/clanky'];
        yield ['/admin/gallery-photo/default', '/admin/gallery-photo/default'];
        yield ['/admin/user/changePassword', '/admin/user/change-password'];
    }

    #[DataProvider('urls')]
    public function testCanonicalize(string $in, string $out): void
    {
        self::assertSame($out, (new LegacyUrlSubscriber())->canonicalize($in));
    }

    public function testFormTimeToken(): void
    {
        $t = new FormTimeToken('secret');
        $now = 1_800_000_000;
        $token = $t->issue($now);

        self::assertFalse($t->isValid($token, $now + 2), 'too fast');
        self::assertTrue($t->isValid($token, $now + 30));
        self::assertFalse($t->isValid($token, $now + 2 * 86400), 'too old');
        self::assertFalse($t->isValid(str_replace((string) $now, (string) ($now - 100), $token), $now + 30), 'forged timestamp');
        self::assertFalse((new FormTimeToken('other'))->isValid($token, $now + 30), 'other secret');
        self::assertFalse($t->isValid(null));
    }
}
