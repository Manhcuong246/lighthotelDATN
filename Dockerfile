FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Chưa có artisan → bỏ script post-install (package:discover chạy ở stage app)
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.* ./
COPY resources ./resources
RUN npm run build

FROM php:8.2-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    zlib-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    mysql-client \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip intl gd opcache \
  && rm -rf /var/cache/apk/*

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN php artisan package:discover --ansi

RUN mkdir -p storage bootstrap/cache \
  && chown -R www-data:www-data storage bootstrap/cache

COPY docker/app-entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN chmod +x /usr/local/bin/app-entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/app-entrypoint.sh"]
CMD ["php-fpm"]
