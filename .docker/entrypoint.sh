#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- apache2-foreground "$@"
fi

# In dev the source tree is bind-mounted over /app, so vendor/ may be missing and
# composer has to run here. In prod (APP_ENV=prod, set by the Dockerfile's prod
# stage) dependencies are already baked into the image, and re-running composer on
# every container start would make BOOTING depend on packagist/github being
# reachable — turning a registry blip into a failed healthcheck and an aborted
# blue-green rollout.
if [ "${APP_ENV}" != "prod" ]; then
	composer install
fi

mkdir -p www/webtemp
chmod 777 www/webtemp

mkdir -p temp/sessions
chmod 777 temp/sessions

mkdir -p temp/cache
chmod 777 temp/cache

mkdir -p log
chmod 777 log

mkdir -p www/upload/
chmod 777 www/upload/

mkdir -p www/img/gallery/
chmod 777 www/img/gallery/

mkdir -p www/img/instructor/
chmod 777 www/img/instructor/

mkdir -p www/img/link/
chmod 777 www/img/link/

mkdir -p www/img/actuality/
chmod 777 www/img/actuality/

mkdir -p www/img/article/
chmod 777 www/img/article/

exec "$@"
