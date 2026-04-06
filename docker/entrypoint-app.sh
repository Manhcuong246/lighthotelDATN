#!/bin/sh
set -e
cd /var/www/html
if [ -f artisan ]; then
  # Link tạo trên Windows đôi khi trỏ path kiểu /mnt/host/... — trong nginx:alpine không dereference được → ảnh /storage hỏng.
  # Xóa symlink cũ để artisan tạo lại (tương đối) ngay trong container Linux.
  if [ -L public/storage ]; then
    rm -f public/storage
  fi
  php artisan storage:link --force 2>/dev/null || true
fi
exec docker-php-entrypoint php-fpm
