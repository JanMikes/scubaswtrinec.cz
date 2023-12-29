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

COPY ./.docker/entrypoint.sh /usr/local/bin/docker-php-entrypoint
RUN chmod +x /usr/local/bin/docker-php-entrypoint

RUN a2enmod rewrite

WORKDIR /app

FROM dev as prod

COPY . /app
RUN composer install
