#!/usr/bin/env bash
# Runs from the base image's /docker-entrypoint.d/ before FrankenPHP starts.
#
# Production: migrations do NOT run here. lily's deploy.sh runs them as a one-off
# (`docker compose run --rm --no-deps web bin/console doctrine:migrations:migrate`) BEFORE the new
# web container starts, so a failed migration aborts the deploy with nothing changed.
# Local dev: dependencies + migrations on boot for convenience.
set -e

ENVIRONMENT="${APP_ENV:-dev}"

if [[ "$ENVIRONMENT" == "dev" ]]; then
    echo "== dev: composer install + migrations =="
    rm -rf var/cache/*
    composer install --no-interaction
    wait-for-it "${DATABASE_HOST:-db}:${DATABASE_PORT:-3306}" --timeout=30
    bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

mkdir -p var/cache var/log
chmod -R 777 var
