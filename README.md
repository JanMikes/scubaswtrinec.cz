# scubaswtrinec.cz

Website of the diving club SCUBA S.W. Třinec: news, articles, photo galleries, instructors,
discussion, links, contact, and an admin at `/admin`.

Symfony 8.1 on FrankenPHP (`ghcr.io/thedevs-cz/php:8.5`), Doctrine ORM mapped onto the club's
original 2014 MariaDB schema, Twig + Tailwind (AssetMapper), CKEditor 5 in the admin.
Rebuilt in 2026 from the 2014 Nette 2.1 / PHP 7.1 site — see `docs/rebuild-checklist.md`.

## Local development

```bash
docker compose up -d            # web on http://localhost:8080, MariaDB 11.4 (prod flags)
docker compose run --rm web bin/console tailwind:build --watch   # or: --profile assets
docker compose exec web composer test        # PHPUnit (builds a scratch *_test database)
docker compose exec web composer phpstan
docker compose exec web composer cs:check
```

Rehearse the production migration on a copy of the production dump (never commit the dump):
`docker compose exec web php bin/verify-prod-copy.php var/prod-dump.sql`.

## Deployment

Push to `main` → `Tests` → `Release` (builds `ghcr.io/janmikes/scubaswtrinec.cz:sha-…` and triggers
the lily deploy webhook). The stack definition (`compose.yaml`, `deploy.sh`) lives in the lily.srv
infra repo under `apps/scubaswtrinec/`.
