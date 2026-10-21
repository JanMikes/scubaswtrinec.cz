<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Guestbook\SpamClassifier;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Hides the 11 bot posts of 2026 in the guest book (discussion ids 24–34, every field a random
 * mixed-case letter string) the same way the 2014 spam was hidden: `del_flag = 1` (soft delete,
 * the rows stay). Reversible: down() un-hides exactly the rows this migration hid.
 *
 * Guard: the migration acts only when ALL 11 rows exist, are still visible and are rejected by
 * App\Guestbook\SpamClassifier; if none of them exist (fresh/test database) it does nothing; any other
 * state aborts the deploy instead of touching rows it was not written for.
 */
final class Version20261022100000 extends AbstractMigration
{
    private const array SPAM_IDS = [24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34];
    private const string MARKER = 'migration:Version20261022100000:guestbook-spam-2026';

    public function getDescription(): string
    {
        return 'Soft-delete the 11 random-letter bot posts of 2026 in the guest book (ids 24-34)';
    }

    public function up(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, author, subject, text, del_flag FROM discussion WHERE id IN (?)',
            [self::SPAM_IDS],
            [\Doctrine\DBAL\ArrayParameterType::INTEGER],
        );
        if ([] === $rows) {
            $this->write('No 2026 bot posts in this database (fresh/test install) — nothing to hide.');

            return;
        }

        $this->abortIf(\count($rows) !== \count(self::SPAM_IDS), \sprintf('Expected %d spam rows, found %d — refusing to guess.', \count(self::SPAM_IDS), \count($rows)));
        $classifier = new SpamClassifier();
        foreach ($rows as $row) {
            $this->abortIf(0 !== (int) $row['del_flag'], \sprintf('Row %d is already deleted — unexpected state.', $row['id']));
            $verdict = $classifier->classify($row['author'], $row['subject'], $row['text']);
            $this->abortIf(!$verdict->isRejected(), \sprintf('Row %d does not look like the bot posts (%s) — refusing to hide it.', $row['id'], $verdict->decision));
        }

        $this->addSql(
            'UPDATE discussion SET del_flag = 1, upd_process = ?, upd_user_id = NULL WHERE id IN ('.implode(',', self::SPAM_IDS).') AND del_flag = 0',
            [self::MARKER],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'UPDATE discussion SET del_flag = 0, upd_process = NULL WHERE id IN ('.implode(',', self::SPAM_IDS).') AND del_flag = 1 AND upd_process = ?',
            [self::MARKER],
        );
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
