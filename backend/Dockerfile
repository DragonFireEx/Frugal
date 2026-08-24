FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    icu-dev \
    postgresql-dev \
    $PHPIZE_DEPS \
    git \
    unzip \
    bash

RUN docker-php-ext-install pdo pdo_pgsql intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-interaction --no-autoloader || true

COPY . .
RUN composer install --no-interaction --optimize-autoloader

CMD ["php-fpm"]
