# scubaswtrinec.cz — rebuild checklist

Rebuild of the 2014 Nette 2.1 / PHP 7.1 site (CVE-2026-109618/109619: PHP 7.1 gets no fix) as a new
Symfony app on `ghcr.io/thedevs-cz/php:8.5`. **Every** existing feature is kept; the existing MariaDB
database and the user-data volume are reused as-is. Infra decision: lily.srv `docs/decisions.md` → D83.

Verification: 114 PHPUnit tests (every public route, every legacy URL shape, admin auth incl. the
legacy-hash proof, admin CRUD for every section, the legacy-DB migration replay), PHPStan level 8,
CS, `bin/verify-prod-copy.php` on the real 2026-10-21 production dump (all 16 tables byte-identical
after migrating, schema in sync), and a crawl of 380 old URLs + all 500 user-data files against a
local prod-mode instance (results below). Cutover checks on the box: §6.

Status legend: `[x]` done and verified · `[ ]` open · `[~]` intentionally changed (reason given).

## 0. Understanding of the old app (recorded before building)

**Code** (`app/`, `libs/`, read in full): one frontend presenter (`Frontend:Homepage`, actions =
pages), 14 backend presenters (`Backend:*`, secured by `SecuredPresenter`), list components with
`TRecordHandlers` (activate / deactivate / activate-all / soft delete / move up-down), form
factories per entity, a home-grown table gateway (`Entity` base class, table name derived from the
class name), `ThumbnailService` (on-demand thumbnails registered in `thumbnail`), `ImageUploadService`
(original saved as `<path>/<filename>`, row in `photo`). Router: `admin/<presenter>/<action>[/<id>]`
(Backend) and `<action>[/<id>]` (Frontend, default `Homepage:aktuality`). Dead code: `Mailer`
(registered, never called), `Member`/`Statement`/`Statute`/`About` factories (no tables), `GA`
component (unused; GA is inlined in the layout), KCFinder (shipped but `disabled => true` and never
enabled — CKEditor's upload/browse buttons never worked).

**Data model** — 16 InnoDB tables, all with the audit columns `ins_dt, ins_process, ins_user_id,
upd_dt (ON UPDATE), upd_process, upd_user_id, active_flag, del_flag` (soft delete everywhere):

| table | rows (live / inactive / deleted) | notes |
|---|---|---|
| `actuality` | 115 / 0 / 29 | date, name, text (HTML, 76 rows with YouTube iframes/scripts) |
| `actuality_photo` | 189 / 0 / 59 | FK actuality, photo; `order`, description |
| `article` | 75 / 0 / 15 | same shape as actuality |
| `article_photo` | 328 / 0 / 69 | |
| `gallery` | 35 / 0 / 23 | name, description, cover `photo_id`, `order` |
| `gallery_photo` | 255 / 0 / 25 | |
| `instructor` | 2 / 0 / 2 | name, text (HTML), photo, `order` |
| `link` | 4 / 0 / 1 | name, url, description, icon photo, `order` |
| `discussion` | 27 / 0 / 7 | author, email, subject, text, ip, user agent |
| `discussion_ban` | 1 | banned IPs |
| `contact` | 4 | append-only history; page shows the newest row |
| `photo` | 921 / – / 6 | `path` (e.g. `img/gallery/12`) + `filename`, relative to the web root |
| `thumbnail` | 1761 / – / 9 | per photo × type, file in `<photo.path>/<type>/<filename>` |
| `lst_thumbnail_tp` | 4 | admin 120×120, actuality 200×150, gallery 262×197, link 80×80 |
| `user` | 4 | name, email, password = `md5('scuba' . $plain . 'sw')` (all 4 rows, 32-hex) |
| `aa` | 0 | empty junk table — left alone |

**User data** (`scubaswtrinec_userdata` volume, 500 files / 680 MB): `actuality/`, `article/`,
`gallery/`, `instructor/`, `link/`, `upload/` (only an `.htaccess`).
**Pre-existing data loss (not caused by this rebuild):** of the 921 live `photo` rows only ~170
originals exist on disk (gallery: 7 of 252 gallery photos, 0 of 33 covers; the live site renders
dead `http://placehold.it/...` images for the rest). They were already missing on spare before the
D60 move (exactly 500 files were copied). No local copy exists. The new site renders a styled
placeholder instead of a broken image and keeps every DB row; the operator may still have an older
backup to restore originals from (they would show up automatically, paths unchanged).

**Live behaviour** (crawled 2026-10-21): `/aktuality` 301 → `/`; trailing slash 301 → no slash;
upper case 301 → lower case; detail of a missing/inactive record 302 → its list; `/kontakt/5` (any
`/<action>/<id>`) 200; `/default` serves a lorem-ipsum template page; `/admin/*` 302 → sign-in;
no sitemap, no favicon (404), robots.txt allows all, GA Universal `UA-32470478-30` (dead since 2023).
Recent discussion posts (2026) look like bot spam (random strings) — data left untouched.

## 1. Public pages / URL map

| old URL | new behaviour | status |
|---|---|---|
| `/`, `/?page=N` (aktuality, 5/page, date desc) | home: hero + latest news, same pagination param | [x] |
| `/aktuality[?page=N]` | 301 → `/[?page=N]` | [x] |
| `/detail-aktuality/{id}` (115 live ids) | 200; missing/inactive → 302 `/` | [x] |
| `/clanky[?page=N]` (5/page) | 200 | [x] |
| `/detail-clanku/{id}` (75 live ids) | 200; missing/inactive → 302 `/clanky` | [x] |
| `/galerie[?page=N]` (9/page) | 200 | [x] |
| `/detail-galerie/{id}` (35 live ids) | 200 + lightbox; missing/inactive → 302 `/galerie` | [x] |
| `/instruktori` | 200 (order desc) | [x] |
| `/diskuze[?page=N]` (10/page) + post form | 200; POST creates a post | [x] |
| `/odkazy` | 200 | [x] |
| `/kontakt` | 200 (newest `contact` row) | [x] |
| `/<action>/<anything>` e.g. `/kontakt/5` | 301 → `/<action>` (detail routes excepted) | [x] |
| `/default` | [~] 301 → `/` (was a lorem-ipsum demo page) | [x] |
| trailing slash, upper-case paths | 301 to canonical | [x] |
| `/detailAktuality/1` style (camel case) | 301 → dashed form | [x] |
| `/img/**`, `/upload/**` (user data) | served from the same volume, same paths | [x] |
| `/images/slides/big/{1..4}.jpg` | kept (used by the new hero) | [x] |
| old theme assets `/css/*`, `/js/*`, `/rs-plugin/*`, `/fontello/*`, `/ckeditor/*`, `/kcfinder/*` | [~] 404 — template assets, not content; KCFinder PHP removed (attack surface) | [x] |
| `/robots.txt` | 200 (+ sitemap line, admin disallowed) | [x] |
| `/sitemap.xml` | new (was 404) | [x] |
| `/favicon.ico` | new (was 404) | [x] |
| 404 page | Czech 404 in the new layout | [x] |

## 2. Admin (`/admin/...`, old URLs keep working)

| feature | new | status |
|---|---|---|
| sign-in `/admin/sign/in` with e-mail + password, "Trvalé přihlášení" (14 days) else short session, backlink | same URL, Symfony security, remember-me | [x] |
| **existing 4 accounts keep their passwords** (legacy md5+static salt) | `LegacyMd5PasswordHasher` as `migrate_from`; transparent rehash on next login | [x] |
| logout | | [x] |
| change password (old + new ×2, 4–20 chars) `/admin/user/change-password` | | [x] |
| dashboard | quick stats + links | [x] |
| Aktuality: list (date desc), add/edit (date, title, WYSIWYG), activate/deactivate/activate-all, soft delete | | [x] |
| Aktuality photos per item: list (order desc), add (description + image), edit/replace, reorder up/down, activate…, delete | + multi-file upload | [x] |
| Články + photos | same as Aktuality | [x] |
| Galerie: list ordered, reorder, cover image, description, photos sub-list (count) | | [x] |
| Galerie photos | same as other photo lists, multi-upload | [x] |
| Diskuze: list (author, e-mail, IP, text), edit, activate…, delete, **ban IP**, ban list + unban | | [x] |
| Kontakt: one WYSIWYG text, every save appends a history row | | [x] |
| Instruktoři: ordered list, name, WYSIWYG text, photo | | [x] |
| Odkazy: ordered list, name, URL, description, icon | | [x] |
| WYSIWYG must keep existing HTML incl. YouTube iframes, source mode | CKEditor 5 (GPL) with General HTML Support + source editing | [x] |
| audit columns (`ins_*`/`upd_*` user + process) still written | Doctrine listener | [x] |
| thumbnails generated on upload and on demand, registered in `thumbnail` | same layout on disk | [x] |
| old admin URLs `/admin/<presenter>/<action>[/<id>]?parentId=` | same paths in the new admin | [x] |

## 3. Forms, mail, antispam

| item | new | status |
|---|---|---|
| discussion form (author*, e-mail*, subject, text*), stores IP + user agent, published immediately | same | [x] |
| antispam: hidden "clear this field" input + ≥5 s submit delay | honeypot + signed time token (3 s–6 h) + real-IP rate limit + IP bans + CSRF + content classifier with moderation queue (2026-10-22, `docs/guestbook-spam.md`) | [x] |
| banned IP cannot post ("V diskuzi již nemůžete přispívat!") | same | [x] |
| e-mail | none sent by the old app (Mailer was never called) → no SMTP needed | [x] |

## 4. SEO / analytics / misc

| item | new | status |
|---|---|---|
| `<title>` = `<page> \| SCUBASW` | kept, plus meta description, OG tags, canonical | [x] |
| `lang="en"` (wrong) | [~] `lang="cs"` | [x] |
| Google Analytics UA (Universal Analytics, dead since 2023) | [~] removed (no data collected since 2023); GA4 id can be added via env `GA_MEASUREMENT_ID` | [x] |
| footer contacts (Roman Kudela, Martin Šajnar) | kept | [x] |
| www → apex 301 | unchanged (Traefik) | [x] |

## 5. Platform

| item | status |
|---|---|
| PHP ≥ 8.5.12 (FrankenPHP base image) | [x] box: 8.5.12, display_errors/expose_php off |
| healthcheck hits the DB (`/-/health-check/readiness`) | [x] |
| migrations run as a one-off in `deploy.sh` before the web container starts | [x] verified on the box |
| no new Infisical keys (APP_SECRET derived from DB_PASSWORD, see D83) | [x] |
| user data volume mounted at the same public paths | [x] verified on the box |
| backups (`backup.mysql`, `backup.files`) labels kept, paths updated | [x] verified on the box |
| tests gate the release (PHPUnit, PHPStan, CS, migrations) | [x] |
| old tables kept, nothing dropped | [x] |

## 6. Verification evidence (local, against the production copy)

* **Migration rehearsal** (`php bin/verify-prod-copy.php <dump>`): 16/16 tables IDENTICAL (row count +
  md5 over every column), `doctrine:schema:validate` in sync, 0.7 s; all 4 users carry legacy md5
  hashes that `LegacyMd5PasswordHasher` verifies.
* **Password proof** (local DB only, throwaway user `legacy-test@example.invalid` with a 2014-style
  hash): login OK with mixed-case e-mail, hash upgraded to bcrypt `$2y$` on that login, same password
  keeps working; automated in `tests/Functional/AdminAuthTest.php`.
* **URL map** (380 URLs: every list page incl. every `?page=N`, every actuality/article/gallery id
  live or hidden, legacy variants) old live site vs new prod-mode build: 281× 200→200, 67× 302→302
  (same list target), 28× 301→301, and 4 intentional changes (`/default` 200→301 `/`, `/kontakt/5`
  200→301 `/kontakt`, `/admin/` and `/admin/sign/` 302→301 to the canonical admin URL). 0 regressions.
* **User data**: 499/500 files 200 with the same paths, `/upload/.htaccess` 403 (as before).
* **Admin**: every admin screen 200 with an existing session; CKEditor 5 loads existing posts with
  their YouTube iframes intact (headless Chrome, prod mode).

## 6b. Production cutover (2026-10-21, lily journal + D83)

* Deploy `sha-5f3b189` 21:48Z: migrations one-off OK (1.4 s), db container not re-created, production
  rows re-fingerprinted read-only: every 2014 row identical.
* Incident 21:49Z: the first verification crawl OOM-killed web (32 default FrankenPHP threads decoding
  originals for first-time thumbnails); auto-restarted in seconds. Fixed in `8ebff07` + lily `c0be9f9`
  (4 workers, `max_requests 500`, one image decode at a time, Imagick RAM cap, thumbnail warm-up in
  deploy). Re-verified: 380/380 page URLs as expected, 499/500 files 200 (`.htaccess` 403 as before),
  memory flat ~364 MiB / 768, 0 restarts, 0 errors.
* Not done on production by design: no login with the real accounts (their passwords are not known to
  us); the legacy-hash path is covered by tests and by the unchanged md5 hashes in the live table.
* Design overview: https://claude.ai/artifact/CJarc4HSWMRrRXvK9DYxk7

## 7. Operator follow-ups (not blocking)

* `scubaswtrinec_sessions` volume (2014 PHP session files) can be removed.
* Old tables are all kept and still used. `aa` (empty 2014 leftover) was dropped on 2026-10-22
  (`Version20261022100100`, reversible).
* ~750 of 921 photo originals were already missing before this rebuild (see §0) — restore them from
  an older backup if one exists; they appear automatically.
* The 2014 repo history contains `.docker/mysql-data/` (a dev MariaDB datadir) in this public repo.
* Optional: a dedicated `APP_SECRET` key in Infisical project `scubaswtrinec` (see D83).
