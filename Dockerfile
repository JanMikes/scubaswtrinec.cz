FROM ghcr.io/thedevs-cz/php:8.5

ENV APP_ENV="prod" \
    APP_DEBUG=0 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

RUN rm $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini

COPY --link .docker/Caddyfile /etc/caddy/Caddyfile
COPY --link --chmod=755 .docker/on-startup.sh /docker-entrypoint.d/on-startup.sh

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader

COPY . .

# Asset build needs no database; give the container a throwaway value so the kernel boots.
RUN DATABASE_PASSWORD=build composer install --no-dev --no-interaction --classmap-authoritative \
    && DATABASE_PASSWORD=build bin/console importmap:install \
    && DATABASE_PASSWORD=build bin/console tailwind:build --minify \
    && DATABASE_PASSWORD=build bin/console asset-map:compile \
    && rm -rf var/cache/* var/log/* \
    && mkdir -p public/img public/upload var \
    && chmod -R 777 var

ARG APP_VERSION=dev
ENV APP_VERSION="${APP_VERSION}"
