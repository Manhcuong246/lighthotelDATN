#!/usr/bin/env bash
# Tải ảnh mẫu vào storage/app/public/room_images/ và cập nhật DB (máy Linux/server).
set -euo pipefail
cd "$(dirname "$0")/.."
echo ">> php artisan room-images:fetch $*"
php artisan room-images:fetch "$@"
echo ""
echo ">> (optional) php artisan room-images:migrate-legacy"
