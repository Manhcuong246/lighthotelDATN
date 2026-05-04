FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

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

ENV LANG=C.UTF-8
ENV LC_ALL=C.UTF-8

COPY . .
COPY --from=vendor /app/vendor ./vendor

# UTF-8 mặc định cho tiếng Việt (file nằm trong repo; không phụ thuộc COPY .)
COPY docker/php/zz-charset.ini /usr/local/etc/php/conf.d/zz-charset.ini
COPY docker/php/zz-uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini

RUN php artisan package:discover --ansi

RUN mkdir -p storage bootstrap/cache \
  && chown -R www-data:www-data storage bootstrap/cache

# PHP-FPM mặc định clear_env=yes → Laravel trong container không thấy DB_HOST từ docker compose (chỉ đọc .env host).
RUN sed -i 's/^;clear_env = no/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000
CMD ["php-fpm"]
