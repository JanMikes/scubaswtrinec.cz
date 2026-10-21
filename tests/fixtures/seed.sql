-- Synthetic data in the exact shapes of the production rows (no personal data).
-- Password of users 1 and 2 = md5('scuba' . 'Potapec-1986!' . 'sw'): the 2014 legacy scheme.
SET NAMES utf8mb4;

INSERT INTO `user` (`id`, `name`, `email`, `password`, `ins_dt`, `ins_process`, `del_flag`) VALUES
    (1, 'Testovací Admin', 'admin@example.invalid', MD5(CONCAT('scuba', 'Potapec-1986!', 'sw')), '2014-08-07 12:56:37', 'seed', 0),
    (2, 'Smazaný Admin', 'deleted@example.invalid', MD5(CONCAT('scuba', 'Potapec-1986!', 'sw')), '2014-08-07 12:56:37', 'seed', 1);

INSERT INTO `photo` (`id`, `filename`, `path`, `width`, `height`, `ins_dt`) VALUES
    (1, '1.jpg', 'img/gallery/1', 800, 600, '2014-09-23 11:06:29'),
    (2, '2.jpg', 'img/gallery/1', 800, 600, '2014-09-23 11:06:29'),
    (3, '3.jpg', 'img/actuality/1', 800, 600, '2014-09-23 10:54:52'),
    (4, 'main.jpg', 'img/gallery/1', 800, 600, '2014-09-23 11:04:42'),
    (5, 'main.jpg', 'img/instructor/1', 800, 600, '2014-10-07 12:15:59');

INSERT INTO `actuality` (`id`, `date`, `name`, `text`, `ins_dt`, `active_flag`, `del_flag`) VALUES
    (1, '2026-07-29', 'Potápění pod ledem', '<p>Ice diving neboli <strong>potápění pod ledem</strong> 🧊.</p><p><iframe src="https://www.youtube.com/embed/abc123" width="560" height="315"></iframe></p>', '2026-07-29 10:00:00', 1, 0),
    (2, '2026-06-01', 'Gruner see', '<p>Tragický osud Gruner see.</p>', '2026-06-01 10:00:00', 1, 0),
    (3, '2026-05-01', 'Velké Košariská', '<p>Text 3</p>', '2026-05-01 10:00:00', 1, 0),
    (4, '2026-04-01', 'Historie potápění 1', '<p>Text 4</p>', '2026-04-01 10:00:00', 1, 0),
    (5, '2026-03-01', 'Historie potápění 2', '<p>Text 5</p>', '2026-03-01 10:00:00', 1, 0),
    (6, '2026-02-01', 'Šestá aktualita', '<p>Text 6</p>', '2026-02-01 10:00:00', 1, 0),
    (7, '2026-01-01', 'Neaktivní aktualita', '<p>skrytá</p>', '2026-01-01 10:00:00', 0, 0),
    (8, '2025-12-01', 'Smazaná aktualita', '<p>smazaná</p>', '2025-12-01 10:00:00', 1, 1);

INSERT INTO `actuality_photo` (`id`, `actuality_id`, `photo_id`, `order`, `description`, `ins_dt`) VALUES
    (1, 1, 3, 10, 'Díra v ledu', '2026-07-29 10:00:00');

INSERT INTO `article` (`id`, `date`, `name`, `text`, `ins_dt`, `active_flag`, `del_flag`) VALUES
    (1, '2024-04-23', 'Sidemount diving', '<h3>Sidemount</h3><p>Článek o sidemountu.</p>', '2024-04-23 10:00:00', 1, 0),
    (2, '2024-01-01', 'Skrytý článek', '<p>x</p>', '2024-01-01 10:00:00', 0, 0);

INSERT INTO `gallery` (`id`, `photo_id`, `order`, `name`, `description`, `ins_dt`, `active_flag`, `del_flag`) VALUES
    (1, 4, 30, 'Svobodné Heřmanice únor 2019', 'Ponor pod ledem', '2019-02-01 10:00:00', 1, 0),
    (2, NULL, 20, 'Gossau see', NULL, '2016-01-01 10:00:00', 1, 0),
    (3, NULL, 10, 'Skrytá galerie', NULL, '2015-01-01 10:00:00', 0, 0);

INSERT INTO `gallery_photo` (`id`, `gallery_id`, `photo_id`, `order`, `description`, `ins_dt`) VALUES
    (1, 1, 1, 20, 'Na disku', '2019-02-01 10:00:00'),
    (2, 1, 2, 10, 'Chybí na disku', '2019-02-01 10:00:00');

INSERT INTO `instructor` (`id`, `photo_id`, `order`, `name`, `text`, `ins_dt`) VALUES
    (1, 5, 70, 'Mgr. Roman Kudela', '<p>Instruktor CMAS.</p>', '2014-10-07 12:15:59'),
    (2, NULL, 60, 'Jiří Valenta', '<p>Instruktor.</p>', '2014-10-07 13:44:06');

INSERT INTO `link` (`id`, `photo_id`, `order`, `name`, `url`, `description`, `ins_dt`) VALUES
    (1, NULL, 10, 'Jezerní důl', 'http://jezernidul.cz', 'Lokalita', '2024-06-25 08:10:53'),
    (2, NULL, 20, 'Krajina břidlice', 'http://krajinabridlice.cz', NULL, '2024-06-25 08:10:53');

INSERT INTO `discussion` (`id`, `ip`, `author`, `subject`, `email`, `text`, `additional_info`, `ins_dt`, `active_flag`) VALUES
    (1, '192.0.2.10', 'Potápěč', 'Dotaz', 'diver@example.invalid', 'Kdy je další trénink?', 'Mozilla', '2026-07-28 13:58:52', 1),
    (2, '192.0.2.66', 'Spammer', NULL, 'spam@example.invalid', 'buy pills', 'bot', '2026-07-29 03:00:00', 1),
    (3, '198.51.100.40', 'Čekatel', NULL, 'wait@example.invalid', 'Held for moderation https://a.example https://b.example https://c.example', 'Mozilla', '2026-10-22 08:00:00', 0);

INSERT INTO `discussion_ban` (`id`, `ip`, `reason`, `ins_dt`) VALUES
    (1, '192.0.2.99', 'Zablokovaný - spam@example.invalid', '2014-11-29 22:55:28');

INSERT INTO `contact` (`id`, `text`, `ins_dt`) VALUES
    (1, '<p>Stará verze kontaktu</p>', '2014-09-23 11:14:00'),
    (2, '<h3>SCUBA S.W.</h3><p>Klub založen v roce 1986.</p>', '2024-06-25 08:00:55');
