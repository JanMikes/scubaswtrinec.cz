<?php

declare(strict_types=1);

use App\Tests\TestDatabase;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

umask(0000);

// Fresh test database built by the real migrations (baseline + upgrade = the production path),
// then the legacy-shaped seed data. DAMA wraps each test in a rolled-back transaction.
TestDatabase::rebuild();
