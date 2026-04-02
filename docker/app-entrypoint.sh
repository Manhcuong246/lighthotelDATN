#!/bin/sh
set -e
cd /var/www/html
# Vendor trong image là --no-dev; cache từ máy host có thể chứa Pail/Sail — tạo lại cho khớp
php artisan package:discover --ansi
# Symlink public/storage → storage/app/public (ảnh upload không 404)
php artisan storage:link --force 2>/dev/null || php artisan storage:link 2>/dev/null || true
exec docker-php-entrypoint "$@"
