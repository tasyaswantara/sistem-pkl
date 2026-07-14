FROM composer:2 AS vendor

WORKDIR /app

COPY . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

FROM node:20-bookworm-slim AS assets

WORKDIR /app

COPY --from=vendor /app /app

RUN npm ci && npm run build

FROM php:8.3-fpm-bookworm AS app

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PORT=10000

RUN apt-get update && apt-get install -y --no-install-recommends \
        gettext-base \
        nginx \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libxml2-dev \
        libonig-dev \
        libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        mbstring \
        pdo_mysql \
        pdo_pgsql \
        zip \
        gd \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY nginx.conf /var/www/html/nginx.conf
COPY start.sh /start.sh

RUN chmod +x /start.sh \
    && mkdir -p /run/nginx \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/build

EXPOSE 10000

CMD ["/start.sh"]
