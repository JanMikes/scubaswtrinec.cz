<?php

declare(strict_types=1);

namespace App\Tests\Migration;

use App\Tests\TestDatabase;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Version20261022100000 (hide the 11 bot posts) and Version20261022100100 (drop `aa`) on a scratch
 * database shaped like production: the 2014 schema with `aa`, the real 2026 spam rows (fixture) and
 * legitimate posts. Up hides exactly ids 24-34, down restores them; a row that does not look like the
 * bots aborts the migration without touching anything.
 */
final class GuestbookCleanupMigrationTest extends TestCase
{
    private const string DB = 'scubaswtrinec_migcheck';

    /** @return array<string, string> */
    private function env(): array
    {
        return ['APP_ENV' => 'dev', 'DATABASE_NAME' => self::DB];
    }

    private function prepare(): \PDO
    {
        TestDatabase::recreate(self::DB);
        TestDatabase::console(['doctrine:migrations:migrate', 'DoctrineMigrations\Version20261021120000', '--no-interaction'], $this->env());
        $pdo = TestDatabase::pdo(self::DB);
        $pdo->exec('CREATE TABLE `aa` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `ins_dt` datetime NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB');
        $insert = $pdo->prepare('INSERT INTO discussion (id, ip, author, subject, email, text, ins_dt, active_flag, del_flag) VALUES (?, ?, ?, ?, ?, ?, NOW(), 1, 0)');
        foreach (['legitimate', 'spam-2026'] as $set) {
            /** @var list<array{id: int, author: ?string, subject: ?string, text: ?string}> $rows */
            $rows = json_decode((string) file_get_contents(\dirname(__DIR__).'/fixtures/guestbook/'.$set.'.json'), true, flags: \JSON_THROW_ON_ERROR);
            foreach ($rows as $r) {
                $insert->execute([$r['id'], '172.18.0.32', $r['author'], $r['subject'], 'x@example.invalid', $r['text']]);
            }
        }

        return $pdo;
    }

    private function q(\PDO $pdo, string $sql): \PDOStatement
    {
        $stmt = $pdo->query($sql);
        self::assertInstanceOf(\PDOStatement::class, $stmt);

        return $stmt;
    }

    /** @return array<int, int> id => del_flag */
    private function flags(\PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT id, del_flag FROM discussion ORDER BY id');
        self::assertInstanceOf(\PDOStatement::class, $stmt);

        /* @var array<int, int> */
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_KEY_PAIR));
    }

    public function testUpHidesExactlyTheBotPostsAndDownRestoresThem(): void
    {
        $pdo = $this->prepare();
        $legitBefore = $this->q($pdo, 'SELECT * FROM discussion WHERE id < 24 ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC);

        TestDatabase::console(['doctrine:migrations:migrate', '--no-interaction'], $this->env());
        $flags = $this->flags($pdo);
        foreach (range(24, 34) as $id) {
            self::assertSame(1, $flags[$id], "spam row $id hidden");
        }
        self::assertSame($legitBefore, $this->q($pdo, 'SELECT * FROM discussion WHERE id < 24 ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC), 'legitimate posts untouched');
        self::assertSame(0, (int) $this->q($pdo, "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'aa'")->fetchColumn());

        TestDatabase::console(['doctrine:migrations:migrate', 'DoctrineMigrations\Version20261021120000', '--no-interaction'], $this->env());
        $flags = $this->flags($pdo);
        foreach (range(24, 34) as $id) {
            self::assertSame(0, $flags[$id], "spam row $id restored by down()");
        }
        self::assertSame(1, (int) $this->q($pdo, "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'aa'")->fetchColumn(), '`aa` recreated by down()');
    }

    public function testRowThatDoesNotLookLikeTheBotsAbortsTheCleanup(): void
    {
        $pdo = $this->prepare();
        $pdo->exec("UPDATE discussion SET author = 'Jana', subject = 'Dotaz', text = 'Kdy je trénink?' WHERE id = 30");

        try {
            TestDatabase::console(['doctrine:migrations:migrate', '--no-interaction'], $this->env());
            self::fail('the migration must abort');
        } catch (ProcessFailedException $e) {
            self::assertStringContainsString('Row 30 does not look like the bot posts', $e->getProcess()->getOutput().$e->getProcess()->getErrorOutput());
        }
        self::assertSame([], array_filter($this->flags($pdo), static fn (int $f, int $id): bool => $id >= 24 && 1 === $f, \ARRAY_FILTER_USE_BOTH), 'nothing was hidden');
    }

    public function testAaWithRowsIsNotDropped(): void
    {
        $pdo = $this->prepare();
        $pdo->exec('INSERT INTO aa (ins_dt) VALUES (NOW())');

        $this->expectException(ProcessFailedException::class);
        TestDatabase::console(['doctrine:migrations:migrate', '--no-interaction'], $this->env());
    }
}
