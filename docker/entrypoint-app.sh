#!/bin/sh
set -e
cd /var/www/html
if [ -f artisan ]; then
  # Bind mount ghi đè bootstrap/cache từ máy dev (có provider dev) trong khi vendor là image --no-dev → regenerate cho khớp.
  rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php 2>/dev/null || true
  php artisan package:discover --ansi

  # Link tạo trên Windows đôi khi trỏ path kiểu /mnt/host/... — trong nginx:alpine không dereference được → ảnh /storage hỏng.
  # Xóa symlink cũ để artisan tạo lại (tương đối) ngay trong container Linux.
  if [ -L public/storage ]; then
    rm -f public/storage
  fi
  php artisan storage:link --force 2>/dev/null || true
fi
exec docker-php-entrypoint php-fpm
