#!/bin/sh
set -e
cd /var/www/html
# Vendor trong image là --no-dev; cache từ máy host có thể chứa Pail/Sail — tạo lại cho khớp
php artisan package:discover --ansi
exec docker-php-entrypoint "$@"
