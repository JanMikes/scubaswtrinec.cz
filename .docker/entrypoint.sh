#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- apache2-foreground "$@"
fi

composer install

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

exec "$@"
