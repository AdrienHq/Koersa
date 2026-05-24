# PHP-FPM image for local development.
# The application source is bind-mounted at runtime (see compose.yaml), so this
# image only provides the runtime, extensions, and Composer.
FROM php:8.4-fpm-alpine

# Runtime libraries for the compiled extensions and tooling Composer relies on.
RUN apk add --no-cache \
        git \
        unzip \
        icu-libs \
        libpq \
        libzip

# Compile the extensions, then drop the build toolchain to keep the image lean.
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        postgresql-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql opcache zip \
    && pecl install redis pcov \
    && docker-php-ext-enable redis pcov \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/koersa.ini

WORKDIR /app
