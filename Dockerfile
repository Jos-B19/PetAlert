# syntax=docker/dockerfile:1

FROM php:8.3-apache AS php-base

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    PORT=10000 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        dom \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && a2enmod headers rewrite \
    && sed -i 's/^Listen 80$/Listen 0.0.0.0:10000/' /etc/apache2/ports.conf \
    && sed -i 's#<VirtualHost \*:80>#<VirtualHost *:10000>#' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
    && printf '%s\n' \
        '<Directory /var/www/html/public>' \
        '    Options FollowSymLinks' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
        >> /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

FROM php-base AS composer-deps

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY . .

RUN mkdir -p bootstrap/cache \
    && COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader

FROM node:22-bookworm-slim AS frontend-build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts --no-audit --no-fund

COPY . .
RUN npm run build

FROM php-base AS production

WORKDIR /var/www/html

COPY . .
COPY --from=composer-deps /var/www/html/vendor/ ./vendor/
COPY --from=composer-deps /var/www/html/bootstrap/cache/ ./bootstrap/cache/
COPY --from=frontend-build /app/public/build/ ./public/build/
COPY docker/certs/ /tmp/aiven-certs/

RUN if [ -f /tmp/aiven-certs/ca.pem ]; then \
        install -o root -g root -m 0644 /tmp/aiven-certs/ca.pem /etc/ssl/certs/aiven-ca.pem; \
    fi \
    && rm -rf /tmp/aiven-certs \
    && mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 10000

CMD ["apache2-foreground"]
