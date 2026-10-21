<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Baseline: the 2014 Nette schema exactly as it runs in production (DDL taken from the live
 * MariaDB 11.4 dump of 2026-10-21, AUTO_INCREMENT counters stripped).
 *
 * Every statement is `CREATE TABLE IF NOT EXISTS` / `INSERT IGNORE`, so on the production database —
 * where all of this already exists with real data — the migration changes nothing and only gets
 * recorded as executed. On an empty database (tests, local dev) it creates the legacy schema, which
 * the following migrations then upgrade exactly as they upgrade production.
 */
final class Version20141001000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '2014 legacy schema baseline (no-op where it already exists)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `photo` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `filename` varchar(255) DEFAULT NULL,
                  `path` varchar(255) DEFAULT NULL,
                  `width` int(4) unsigned DEFAULT NULL,
                  `height` int(4) unsigned DEFAULT NULL,
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
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `lst_thumbnail_tp` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `code` varchar(255) DEFAULT NULL,
                  `name` varchar(255) DEFAULT NULL,
                  `description` text DEFAULT NULL,
                  `width` int(11) unsigned DEFAULT NULL,
                  `height` int(11) unsigned DEFAULT NULL,
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
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `thumbnail` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `type_id` int(11) unsigned DEFAULT NULL,
                  `photo_id` int(11) unsigned DEFAULT NULL,
                  `filename` varchar(255) DEFAULT NULL,
                  `path` varchar(255) DEFAULT NULL,
                  `width` int(4) unsigned DEFAULT NULL,
                  `height` int(4) unsigned DEFAULT NULL,
                  `ins_dt` datetime NOT NULL,
                  `ins_process` varchar(255) DEFAULT NULL,
                  `ins_user_id` varchar(100) DEFAULT NULL,
                  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  `upd_process` varchar(255) DEFAULT NULL,
                  `upd_user_id` varchar(100) DEFAULT NULL,
                  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
                  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `type_id` (`type_id`),
                  KEY `photo_id` (`photo_id`),
                  CONSTRAINT `thumbnail_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `lst_thumbnail_tp` (`id`) ON UPDATE NO ACTION,
                  CONSTRAINT `thumbnail_ibfk_2` FOREIGN KEY (`photo_id`) REFERENCES `photo` (`id`) ON UPDATE NO ACTION
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
            SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `user` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `name` varchar(100) DEFAULT NULL,
                  `email` varchar(100) DEFAULT NULL,
                  `password` varchar(100) DEFAULT NULL,
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
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `actuality` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `date` date DEFAULT NULL,
                  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
                  `text` text DEFAULT NULL,
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
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `actuality_photo` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `actuality_id` int(11) unsigned DEFAULT NULL,
                  `photo_id` int(11) unsigned DEFAULT NULL,
                  `order` int(11) unsigned DEFAULT NULL,
                  `description` varchar(100) DEFAULT NULL,
                  `ins_dt` datetime NOT NULL,
                  `ins_process` varchar(255) DEFAULT NULL,
                  `ins_user_id` varchar(100) DEFAULT NULL,
                  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  `upd_process` varchar(255) DEFAULT NULL,
                  `upd_user_id` varchar(100) DEFAULT NULL,
                  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
                  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `actuality_id` (`actuality_id`),
                  KEY `photo_id` (`photo_id`),
                  CONSTRAINT `actuality_photo_ibfk_1` FOREIGN KEY (`actuality_id`) REFERENCES `actuality` (`id`) ON UPDATE NO ACTION,
                  CONSTRAINT `actuality_photo_ibfk_2` FOREIGN KEY (`photo_id`) REFERENCES `photo` (`id`) ON UPDATE NO ACTION
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
            SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `article` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `date` date DEFAULT NULL,
                  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
                  `text` text DEFAULT NULL,
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
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `article_photo` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `article_id` int(11) unsigned DEFAULT NULL,
                  `photo_id` int(11) unsigned DEFAULT NULL,
                  `order` int(11) unsigned DEFAULT NULL,
                  `description` varchar(100) DEFAULT NULL,
                  `ins_dt` datetime NOT NULL,
                  `ins_process` varchar(255) DEFAULT NULL,
                  `ins_user_id` varchar(100) DEFAULT NULL,
                  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  `upd_process` varchar(255) DEFAULT NULL,
                  `upd_user_id` varchar(100) DEFAULT NULL,
                  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
                  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `article_id` (`article_id`),
                  KEY `photo_id` (`photo_id`),
                  CONSTRAINT `article_photo_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`) ON UPDATE NO ACTION,
                  CONSTRAINT `article_photo_ibfk_2` FOREIGN KEY (`photo_id`) REFERENCES `photo` (`id`) ON UPDATE NO ACTION
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
            SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `gallery` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `photo_id` int(11) unsigned DEFAULT NULL,
                  `order` int(11) unsigned DEFAULT NULL,
                  `name` varchar(50) DEFAULT NULL,
                  `description` text DEFAULT NULL,
                  `ins_dt` datetime NOT NULL,
                  `ins_process` varchar(255) DEFAULT NULL,
                  `ins_user_id` varchar(100) DEFAULT NULL,
                  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  `upd_process` varchar(255) DEFAULT NULL,
                  `upd_user_id` varchar(100) DEFAULT NULL,
                  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
                  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `photo_id` (`photo_id`),
                  CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`photo_id`) REFERENCES `photo` (`id`) ON UPDATE NO ACTION
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
            SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `gallery_photo` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `gallery_id` int(11) unsigned DEFAULT NULL,
                  `photo_id` int(11) unsigned DEFAULT NULL,
                  `order` int(11) unsigned DEFAULT NULL,
                  `description` varchar(100) DEFAULT NULL,
                  `ins_dt` datetime NOT NULL,
                  `ins_process` varchar(255) DEFAULT NULL,
                  `ins_user_id` varchar(100) DEFAULT NULL,
                  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  `upd_process` varchar(255) DEFAULT NULL,
                  `upd_user_id` varchar(100) DEFAULT NULL,
                  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
                  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `gallery_id` (`gallery_id`),
                  KEY `photo_id` (`photo_id`),
                  CONSTRAINT `gallery_photo_ibfk_1` FOREIGN KEY (`gallery_id`) REFERENCES `gallery` (`id`) ON UPDATE NO ACTION,
                  CONSTRAINT `gallery_photo_ibfk_2` FOREIGN KEY (`photo_id`) REFERENCES `photo` (`id`) ON UPDATE NO ACTION
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
            SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `instructor` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `photo_id` int(11) unsigned DEFAULT NULL,
                  `order` int(11) unsigned DEFAULT NULL,
                  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
                  `text` text DEFAULT NULL,
                  `ins_dt` datetime NOT NULL,
                  `ins_process` varchar(255) DEFAULT NULL,
                  `ins_user_id` varchar(100) DEFAULT NULL,
                  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  `upd_process` varchar(255) DEFAULT NULL,
                  `upd_user_id` varchar(100) DEFAULT NULL,
                  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
                  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `photo_id` (`photo_id`),
                  CONSTRAINT `instructor_ibfk_1` FOREIGN KEY (`photo_id`) REFERENCES `photo` (`id`) ON UPDATE NO ACTION
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
            SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `link` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `photo_id` int(11) unsigned DEFAULT NULL,
                  `order` int(11) unsigned DEFAULT NULL,
                  `name` varchar(50) DEFAULT NULL,
                  `url` varchar(255) DEFAULT '',
                  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
                  `ins_dt` datetime NOT NULL,
                  `ins_process` varchar(255) DEFAULT NULL,
                  `ins_user_id` varchar(100) DEFAULT NULL,
                  `upd_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  `upd_process` varchar(255) DEFAULT NULL,
                  `upd_user_id` varchar(100) DEFAULT NULL,
                  `active_flag` tinyint(1) unsigned NOT NULL DEFAULT 1,
                  `del_flag` tinyint(1) unsigned NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `photo_id` (`photo_id`),
                  CONSTRAINT `link_ibfk_1` FOREIGN KEY (`photo_id`) REFERENCES `photo` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
            SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `contact` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `text` text DEFAULT NULL,
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
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `discussion` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `ip` varchar(30) DEFAULT NULL,
                  `author` varchar(50) DEFAULT NULL,
                  `subject` varchar(50) DEFAULT NULL,
                  `email` varchar(50) DEFAULT NULL,
                  `text` text DEFAULT NULL,
                  `additional_info` text DEFAULT NULL,
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
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `discussion_ban` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `ip` varchar(40) DEFAULT '',
                  `reason` text DEFAULT NULL,
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

        $this->addSql(<<<'SQL'
            INSERT IGNORE INTO `lst_thumbnail_tp` (`id`, `code`, `name`, `width`, `height`, `ins_dt`, `ins_process`) VALUES
                (1, 'admin', 'Admin', 120, 120, NOW(), 'baseline'),
                (2, 'actuality', 'Aktualita', 200, 150, NOW(), 'baseline'),
                (3, 'gallery', 'Galerie', 262, 197, NOW(), 'baseline'),
                (4, 'link', 'Odkaz', 80, 80, NOW(), 'baseline')
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The baseline is never rolled back (it may describe production data).');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
