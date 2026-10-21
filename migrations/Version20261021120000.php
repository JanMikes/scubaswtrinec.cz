<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Upgrade the 2014 schema for the rebuilt site. Forward-only, additive and data-preserving; every
 * change is also compatible with the old Nette app, so rolling back the image keeps working:
 *
 *  - all content tables utf8mb3 -> utf8mb4 (lossless: utf8mb4 is a superset; lets editors and
 *    discussion posts use emoji instead of being silently truncated at the first one);
 *  - `user.password` varchar(100) -> varchar(255): room for modern hashes (the legacy md5 values
 *    stay until each user's next login re-hashes them);
 *  - `discussion.ip` / `discussion_ban.ip` -> varchar(45): full IPv6 addresses;
 *  - two extra thumbnail sizes for the new design (`card`, `large`);
 *  - `sessions` table for PdoSessionHandler.
 *
 * No table or column is dropped or renamed.
 */
final class Version20261021120000 extends AbstractMigration
{
    private const array TABLES = [
        'photo', 'lst_thumbnail_tp', 'thumbnail', 'user', 'actuality', 'actuality_photo', 'article',
        'article_photo', 'gallery', 'gallery_photo', 'instructor', 'link', 'contact', 'discussion',
        'discussion_ban',
    ];

    public function getDescription(): string
    {
        return 'utf8mb4, wider password/ip columns, new thumbnail sizes, sessions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        foreach (self::TABLES as $table) {
            $this->addSql(\sprintf('ALTER TABLE `%s` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $table));
        }
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');

        $this->addSql('ALTER TABLE `user` MODIFY `password` VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `discussion` MODIFY `ip` VARCHAR(45) DEFAULT NULL');
        $this->addSql("ALTER TABLE `discussion_ban` MODIFY `ip` VARCHAR(45) DEFAULT ''");

        $this->addSql(<<<'SQL'
            INSERT INTO `lst_thumbnail_tp` (`code`, `name`, `description`, `width`, `height`, `ins_dt`, `ins_process`)
            SELECT 'card', 'Karta (nový web)', 'crop', 720, 540, NOW(), 'Version20261021120000'
            FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `lst_thumbnail_tp` WHERE `code` = 'card')
            SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO `lst_thumbnail_tp` (`code`, `name`, `description`, `width`, `height`, `ins_dt`, `ins_process`)
            SELECT 'large', 'Velký náhled (nový web)', 'fit', 1600, 1600, NOW(), 'Version20261021120000'
            FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `lst_thumbnail_tp` WHERE `code` = 'large')
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `sessions` (
                `sess_id` VARBINARY(128) NOT NULL PRIMARY KEY,
                `sess_data` LONGBLOB NOT NULL,
                `sess_lifetime` INTEGER UNSIGNED NOT NULL,
                `sess_time` INTEGER UNSIGNED NOT NULL,
                INDEX `sessions_sess_lifetime_idx` (`sess_lifetime`)
            ) COLLATE utf8mb4_bin, ENGINE = InnoDB
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Forward-only: narrowing columns back could truncate data.');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
