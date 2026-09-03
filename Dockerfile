FROM php:7.1-apache as dev

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_HOME="/.composer" \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=1 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=15000 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=192 \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE=10

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# Very convenient PHP extensions installer: https://github.com/mlocati/docker-php-extension-installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# php:7.1-apache is built on Debian buster, which is EOL and has been RETIRED
# from deb.debian.org to archive.debian.org. The base image still points at the
# live mirrors, so a plain `apt-get update` now fails with
# "does not have a Release file" and the whole build dies. Repoint at the
# archive (and drop buster-updates, which does not exist there). Check-Valid-Until
# is disabled because the archived Release files are long past their expiry.
# Remove this block if/when the base image moves off buster.
RUN sed -i \
        -e 's|http://deb.debian.org/debian|http://archive.debian.org/debian|g' \
        -e 's|http://security.debian.org/debian-security|http://archive.debian.org/debian-security|g' \
        -e '/buster-updates/d' \
        /etc/apt/sources.list \
    && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99archive

RUN mkdir /.composer \
    && mkdir /usr/tmp \
    && apt-get update && apt-get install -y \
        git \
        zip \
        ca-certificates \
        curl \
        lsb-release \
        gnupg \
    && install-php-extensions \
        exif \
        bcmath \
        intl \
        pcntl \
        zip \
        pdo_mysql \
        opcache \
        apcu \
        gd


COPY ./.docker/apache/site.conf /etc/apache2/sites-available/000-default.conf
COPY ./.docker/apache/mpm-limits.conf /etc/apache2/conf-available/mpm-limits.conf
RUN a2enconf mpm-limits

COPY ./.docker/entrypoint.sh /usr/local/bin/docker-php-entrypoint
RUN chmod +x /usr/local/bin/docker-php-entrypoint

RUN a2enmod rewrite

WORKDIR /app

FROM dev as prod

# APP_ENV=prod tells the entrypoint that dependencies are already baked in, so it
# must NOT run `composer install` on every container start. On the old image that
# runtime install added a network dependency (packagist/github) to *booting* —
# a registry hiccup would have failed the healthcheck and aborted a blue-green
# rollout, for a step whose result is already in the image.
ENV APP_ENV=prod

COPY . /app
# Flags kept deliberately minimal (only --no-interaction, for CI): this is a
# 2014-era Nette 2.1 app and the produced vendor/ tree should stay byte-comparable
# to the image that has been serving production.
RUN composer install --no-interaction
