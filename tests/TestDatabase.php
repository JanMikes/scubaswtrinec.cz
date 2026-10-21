<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class TestDatabase
{
    public const string LEGACY_PASSWORD = 'Potapec-1986!';
    public const string ADMIN_EMAIL = 'admin@example.invalid';

    public static function pdo(?string $db = null): \PDO
    {
        $dsn = \sprintf('mysql:host=%s;port=%s;charset=utf8mb4%s', $_ENV['DATABASE_HOST'], $_ENV['DATABASE_PORT'], null !== $db ? ';dbname='.$db : '');

        return new \PDO($dsn, $_ENV['DATABASE_USER'], $_ENV['DATABASE_PASSWORD'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    }

    /** Same name doctrine.yaml derives for the test env (`dbname_suffix: _test<TEST_TOKEN>`). */
    public static function name(): string
    {
        return $_ENV['DATABASE_NAME'].'_test'.($_ENV['TEST_TOKEN'] ?? '');
    }

    /** Recreates a scratch database; refuses anything that is not a `_test` / `_migcheck` database. */
    public static function recreate(string $db): void
    {
        if (1 !== preg_match('/_(test\d*|migcheck)$/', $db)) {
            throw new \LogicException(\sprintf('Refusing to recreate database "%s": not a scratch database.', $db));
        }
        $pdo = self::pdo();
        // Production runs MariaDB non-strict (lily compose: --sql-mode=NO_ENGINE_SUBSTITUTION).
        $pdo->exec("SET GLOBAL sql_mode = 'NO_ENGINE_SUBSTITUTION'");
        $pdo->exec('DROP DATABASE IF EXISTS `'.$db.'`');
        $pdo->exec('CREATE DATABASE `'.$db.'` CHARACTER SET latin1 COLLATE latin1_swedish_ci');
    }

    public static function rebuild(): void
    {
        $db = self::name();
        self::recreate($db);
        self::console(['doctrine:migrations:migrate', '--no-interaction']);
        self::pdo($db)->exec((string) file_get_contents(__DIR__.'/fixtures/seed.sql'));
        self::writeImages();
        self::console(['cache:pool:clear', 'app.rate_limiter_cache']);
    }

    /**
     * @param list<string>          $args
     * @param array<string, string> $env
     */
    public static function console(array $args, array $env = []): string
    {
        $process = new Process(['php', \dirname(__DIR__).'/bin/console', ...$args], \dirname(__DIR__), $env + ['APP_ENV' => 'test']);
        $process->setTimeout(300);
        $process->mustRun();

        return $process->getOutput();
    }

    public static function publicDir(): string
    {
        return \dirname(__DIR__).'/var/test-public';
    }

    /** Originals for the photo rows the seed marks as "on disk"; the others are deliberately missing. */
    private static function writeImages(): void
    {
        $fs = new Filesystem();
        $fs->remove(self::publicDir());
        foreach (['img/gallery/1/1.jpg', 'img/gallery/1/main.jpg', 'img/actuality/1/3.jpg', 'img/instructor/1/main.jpg'] as $rel) {
            $fs->mkdir(\dirname(self::publicDir().'/'.$rel));
            self::jpeg(self::publicDir().'/'.$rel, 800, 600);
        }
    }

    /**
     * @param int<1, max> $w
     * @param int<1, max> $h
     */
    public static function jpeg(string $path, int $w, int $h): void
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, (int) imagecolorallocate($img, 51, 146, 234));
        imagejpeg($img, $path, 80);
    }
}
