<?php

declare(strict_types=1);

/*
 * Rehearses the production migration on a COPY of the production dump (never on production):
 *
 *   docker compose exec web php bin/verify-prod-copy.php /path/to/prod-dump.sql
 *
 * 1. restores the dump into the scratch database `scubaswtrinec_migcheck`,
 * 2. fingerprints every 2014 table (row count + md5 over all columns, read as UTF-8),
 * 3. runs `doctrine:migrations:migrate` exactly like the deploy does,
 * 4. fingerprints again and fails unless every table is identical; validates the schema;
 * 5. checks every user still has a verifiable hash (legacy md5 or modern).
 * The dump itself holds personal data: keep it out of the repository.
 */

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$dump = $argv[1] ?? '';
if (!is_file($dump)) {
    fwrite(STDERR, "usage: php bin/verify-prod-copy.php <prod-dump.sql>\n");
    exit(2);
}
$db = 'scubaswtrinec_migcheck';
$dsn = sprintf('mysql:host=%s;port=%s', $_ENV['DATABASE_HOST'], $_ENV['DATABASE_PORT']);
$root = new PDO($dsn, $_ENV['DATABASE_USER'], $_ENV['DATABASE_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$root->exec('DROP DATABASE IF EXISTS `'.$db.'`');
$root->exec('CREATE DATABASE `'.$db.'` CHARACTER SET latin1 COLLATE latin1_swedish_ci');

$restore = Process::fromShellCommandline(sprintf(
    'mariadb -h%s -P%s -u%s -p%s --default-character-set=binary %s < %s',
    escapeshellarg($_ENV['DATABASE_HOST']),
    escapeshellarg($_ENV['DATABASE_PORT']),
    escapeshellarg($_ENV['DATABASE_USER']),
    escapeshellarg($_ENV['DATABASE_PASSWORD']),
    $db,
    escapeshellarg($dump),
));
if (0 !== $restore->setTimeout(600)->run()) {
    // The FrankenPHP image has no mariadb client: fall back to PDO (the dump is plain SQL).
    $pdo = new PDO($dsn.';dbname='.$db, $_ENV['DATABASE_USER'], $_ENV['DATABASE_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('SET NAMES binary');
    $pdo->exec((string) file_get_contents($dump));
}

$tables = ['actuality', 'actuality_photo', 'article', 'article_photo', 'contact', 'discussion', 'discussion_ban',
    'gallery', 'gallery_photo', 'instructor', 'link', 'lst_thumbnail_tp', 'photo', 'thumbnail', 'user'];

$fingerprint = static function () use ($dsn, $db, $tables): array {
    $pdo = new PDO($dsn.';dbname='.$db.';charset=utf8mb4', $_ENV['DATABASE_USER'], $_ENV['DATABASE_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $out = [];
    foreach ($tables as $t) {
        // lst_thumbnail_tp gains two rows (card, large); compare the 2014 rows only.
        $sql = match ($t) {
            'lst_thumbnail_tp' => "SELECT * FROM `$t` WHERE code NOT IN ('card','large') ORDER BY id",
            // Version20261022100000 hides the 11 bot posts of 2026 on purpose; checked separately below.
            'discussion' => "SELECT * FROM `$t` WHERE id NOT BETWEEN 24 AND 34 ORDER BY id",
            default => "SELECT * FROM `$t` ORDER BY id",
        };
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $out[$t] = count($rows).' rows, md5 '.md5(serialize($rows));
    }

    return $out;
};

$console = static function (array $args) use ($db): string {
    $p = new Process(['php', dirname(__DIR__).'/bin/console', ...$args], dirname(__DIR__), ['APP_ENV' => 'dev', 'DATABASE_NAME' => $db, 'SHELL_VERBOSITY' => '0']);
    $p->setTimeout(600)->mustRun();

    return $p->getOutput().$p->getErrorOutput();
};

$before = $fingerprint();
echo "== before migration\n";
foreach ($before as $t => $f) {
    printf("  %-18s %s\n", $t, $f);
}

$t0 = microtime(true);
echo "\n== doctrine:migrations:migrate (as in deploy.sh)\n".$console(['doctrine:migrations:migrate', '--no-interaction']);
printf("   took %.1f s\n", microtime(true) - $t0);

$after = $fingerprint();
$ok = true;
echo "\n== after migration\n";
foreach ($after as $t => $f) {
    $same = $before[$t] === $f;
    $ok = $ok && $same;
    printf("  %-18s %s  %s\n", $t, $f, $same ? 'IDENTICAL' : 'DIFFERENT (was '.$before[$t].')');
}

echo "\n== doctrine:schema:validate\n".$console(['doctrine:schema:validate']);

$pdo = new PDO($dsn.';dbname='.$db.';charset=utf8mb4', $_ENV['DATABASE_USER'], $_ENV['DATABASE_PASSWORD']);
$spam = $pdo->query('SELECT id, del_flag FROM discussion WHERE id BETWEEN 24 AND 34 ORDER BY id')->fetchAll(PDO::FETCH_KEY_PAIR);
$hidden = count(array_filter($spam, static fn ($d): bool => 1 === (int) $d));
printf("\n== guest-book spam (ids 24-34): %d rows, %d hidden\n", count($spam), $hidden);
$ok = $ok && (0 === count($spam) || 11 === $hidden);

echo "\n== users (hash scheme only)\n";
foreach ($pdo->query('SELECT id, del_flag, password FROM `user` ORDER BY id') as $u) {
    $scheme = preg_match('/^[0-9a-f]{32}$/', (string) $u['password']) ? 'legacy md5 -> verified by LegacyMd5PasswordHasher, upgraded on next login'
        : (str_starts_with((string) $u['password'], '$') ? 'modern' : 'UNKNOWN');
    $ok = $ok && 'UNKNOWN' !== $scheme;
    printf("  user %d%s: %s\n", $u['id'], $u['del_flag'] ? ' (deleted)' : '', $scheme);
}

echo "\n".($ok ? "RESULT: OK — every row identical, schema in sync\n" : "RESULT: FAILED\n");
exit($ok ? 0 : 1);
