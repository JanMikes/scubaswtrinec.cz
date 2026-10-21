<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Hides the clearly marked verification post made on production on 2026-10-22 to prove that a normal
 * guest-book post is accepted and stores the real client IP (discussion id 35). Soft delete (del_flag),
 * guarded by the exact author/text, reversible; a no-op on any database without that post.
 */
final class Version20261022110000 extends AbstractMigration
{
    private const string AUTHOR = 'TEST – správce webu';
    private const string TEXT = 'Testovací příspěvek: ověření nové ochrany diskuze proti spamu. Bude hned smazán.';
    private const string MARKER = 'migration:Version20261022110000:verification-post';

    public function getDescription(): string
    {
        return 'Soft-delete the 2026-10-22 guest-book verification post';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE discussion SET del_flag = 1, upd_process = ? WHERE author = ? AND text = ? AND del_flag = 0',
            [self::MARKER, self::AUTHOR, self::TEXT],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE discussion SET del_flag = 0, upd_process = NULL WHERE upd_process = ?', [self::MARKER]);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
