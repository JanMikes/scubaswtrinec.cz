<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drops `aa`, an empty 2014 leftover: no rows, no foreign key points at it, no view mentions it, and
 * neither the 2014 Nette code nor this app ever used it (checked 2026-10-22 on the production dump).
 * Guard: aborts if the table holds any row. down() recreates the exact 2014 DDL.
 */
final class Version20261022100100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the empty, unreferenced 2014 table `aa`';
    }

    public function up(Schema $schema): void
    {
        $exists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'aa'",
        );
        if (0 === $exists) {
            $this->write('Table `aa` does not exist — nothing to drop.');

            return;
        }
        $rows = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM `aa`');
        $this->abortIf($rows > 0, \sprintf('Table `aa` holds %d row(s) — refusing to drop it.', $rows));
        $refs = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.key_column_usage WHERE table_schema = DATABASE() AND referenced_table_name = 'aa'",
        );
        $this->abortIf($refs > 0, 'A foreign key references `aa` — refusing to drop it.');

        $this->addSql('DROP TABLE `aa`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `aa` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `ins_dt` datetime NOT NULL,
                `ins_process` varchar(255) DEFAULT NULL,
                `ins_user_id` varchar(100) DEFAULT NULL,
                `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                `upd_process` varchar(255) DEFAULT NULL,
                `upd_user_id` varchar(100) DEFAULT NULL,
                `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
                `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
            SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
