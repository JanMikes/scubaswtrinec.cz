<?php

declare(strict_types=1);

namespace App\Tests\Migration;

use App\Tests\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Replays the production cutover on a scratch database:
 *   1. the 2014 schema with data and NO doctrine_migration_versions table (= production today);
 *   2. `doctrine:migrations:migrate` (the deploy's one-off step);
 *   3. every row of every 2014 table must be byte-for-byte identical (per-table fingerprint),
 *      and the mapping must validate against the resulting schema.
 *
 * The same check is run against a copy of the real production dump by bin/verify-prod-copy.sh
 * (not in CI: the dump holds personal data and is never committed).
 */
final class LegacyDatabaseMigrationTest extends TestCase
{
    private const string DB = 'scubaswtrinec_migcheck';

    public function testMigratingALegacyDatabaseKeepsEveryRow(): void
    {
        TestDatabase::recreate(self::DB);
        $env = ['APP_ENV' => 'dev', 'DATABASE_NAME' => self::DB];

        // 1. 2014 schema (the baseline migration *is* the production DDL), legacy data, no ledger.
        TestDatabase::console(['doctrine:migrations:migrate', 'DoctrineMigrations\Version20141001000000', '--no-interaction'], $env);
        $pdo = TestDatabase::pdo(self::DB);
        // utf8mb3 tables cannot hold 4-byte characters before the upgrade: strip them from the seed.
        $pdo->exec((string) preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', (string) file_get_contents(\dirname(__DIR__).'/fixtures/seed.sql')));
        $pdo->exec('DROP TABLE doctrine_migration_versions');
        self::assertSame('utf8mb3_general_ci', $this->tableCollation($pdo, 'gallery'));

        $before = $this->fingerprints($pdo);

        // 2. the deploy step
        TestDatabase::console(['doctrine:migrations:migrate', '--no-interaction'], $env);
        self::assertSame('utf8mb4', $this->charset(TestDatabase::pdo(self::DB), 'user', 'password'));

        // 3. nothing lost or altered
        $after = $this->fingerprints(TestDatabase::pdo(self::DB));
        self::assertSame($before, $after);
        self::assertSame('utf8mb4_unicode_ci', $this->tableCollation(TestDatabase::pdo(self::DB), 'gallery'));
        // Exits non-zero (-> ProcessFailedException) unless mapping and database are in sync.
        TestDatabase::console(['doctrine:schema:validate'], $env + ['SHELL_VERBOSITY' => '0']);

        // Running the deploy step again is a no-op.
        TestDatabase::console(['doctrine:migrations:migrate', '--no-interaction', '--allow-no-migration'], $env);
        self::assertSame($before, $this->fingerprints(TestDatabase::pdo(self::DB)));
    }

    /** @return array<string, string> table => "rows:md5" over every column of every row, as UTF-8 text */
    private function fingerprints(\PDO $pdo): array
    {
        $pdo->exec('SET NAMES utf8mb4');
        $tables = ['actuality', 'actuality_photo', 'article', 'article_photo', 'contact', 'discussion', 'discussion_ban',
            'gallery', 'gallery_photo', 'instructor', 'link', 'photo', 'thumbnail', 'user'];
        $out = [];
        foreach ($tables as $t) {
            $rows = $this->q($pdo, "SELECT * FROM `$t` ORDER BY id")->fetchAll(\PDO::FETCH_ASSOC);
            $out[$t] = \count($rows).':'.md5(serialize($rows));
        }
        $out['lst_thumbnail_tp(1-4)'] = md5(serialize($this->q($pdo, 'SELECT id, code, width, height FROM lst_thumbnail_tp WHERE id <= 4 ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC)));

        return $out;
    }

    private function q(\PDO $pdo, string $sql): \PDOStatement
    {
        $stmt = $pdo->query($sql);
        self::assertInstanceOf(\PDOStatement::class, $stmt);

        return $stmt;
    }

    private function charset(\PDO $pdo, string $table, string $column): string
    {
        $stmt = $pdo->prepare('SELECT character_set_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?');
        $stmt->execute([self::DB, $table, $column]);

        return (string) $stmt->fetchColumn();
    }

    private function tableCollation(\PDO $pdo, string $table): string
    {
        $stmt = $pdo->prepare('SELECT table_collation FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
        $stmt->execute([self::DB, $table]);

        return (string) $stmt->fetchColumn();
    }
}
